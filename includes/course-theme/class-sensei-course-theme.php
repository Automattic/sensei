<?php
/**
 * File containing Sensei_Course_Theme class.
 *
 * @package sensei-lms
 * @since   4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use \Sensei\Blocks\Course_Theme;

/**
 * Load the 'Sensei Course Theme' theme for the /learn subsite.
 *
 * @since 4.0.0
 */
class Sensei_Course_Theme {
	/**
	 * URL prefix for loading the course theme.
	 */
	const QUERY_VAR = 'learn';

	/**
	 * Directory for the course theme.
	 */
	const THEME_NAME = 'sensei-course-theme';

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_Course_Theme constructor. Prevents other instances from being created outside of `self::instance()`.
	 */
	private function __construct() {
	}

	/**
	 * Fetches an instance of the class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initializes the Course Theme.
	 */
	public function init() {
		add_action( 'setup_theme', [ $this, 'add_rewrite_rules' ], 0, 10 );
		add_action( 'setup_theme', [ $this, 'maybe_override_theme' ], 0, 20 );

	}

	/**
	 * Is the theme active for the current request.
	 *
	 * @return bool
	 */
	public function is_active() {
		return get_query_var( self::QUERY_VAR );
	}

	/**
	 * Add the URL prefix the theme is active under.
	 *
	 * @param string $path
	 *
	 * @return string|void
	 */
	public function prefix_url( $path = '' ) {

		// TODO also support non-pretty permalink version.

		return home_url( '/' . self::QUERY_VAR . '/' . $path );
	}

	/**
	 * Replace theme for the current request if it's for course theme mode.
	 */
	public function maybe_override_theme() {

		// Do a cheaper preliminary check first.
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! preg_match( '#' . preg_quote( '/' . self::QUERY_VAR . '/', '#' ) . '#i', $uri ) && ! isset( $_GET[ self::QUERY_VAR ] ) ) {
			return;
		}

		// Then parse the request and make sure the query var is correct.
		wp();

		if ( get_query_var( self::QUERY_VAR ) ) {
			$this->override_theme();
		}
	}

	/**
	 * Load a bundled theme for the request.
	 */
	private function override_theme() {

		add_action( 'theme_root', [ $this, 'get_plugin_themes_root' ] );
		add_action( 'template', [ $this, 'theme_template' ] );
		add_action( 'stylesheet', [ $this, 'theme_stylesheet' ] );
		add_action( 'theme_root_uri', [ $this, 'theme_root_uri' ] );

		add_filter( 'sensei_use_sensei_template', '__return_false' );
		add_filter( 'template_include', [ $this, 'get_wrapper_template' ] );
		add_filter( 'the_content', [ $this, 'override_template_content' ] );
		add_filter( 'body_class', [ $this, 'add_sensei_theme_body_class' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );

	}

	/**
	 * Add a route for loading the course theme.
	 *
	 * @access private
	 */
	public function add_rewrite_rules() {
		add_rewrite_rule( '^' . self::QUERY_VAR . '/([^/]*)/([^/]*)/?', 'index.php?' . self::QUERY_VAR . '=1&post_type=$matches[1]&name=$matches[2]', 'top' );
		add_rewrite_tag( '%' . self::QUERY_VAR . '%', '([^?]+)' );
		// TODO 4.0.0 Make sure new rewrite rules are flushed after update.
	}

	/**
	 * Get course theme name.
	 *
	 * @access private
	 *
	 * @return string
	 */
	public function theme_template() {
		return self::THEME_NAME;
	}

	/**
	 * Get course theme name.
	 *
	 * @access private
	 *
	 * @return string
	 */
	public function theme_stylesheet() {
		return self::THEME_NAME;
	}

	/**
	 * Root URL for bundled themes.
	 *
	 * @access private
	 *
	 * @return string
	 */
	public function theme_root_uri() {
		return Sensei()->plugin_url . '/themes';
	}

	/**
	 * Root directory for bundled themes.
	 *
	 * @access private
	 *
	 * @return string
	 */
	public function get_plugin_themes_root() {
		return Sensei()->plugin_path() . 'themes';
	}

	/**
	 * Directory for course theme.
	 *
	 * @access private
	 *
	 * @return string
	 */
	public function get_course_theme_root() {
		return $this->get_plugin_themes_root() . '/' . self::THEME_NAME;
	}

	/**
	 * Get the wrapper template.
	 *
	 * @access private
	 *
	 * @return string The wrapper template path.
	 */
	public function get_wrapper_template() {
		return locate_template( 'index.php' );
	}

	/**
	 * It overrides the template content, loading the respective
	 * template and rendering the blocks from the template.
	 *
	 * @access private
	 *
	 * @return string The content with template and rendered blocks.
	 */
	public function override_template_content() {
		// Remove filter to avoid infinite loop.
		remove_filter( 'the_content', [ $this, 'override_template_content' ] );

		ob_start();
		$template = get_single_template();
		load_template( $template );
		$output = ob_get_clean();

		// Return template content with rendered blocks.
		return do_blocks( $output );
	}

	/**
	 * Add Sensei theme body class.
	 *
	 * @access private
	 *
	 * @param string[] $classes
	 *
	 * @return string[] $classes
	 */
	public function add_sensei_theme_body_class( $classes ) {
		$classes[] = self::THEME_NAME;

		return $classes;
	}

	/**
	 * Enqueue styles.
	 *
	 * @access private
	 */
	public function enqueue_styles() {
		Sensei()->assets->enqueue( self::THEME_NAME . '-style', 'css/sensei-course-theme.css' );
		if ( ! is_admin() ) {
			Sensei()->assets->enqueue( self::THEME_NAME . '-script', 'course-theme/course-theme.js' );
			Sensei()->assets->enqueue_script( 'sensei-blocks-frontend' );
		}
	}

}

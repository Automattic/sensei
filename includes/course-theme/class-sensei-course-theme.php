<?php
/**
 * File containing Sensei_Course_Theme class.
 *
 * @package sensei-lms
 * @since   3.13.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use \Sensei\Blocks\Course_Theme;

/**
 * Load the 'Sensei Course Theme' theme for the /learn subsite.
 *
 * @since 3.15.0
 */
class Sensei_Course_Theme {
	/**
	 * URL prefix for loading the course theme.
	 */
	const QUERY_VAR = 'learn';

	/**
	 * Course theme preview query var.
	 */
	const PREVIEW_QUERY_VAR = 'sensei_theme_preview';

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
	 *
	 * @param Sensei_Main $sensei Sensei object.
	 */
	public function init( $sensei ) {

		if ( ! $sensei->feature_flags->is_enabled( 'course_theme' ) ) {
			// As soon this feature flag check is removed, the `$sensei` argument can also be removed.
			return;
		}

		add_action( 'setup_theme', [ $this, 'add_rewrite_rules' ], 0, 10 );
		add_action( 'setup_theme', [ $this, 'maybe_override_theme' ], 0, 20 );
		add_action( 'template_redirect', [ Sensei_Course_Theme_Lesson::instance(), 'init' ] );
		add_action( 'template_redirect', [ Sensei_Course_Theme_Quiz::instance(), 'init' ] );

	}


	/**
	 * Is the theme active for the current request.
	 *
	 * @return bool
	 */
	public function is_active() {
		return self::THEME_NAME === get_stylesheet();
	}

	/**
	 * Add the URL prefix the theme is active under.
	 *
	 * @param string $path
	 *
	 * @return string|void
	 */
	public function get_theme_redirect_url( $path = '' ) {

		if ( '' === get_option( 'permalink_structure' ) ) {
			return home_url( add_query_arg( [ self::QUERY_VAR => 1 ], $path ) );
		}

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
	public function override_theme() {

		add_filter( 'theme_root', [ $this, 'get_plugin_themes_root' ] );
		add_filter( 'pre_option_stylesheet_root', [ $this, 'get_plugin_themes_root' ] );
		add_filter( 'pre_option_template_root', [ $this, 'get_plugin_themes_root' ] );
		add_filter( 'pre_option_template', [ $this, 'theme_template' ] );
		add_filter( 'pre_option_stylesheet', [ $this, 'theme_stylesheet' ] );
		add_filter( 'theme_root_uri', [ $this, 'theme_root_uri' ] );

		add_filter( 'sensei_use_sensei_template', '__return_false' );
		add_filter( 'body_class', [ $this, 'add_sensei_theme_body_class' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );

		add_action( 'template_redirect', [ $this, 'admin_menu_init' ], 20 );
		add_action( 'admin_init', [ $this, 'admin_menu_init' ], 20 );

	}

	/**
	 * Add a route for loading the course theme.
	 *
	 * @access private
	 */
	public function add_rewrite_rules() {
		global $wp;
		$wp->add_query_var( self::QUERY_VAR );
		add_rewrite_rule( '^' . self::QUERY_VAR . '/([^/]*)/([^/]*)/?\??(.*)', 'index.php?' . self::QUERY_VAR . '=1&post_type=$matches[1]&name=$matches[2]&$matches[3]', 'top' );
		add_rewrite_tag( '%' . self::QUERY_VAR . '%', '([^?]+)' );

		if ( ! get_option( 'sensei_course_theme_query_var_flushed' ) ) {
			flush_rewrite_rules( false );
			update_option( 'sensei_course_theme_query_var_flushed', 1 );
		}
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

			$check_circle_icon = Sensei()->assets->get_icon( 'check-circle' );
			wp_add_inline_script( self::THEME_NAME . '-script', "window.sensei = window.sensei || {}; window.sensei.checkCircleIcon = '$check_circle_icon';", 'before' );
		}
	}

	/**
	 * Tells if sensei theme is in preview mode.
	 *
	 * @param int $course_id The id of the course.
	 *
	 * @return bool
	 */
	public static function is_preview_mode( $course_id ) {
		// Do not allow sensei preview if not an administrator.
		if ( ! current_user_can( 'manage_sensei' ) ) {
			return false;
		}

		// Do not allow sensei preview if it is not a course related page.
		$course_id = intval( \Sensei_Utils::get_current_course() );
		if ( ! $course_id ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The user is administrator at this point. No need.
		$query_var = isset( $_GET[ self::PREVIEW_QUERY_VAR ] ) ? intval( $_GET[ self::PREVIEW_QUERY_VAR ] ) : 0;

		// Do not allow sensei preview if requested course id does not match.
		if ( $query_var !== $course_id ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns the url for sensei theme customization.
	 */
	public static function get_sensei_theme_customize_url() {
		// Get the last modified lesson.
		$result = get_posts(
			[
				'posts_per_page' => 1,
				'post_type'      => 'lesson',
				'orderby'        => 'modified',
				'meta'           => [
					'key'     => '_lesson_course',
					'compare' => 'EXISTS',
				],
			]
		);
		if ( empty( $result ) ) {
			return '';
		}

		$lesson      = $result[0];
		$course_id   = get_post_meta( $lesson->ID, '_lesson_course', true );
		$preview_url = '/?p=' . $lesson->ID;
		if ( ! Sensei_Course_Theme_Option::has_sensei_theme_enabled( $course_id ) ) {
			$preview_url .= '&learn=1&' . self::PREVIEW_QUERY_VAR . '=' . $course_id;
		}
		return '/wp-admin/customize.php?autofocus[section]=Sensei_Course_Theme_Option&url=' . rawurlencode( $preview_url );
	}

	/**
	 * Replace 'Edit site' in admin bar to point to the current theme template.
	 */
	public function admin_menu_init() {
		remove_action( 'admin_bar_menu', 'wp_admin_bar_edit_site_menu', 40 );
		add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_edit_site_menu' ], 39 );

	}

	/**
	 * Add 'Edit site' in admin bar opening the current theme template.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 *
	 * @return void
	 */
	public function add_admin_bar_edit_site_menu( WP_Admin_Bar $wp_admin_bar ) {

		if ( ! current_user_can( 'edit_theme_options' ) || is_admin() ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => 'site-editor',
				'title' => __( 'Edit Site', 'sensei-lms' ),
				'href'  => admin_url( 'site-editor.php?learn=1&postType=wp_template&postId=' . self::THEME_NAME . '//' . get_post_type() ),
			)
		);
	}

}

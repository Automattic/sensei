<?php
/**
 * File containing Sensei_Course_Theme class.
 *
 * @package sensei-lms
 * @since 3.13.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Course_Theme class.
 *
 * @since 3.13.4
 */
class Sensei_Course_Theme {
	const THEME_POST_META_NAME = '_course_theme';
	const WORDPRESS_THEME      = 'wordpress-theme';
	const SENSEI_THEME         = 'sensei-theme';

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_Course_Theme constructor. Prevents other instances from being created outside of `self::instance()`.
	 */
	private function __construct() {}

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
		add_action( 'admin_enqueue_scripts', [ $this, 'add_feature_flag_inline_script' ] );

		if ( ! $sensei->feature_flags->is_enabled( 'course_theme' ) ) {
			// As soon this feature flag check is removed, the `$sensei` argument can also be removed.
			return;
		}

		// Init blocks.
		new \Sensei\Blocks\Course_Theme();

		add_action( 'init', [ $this, 'register_post_meta' ] );
		add_action( 'template_redirect', [ $this, 'maybe_use_sensei_theme_template' ] );
	}

	/**
	 * Add feature flag inline script.
	 *
	 * @access private
	 */
	public function add_feature_flag_inline_script() {
		$screen  = get_current_screen();
		$enabled = Sensei()->feature_flags->is_enabled( 'course_theme' ) ? 'true' : 'false';

		if ( 'course' === $screen->id ) {
			wp_add_inline_script( 'sensei-admin-course-edit', 'window.senseiCourseThemeFeatureFlagEnabled = ' . $enabled, 'before' );
		}
	}

	/**
	 * Use Sensei Theme template if the theme is set for the current page.
	 *
	 * @access private
	 */
	public function maybe_use_sensei_theme_template() {
		if ( ! is_single() || ! in_array( get_post_type(), [ 'lesson', 'quiz' ], true ) ) {
			return;
		}

		if ( get_post_type() === 'quiz' ) {
			$lesson_id = Sensei()->quiz->get_lesson_id( get_the_ID() );
		} else {
			$lesson_id = get_the_ID();
		}

		$course_id = Sensei()->lesson->get_course_id( $lesson_id );
		$theme     = get_post_meta( $course_id, self::THEME_POST_META_NAME, true );

		if ( self::SENSEI_THEME !== $theme ) {
			return;
		}

		add_filter( 'sensei_use_sensei_template', '__return_false' );
		add_filter( 'template_include', [ $this, 'get_wrapper_template' ] );
		add_filter( 'the_content', [ $this, 'override_template_content' ] );
		add_filter( 'body_class', [ $this, 'add_sensei_theme_body_class' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
	}

	/**
	 * Get the wrapper template.
	 *
	 * @access private
	 *
	 * @return string The wrapper template path.
	 */
	public function get_wrapper_template() {
		return Sensei_Templates::locate_template( 'course-theme/index.php' );
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
		Sensei_Templates::get_template( 'course-theme/single-' . get_post_type() . '.php' );
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
		$classes[] = 'sensei-course-theme';

		return $classes;
	}

	/**
	 * Enqueue styles.
	 *
	 * @access private
	 */
	public function enqueue_styles() {
		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- External dependency that should be cached.
		wp_enqueue_style( 'inter-googlefonts', 'https://fonts.googleapis.com/css?family=Inter:normal,bold' );
		Sensei()->assets->enqueue( 'sensei-course-theme', 'css/sensei-course-theme.css' );
	}

	/**
	 * Register post meta.
	 *
	 * @access private
	 */
	public function register_post_meta() {
		register_post_meta(
			'course',
			self::THEME_POST_META_NAME,
			[
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'default'       => self::WORDPRESS_THEME,
				'auth_callback' => function( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			]
		);
	}
}

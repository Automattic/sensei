<?php
/**
 * File containing Sensei_Course_Theme_Option class.
 *
 * @package sensei-lms
 * @since   3.13.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handle using the Course Theme for a given course.
 *
 * @since 3.13.4
 */
class Sensei_Course_Theme_Option {
	/**
	 * Course post meta for theme preference.
	 */
	const THEME_POST_META_NAME = '_course_theme';

	/**
	 * Default theme setting value.
	 */
	const WORDPRESS_THEME = 'wordpress-theme';

	/**
	 * Course theme setting value.
	 */
	const SENSEI_THEME = 'sensei-theme';

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_Course_Theme_Option constructor. Prevents other instances from being created outside of `self::instance()`.
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
		add_action( 'template_redirect', [ $this, 'maybe_redirect_to_course_theme' ] );
		add_action( 'template_redirect', [ Sensei_Course_Theme_Lesson::instance(), 'init' ] );
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
	public function maybe_redirect_to_course_theme() {

		if ( Sensei_Course_Theme::instance()->is_active() || ! $this->should_use_sensei_theme() ) {
			return;
		}

		$url = str_replace( trailingslashit( home_url() ), '', get_permalink() );
		wp_safe_redirect( Sensei_Course_Theme::instance()->get_theme_redirect_url( $url ) );
	}

	/**
	 * Check if it should use Sensei Theme template.
	 *
	 * @return boolean
	 */
	public function should_use_sensei_theme() {

		if ( ! is_single() || ! in_array( get_post_type(), [ 'lesson', 'quiz' ], true ) ) {
			return false;
		}

		$course_id = \Sensei_Utils::get_current_course();

		if ( null === $course_id ) {
			return false;
		}

		$theme = get_post_meta( $course_id, self::THEME_POST_META_NAME, true );

		if ( self::SENSEI_THEME !== $theme ) {
			return false;
		}

		return true;
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

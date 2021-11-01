<?php
/**
 * File containing Sensei_Course_Navigation class.
 *
 * @package sensei-lms
 * @since 3.16.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Course_Navigation class.
 *
 * @since 3.16.0
 */
class Sensei_Course_Navigation {
	const TEMPLATE_POST_META_NAME                       = '_course_navigation_template';
	const INSTALLED_AFTER_COURSE_NAVIGATION_OPTION_NAME = 'sensei_installed_after_course_navigation';

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_Course_Navigation constructor. Prevents other instances from being created outside of `self::instance()`.
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
	 * Initializes the Course Navigation.
	 *
	 * @param Sensei_Main $sensei Sensei object.
	 */
	public function init( $sensei ) {
		add_action( 'admin_enqueue_scripts', [ $this, 'add_feature_flag_inline_script' ] );

		if ( ! $sensei->feature_flags->is_enabled( 'course_navigation' ) ) {
			// As soon this feature flag check is removed, the `$sensei` argument can also be removed.
			return;
		}

		add_action( 'init', [ $this, 'register_post_meta' ] );
	}

	/**
	 * Add feature flag inline script.
	 *
	 * @access private
	 */
	public function add_feature_flag_inline_script() {
		$screen  = get_current_screen();
		$enabled = Sensei()->feature_flags->is_enabled( 'course_navigation' ) ? 'true' : 'false';

		if ( 'course' === $screen->id ) {
			wp_add_inline_script( 'sensei-admin-course-edit', 'window.senseiCourseNavigationFeatureFlagEnabled = ' . $enabled, 'before' );
		}
	}

	/**
	 * Register post meta.
	 *
	 * @access private
	 */
	public function register_post_meta() {
		$installed_after_course_navigation = 1 === intval( get_option( self::INSTALLED_AFTER_COURSE_NAVIGATION_OPTION_NAME ) );

		register_post_meta(
			'course',
			self::TEMPLATE_POST_META_NAME,
			[
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'default'       => $installed_after_course_navigation ? 'sensei-lesson-template' : 'default-post-template',
				'auth_callback' => function( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			]
		);
	}
}

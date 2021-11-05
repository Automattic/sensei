<?php
/**
 * File containing Sensei_Course_Theme class.
 *
 * @package sensei-lms
 * @since 3.16.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Course_Theme class.
 *
 * @since 3.16.0
 */
class Sensei_Course_Theme {
	const THEME_POST_META_NAME = '_course_theme';

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

		add_action( 'init', [ $this, 'register_post_meta' ] );
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
				'default'       => 'wordpress-theme',
				'auth_callback' => function( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			]
		);
	}
}

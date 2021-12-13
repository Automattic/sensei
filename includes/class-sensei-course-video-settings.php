<?php
/**
 * File containing the Sensei_Course_Progression_Settings class.
 *
 * @package sensei-lms
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Adds the course progression settings to the course.
 *
 * @since 4.0.0
 */
class Sensei_Course_Video_Settings {

	/**
	 * Sensei course progression video autocomplete meta field.
	 */
	const COURSE_VIDEO_AUTOCOMPLETE = 'sensei_course_video_autocomplete';

	/**
	 * Sensei course progression video autopause meta field.
	 */
	const COURSE_VIDEO_AUTOPAUSE = 'sensei_course_video_autopause';

	/**
	 * Sensei course progression required video meta field.
	 */
	const COURSE_VIDEO_REQUIRED = 'sensei_course_video_required';

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_Course_Progression_Settings constructor. Prevents other instances from being created outside of `self::instance()`.
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
	 * Initializes the Video-Based Course Progression.
	 *
	 * @param Sensei_Main $sensei Sensei object.
	 */
	public function init( $sensei ) {
		add_action( 'admin_enqueue_scripts', [ $this, 'add_feature_flag_inline_script' ] );

		if ( ! $sensei->feature_flags->is_enabled( 'video_based_course_progression' ) ) {
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
		$enabled = Sensei()->feature_flags->is_enabled( 'video_based_course_progression' ) ? 'true' : 'false';

		if ( 'course' === $screen->id ) {
			wp_add_inline_script( 'sensei-admin-course-edit', 'window.senseiVideoCourseProgressionFeatureFlagEnabled = ' . $enabled, 'before' );
		}
	}

	/**
	 * Register post meta.
	 *
	 * @access private
	 */
	public function register_post_meta() {
		$settings = [
			self::COURSE_VIDEO_AUTOCOMPLETE,
			self::COURSE_VIDEO_AUTOPAUSE,
			self::COURSE_VIDEO_REQUIRED,
		];
		foreach ( $settings as $setting ) {
			register_post_meta(
				'course',
				$setting,
				[
					'show_in_rest'  => true,
					'single'        => true,
					'type'          => 'boolean',
					'default'       => false,
					'auth_callback' => function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_course', $post_id );
					},
				]
			);
		}
	}
}

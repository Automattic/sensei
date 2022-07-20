<?php
/**
 * File containing the Sensei_Course_Progression_Settings class.
 *
 * @package sensei-lms
 * @since 3.15.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Adds the course progression settings to the course.
 *
 * @since 3.15.0
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
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_post_meta' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_scripts' ] );

		Sensei_Course_Video_Blocks_Youtube_Extension::instance()->init();
		Sensei_Course_Video_Blocks_Video_Extension::instance()->init();
		Sensei_Course_Video_Blocks_Vimeo_Extension::instance()->init();
		Sensei_Course_Video_Blocks_VideoPress_Extension::instance()->init();
	}

	/**
	 * Enqueues settings scripts on the frontend.
	 *
	 * @return void
	 */
	public function enqueue_frontend_scripts() {
		if ( is_admin() || get_post_type() !== 'lesson' ) {
			return;
		}

		Sensei()->assets->register(
			'sensei-course-video-blocks-extension',
			'js/frontend/course-video/video-blocks-extension.js',
			[ 'sensei-youtube-iframe-api', 'sensei-vimeo-iframe-api' ],
			true
		);

		$video_settings      = [
			'courseVideoAutoComplete' => (bool) $this->is_autocomplete_enabled(),
			'courseVideoAutoPause'    => (bool) $this->is_autopause_enabled(),
			'courseVideoRequired'     => (bool) $this->is_required(),
		];
		$video_settings_json = wp_json_encode( $video_settings );
		$script              = "window.sensei = window.sensei || {}; window.sensei.courseVideoSettings = $video_settings_json;";

		wp_add_inline_script( 'sensei-course-video-blocks-extension', $script, 'before' );
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

	/**
	 * Get the course video autocomplete setting.
	 *
	 * @return mixed
	 */
	public function is_autocomplete_enabled() {
		$lesson_course = get_post_meta( get_the_ID(), '_lesson_course', true );
		return get_post_meta( $lesson_course, self::COURSE_VIDEO_AUTOCOMPLETE, true );
	}

	/**
	 * Get the course video autopause setting.
	 *
	 * @return mixed
	 */
	public function is_autopause_enabled() {
		$lesson_course = get_post_meta( get_the_ID(), '_lesson_course', true );
		return get_post_meta( $lesson_course, self::COURSE_VIDEO_AUTOPAUSE, true );
	}

	/**
	 * Get the course video required setting.
	 *
	 * @return mixed
	 */
	public function is_required() {
		$lesson_course = get_post_meta( get_the_ID(), '_lesson_course', true );
		return get_post_meta( $lesson_course, self::COURSE_VIDEO_REQUIRED, true );
	}
}

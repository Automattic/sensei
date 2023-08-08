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
		// When there's no embed handler for YouTube, it uses the oembed filter.
		add_filter( 'embed_oembed_html', [ $this, 'enable_youtube_api' ], 11, 2 );
		// When there is an embed handler registered, it uses the embed handler filter.
		add_filter( 'embed_handler_html', [ $this, 'enable_youtube_api' ], 11, 2 );
	}

	/**
	 * Replace the YouTube iframe URL enabling JS API and providing origin.
	 *
	 * @access private
	 *
	 * @param string $html Embed HTML.
	 * @param string $url  Embed URL.
	 *
	 * @return string
	 */
	public function enable_youtube_api( $html, $url ): string {
		$host = wp_parse_url( $url, PHP_URL_HOST );

		// Skip if it's not a YouTube embed.
		if ( strpos( $host, 'youtu.be' ) === false && strpos( $host, 'youtube.com' ) === false ) {
			return $html;
		}

		return preg_replace_callback(
			'/src="(.*?)"/',
			function ( $matches ) {
				$modified_url = add_query_arg(
					array(
						'enablejsapi' => 1,
						'origin'      => esc_url( home_url() ),
					),
					$matches[1]
				);

				return 'src="' . $modified_url . '"';
			},
			$html
		);
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

		$post = get_post();
		if ( has_block( 'core/video', $post ) || has_block( 'core/embed', $post ) ) {
			Sensei()->assets->enqueue_script( 'sensei-course-video-blocks-extension' );
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

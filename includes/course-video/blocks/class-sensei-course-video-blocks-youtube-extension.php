<?php
/**
 * File containing the Sensei_Course_Video_Blocks_Youtube_Extension class.
 *
 * @package sensei-lms
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extends standard Embed block with YouTube specific functionality for video course progression
 *
 * @since 4.0.0
 */
class Sensei_Course_Video_Blocks_Youtube_Extension {
	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Manages Video course-related settings.
	 *
	 * @var Sensei_Course_Video_Settings
	 */
	private $settings;

	/**
	 * Returns an instance of the class.
	 *
	 * @param Sensei_Course_Video_Settings $settings
	 *
	 * @return Sensei_Course_Video_Blocks_Youtube_Extension
	 */
	public static function instance( Sensei_Course_Video_Settings $settings ) {
		if ( self::$instance ) {
			return self::$instance;
		}

		self::$instance = new self( $settings );
		return self::$instance;
	}

	/**
	 * Initialize the class and hooks.
	 *
	 * @param Sensei_Course_Video_Settings $settings
	 */
	public static function init( Sensei_Course_Video_Settings $settings ) {
		self::instance( $settings )->init_hooks();
	}

	/**
	 * Sensei_Youtube_Extension constructor.
	 *
	 * @param Sensei_Course_Video_Settings $settings
	 */
	private function __construct( Sensei_Course_Video_Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Initialize hooks.
	 */
	public function init_hooks() {
		add_filter( 'embed_oembed_html', [ $this, 'wrap_youtube' ], 10, 4 );
	}

	/**
	 * Wrap YouTube video in a container.
	 *
	 * @param string $html
	 * @param string $url
	 * @param array  $args
	 * @param int    $post_id
	 *
	 * @return string
	 */
	public function wrap_youtube( $html, $url, $args, $post_id ) {
		if ( ! $this->is_youtube_url( $url ) ) {
			return $html;
		}

		$this->enqueue_scripts();

		$html = preg_replace_callback(
			'/src="(.*?)"/',
			function( $matches ) {
				// Enable JS API and provide origin for the iframe.
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
		return '<div class="sensei-course-video-youtube-container">' . $html . '</div>';
	}

	/**
	 * Check if the URL is a YouTube URL.
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	private function is_youtube_url( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		return strpos( $url, 'youtu.be' ) !== false || strpos( $host, 'youtube.com' ) !== false;
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return void
	 */
	private function enqueue_scripts() {
		if ( is_admin() || get_post_type() !== 'lesson' ) {
			return;
		}

		$video_settings      = [
			'courseVideoAutoComplete' => (bool) $this->settings->is_autocomplete_enabled(),
			'courseVideoAutoPause'    => (bool) $this->settings->is_autopause_enabled(),
			'courseVideoRequired'     => (bool) $this->settings->is_required(),
		];
		$video_settings_json = wp_json_encode( $video_settings );
		$script              = "window.sensei = window.sensei || {}; window.sensei.courseVideoSettings = $video_settings_json;";

		wp_add_inline_script( 'sensei-course-video-blocks-youtube', $script, 'before' );
		wp_enqueue_script( 'sensei-course-video-blocks-youtube' );
	}

}

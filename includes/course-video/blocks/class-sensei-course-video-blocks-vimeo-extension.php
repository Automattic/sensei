<?php
/**
 * File containing the Sensei_Course_Video_Blocks_Vimeo_Extension class.
 *
 * @package sensei-lms
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extends standard Embed block with Vimeo specific functionality for video course progression
 *
 * @since 4.0.0
 */
class Sensei_Course_Video_Blocks_Vimeo_Extension {
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
	 * @return Sensei_Course_Video_Blocks_Vimeo_Extension
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
		add_filter( 'embed_oembed_html', [ $this, 'wrap_vimeo' ], 10, 4 );
	}

	/**
	 * Wrap Vimeo video in a container.
	 *
	 * @param string $html
	 * @param string $url
	 * @param array  $args
	 * @param int    $post_id
	 *
	 * @return string
	 */
	public function wrap_vimeo( $html, $url, $args, $post_id ) {
		if ( ! $this->is_vimeo_url( $url ) ) {
			return $html;
		}

		$this->enqueue_scripts();

		return '<div class="sensei-course-video-vimeo-container">' . $html . '</div>';
	}

	/**
	 * Check if the URL is a Vimeo URL.
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	private function is_vimeo_url( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		return in_array( $host, [ 'vimeo.com', 'player.vimeo.com' ], true );
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

		wp_add_inline_script( 'sensei-course-video-blocks-vimeo', $script, 'before' );
		wp_enqueue_script( 'sensei-course-video-blocks-vimeo' );
	}

}

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
class Sensei_Course_Video_Blocks_Video_Extension {
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
	 * @return Sensei_Course_Video_Blocks_Video_Extension
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
	 * Sensei_Course_Video_Blocks_Video_Extension constructor.
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
		add_filter( 'render_block_core/video', [ $this, 'wrap_video' ], 10, 3 );
	}

	/**
	 * Wrap YouTube video in a container.
	 *
	 * @param string   $content
	 * @param array    $parsed_block
	 * @param WP_Block $block
	 *
	 * @return string
	 */
	public function wrap_video( $content, $parsed_block, $block ) {
		$this->enqueue_scripts();

		return '<div class="sensei-course-video-video-container">' . $content . '</div>';
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

		wp_add_inline_script( 'sensei-course-video-blocks-video', $script, 'before' );
		wp_enqueue_script( 'sensei-course-video-blocks-video' );
	}

}

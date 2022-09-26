<?php
/**
 * File containing the Sensei_Course_Video_Blocks_Video_Extension class.
 *
 * @package sensei-lms
 * @since 3.15.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extends standard Video block with functionality for video course progression
 *
 * @since 3.15.0
 *
 * @deprecated 4.6.4
 */
class Sensei_Course_Video_Blocks_Video_Extension {
	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Returns an instance of the class.
	 *
	 * @deprecated 4.6.4
	 *
	 * @return Sensei_Course_Video_Blocks_Video_Extension
	 */
	public static function instance() {
		_deprecated_function( __METHOD__, '4.6.4' );

		if ( self::$instance ) {
			return self::$instance;
		}

		self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Sensei_Course_Video_Blocks_Video_Extension constructor.
	 *
	 * @deprecated 4.6.4
	 */
	private function __construct() {
		_deprecated_function( __METHOD__, '4.6.4' );
	}

	/**
	 * Initialize hooks.
	 *
	 * @deprecated 4.6.4
	 */
	public function init() {
		_deprecated_function( __METHOD__, '4.6.4' );

		add_filter( 'render_block_core/video', [ $this, 'wrap_video' ], 10, 1 );
	}

	/**
	 * Wrap Video in a container.
	 *
	 * @deprecated 4.6.4
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	public function wrap_video( $html ): string {
		_deprecated_function( __METHOD__, '4.6.4' );

		wp_enqueue_script( 'sensei-course-video-blocks-extension' );
		return '<div class="sensei-course-video-container video-extension">' . $html . '</div>';
	}
}

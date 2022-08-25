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
 * @deprecated $$next-version$$
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
	 * @deprecated $$next-version$$
	 *
	 * @return Sensei_Course_Video_Blocks_Video_Extension
	 */
	public static function instance() {
		_deprecated_function( __METHOD__, '$$next-version$$' );

		if ( self::$instance ) {
			return self::$instance;
		}

		self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Sensei_Course_Video_Blocks_Video_Extension constructor.
	 *
	 * @deprecated $$next-version$$
	 */
	private function __construct() {
		_deprecated_function( __METHOD__, '$$next-version$$' );
	}

	/**
	 * Initialize hooks.
	 *
	 * @deprecated $$next-version$$
	 */
	public function init() {
		_deprecated_function( __METHOD__, '$$next-version$$' );

		add_filter( 'render_block_core/video', [ $this, 'wrap_video' ], 10, 1 );
	}

	/**
	 * Wrap Video in a container.
	 *
	 * @deprecated $$next-version$$
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	public function wrap_video( $html ): string {
		_deprecated_function( __METHOD__, '$$next-version$$' );

		wp_enqueue_script( 'sensei-course-video-blocks-extension' );
		return '<div class="sensei-course-video-container video-extension">' . $html . '</div>';
	}
}

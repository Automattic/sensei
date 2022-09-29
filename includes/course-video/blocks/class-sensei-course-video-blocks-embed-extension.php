<?php
/**
 * File containing the Sensei_Course_Video_Blocks_Embed_Extension class.
 *
 * @package sensei-lms
 * @since 3.15.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Provides common logic for video block extensions to implement functionality for video course progression
 *
 * @since 3.15.0
 *
 * @deprecated 4.6.4
 */
abstract class Sensei_Course_Video_Blocks_Embed_Extension {
	/**
	 * Initialize hooks.
	 */
	public function init() {
		_deprecated_function( __METHOD__, '4.6.4' );

		add_filter( 'embed_oembed_html', [ $this, 'wrap_video' ], 10, 2 );
	}

	/**
	 * Returns wrapped video block.
	 *
	 * @deprecated 4.6.4
	 *
	 * @param string $html Video HTML.
	 * @param string $url  Video URL.
	 *
	 * @return string
	 */
	public function wrap_video( $html, $url ): string {
		_deprecated_function( __METHOD__, '4.6.4' );

		if ( ! $this->is_supported( $url ) ) {
			return $html;
		}

		wp_enqueue_script( 'sensei-course-video-blocks-extension' );

		return "<div class='sensei-course-video-container {$this->get_extension_class_name()}'>$html</div>";
	}

	/**
	 * Returns if the URL is supported by the extension.
	 *
	 * @deprecated 4.6.4
	 *
	 * @param string $url Video URL.
	 *
	 * @return bool
	 */
	abstract protected function is_supported( string $url ): bool;

	/**
	 * Returns a class name for the extension.
	 *
	 * @deprecated 4.6.4
	 *
	 * @return string
	 */
	abstract protected function get_extension_class_name(): string;
}

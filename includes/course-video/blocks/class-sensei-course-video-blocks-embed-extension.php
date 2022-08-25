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
 */
abstract class Sensei_Course_Video_Blocks_Embed_Extension {
	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_filter( 'embed_oembed_html', [ $this, 'wrap_video' ], 10, 2 );
	}

	/**
	 * Returns wrapped video block.
	 *
	 * @access private
	 *
	 * @param string $html Video HTML.
	 * @param string $url  Video URL.
	 *
	 * @return string
	 */
	public function wrap_video( $html, $url ): string {
		if ( $this->is_supported( $url ) ) {
			wp_enqueue_script( 'sensei-course-video-blocks-extension' );
		}

		return $html;
	}

	/**
	 * Returns if the URL is supported by the extension.
	 *
	 * @deprecated $$next-version$$
	 *
	 * @param string $url Video URL.
	 *
	 * @return bool
	 */
	abstract protected function is_supported( string $url ): bool;
}

<?php
/**
 * File containing the Sensei_Featured_Video_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block for Contact teacher button.
 */
class Sensei_Featured_Video_Block {

	/**
	 * Sensei_Featured_Video_Block constructor.
	 */
	public function __construct() {
		$this->register_block();
	}

	/**
	 * Register featured video block.
	 *
	 * @access private
	 */
	public function register_block() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/featured-video',
			[
				'render_callback' => [ $this, 'render_featured_video' ],
			],
			Sensei()->assets->src_path( 'blocks/featured-video' )
		);
	}

	/**
	 * Render the featured video block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block HTML.
	 *
	 * @return string The HTML of the block.
	 */
	public function render_featured_video( array $attributes, string $content ): string {
		global $sensei_template_has_lesson_video_block;

		if ( $sensei_template_has_lesson_video_block ) {
			return '';
		}

		global $wp_embed;
		$original_content_width   = $GLOBALS['content_width'] ?? null;
		$GLOBALS['content_width'] = 1200;

		$content = $wp_embed->autoembed( $content );

		$GLOBALS['content_width'] = $original_content_width;

		return $content;
	}
}

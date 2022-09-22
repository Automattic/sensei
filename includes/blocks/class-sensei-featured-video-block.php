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
		global $has_rendered_lesson_video;

		if ( $has_rendered_lesson_video ) {
			return '';
		}
		return $content;
	}
}

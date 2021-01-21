<?php
/**
 * File containing the Sensei_Lesson_Actions_Block class.
 *
 * @package sensei
 * @since 3.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Lesson_Actions_Block is responsible for rendering the 'Lesson Actions' block.
 */
class Sensei_Lesson_Actions_Block {
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/lesson-actions',
			[
				'render_callback' => [ $this, 'render' ],
			],
			Sensei()->assets->src_path( 'blocks/lesson-actions/lesson-actions-block' )
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array  $attributes The block attributes.
	 * @param string $content    The block content.
	 *
	 * @return string The block HTML.
	 */
	public function render( array $attributes, string $content ) : string {
			$lesson = get_post();

			if ( empty( $lesson ) ) {
				return '';
			}

			$urls = sensei_get_prev_next_lessons( $lesson->ID );

			if ( empty( $urls['next']['url'] ) ) {
				return '';
			}

			return '<a href="' . esc_url( $urls['next']['url'] ) . '" > ' . $content . '</a>';
		}
}

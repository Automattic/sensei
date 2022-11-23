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
 * Class Sensei_Next_Lesson_Block is responsible for rendering the 'Next Lesson' block.
 */
class Sensei_Next_Lesson_Block {

	/**
	 * Sensei_Next_Lesson_Block constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/button-next-lesson',
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array  $attributes The block attributes.
	 * @param string $content    The block content.
	 *
	 * @access private
	 *
	 * @return string The block HTML.
	 */
	public function render( array $attributes, string $content ) : string {
		$lesson = get_post();

		if ( empty( $lesson ) || ! Sensei_Utils::user_completed_lesson( $lesson->ID ) ) {
			return '';
		}

		$urls = sensei_get_prev_next_lessons( $lesson->ID );

		if ( empty( $urls['next']['url'] ) ) {
			return '';
		}

		if ( ! empty( $attributes['className'] ) && false !== strpos( $attributes['className'], 'is-style-link' ) ) {
			return preg_replace(
				'/<a /',
				'<a href="' . esc_url( $urls['next']['url'] ) . '" ',
				$content,
				1
			);
		}

		return '<a href="' . esc_url( $urls['next']['url'] ) . '" >' . $content . '</a>';
	}
}

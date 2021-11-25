<?php
/**
 * File containing the Sensei_View_Quiz_Block class.
 *
 * @package sensei
 * @since 3.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_View_Quiz_Block is responsible for rendering the 'View Quiz' block.
 */
class Sensei_View_Quiz_Block {

	/**
	 * Sensei_View_Quiz_Block constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/button-view-quiz',
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
		$lesson_id = get_the_ID();

		if ( empty( $lesson_id ) ) {
			return '';
		}

		$quiz_permalink = Sensei()->lesson->get_quiz_permalink( $lesson_id );

		if ( ! $quiz_permalink || ! Sensei()->access_settings() ) {
			return '';
		}

		if ( ! empty( $attributes['className'] ) && false !== strpos( $attributes['className'], 'is-style-link' ) ) {
			return preg_replace(
				'/<a /',
				'<a href="' . esc_url( $quiz_permalink ) . '" ',
				$content,
				1
			);
		}

		return '<a href="' . esc_url( $quiz_permalink ) . '" >' . $content . '</a>';
	}
}

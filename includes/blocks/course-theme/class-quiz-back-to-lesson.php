<?php
/**
 * File containing the Quiz_Back_To_Lesson class.
 *
 * @package sensei
 * @since 3.13.4
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;

/**
 * Class Quiz_Back_To_Lesson the back to lesson block in the quiz.
 */
class Quiz_Back_To_Lesson {
	/**
	 * Quiz_Back_To_Lesson constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/quiz-back-to-lesson',
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array $attributes The block attributes.
	 *
	 * @access private
	 *
	 * @return string The block HTML.
	 */
	public function render( array $attributes = [] ) : string {
		if ( get_post_type() !== 'quiz' ) {
			return '';
		}

		$lesson_id  = Sensei()->quiz->get_lesson_id( get_the_ID() );
		$lesson_url = get_permalink( $lesson_id );

		if ( empty( $lesson_url ) ) {
			return '';
		}

		$text = $attributes['text'] ?? __( 'Back to lesson', 'sensei-lms' );

		return '<a href="' . esc_url( $lesson_url ) . '" class="sensei-lms-quiz-back-to-lesson">&lt; ' . esc_html( $text ) . '</a>';
	}
}

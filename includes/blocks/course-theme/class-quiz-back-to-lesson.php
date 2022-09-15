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
	 * Block JSON file.
	 */
	const BLOCK_JSON_FILE = '/quiz-blocks/quiz-back-to-lesson.block.json';

	/**
	 * Quiz_Back_To_Lesson constructor.
	 */
	public function __construct() {
		$block_json_path = Sensei()->assets->src_path( 'course-theme/blocks' ) . self::BLOCK_JSON_FILE;
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/quiz-back-to-lesson',
			[
				'render_callback' => [ $this, 'render' ],
				'style'           => 'sensei-theme-blocks',
			],
			$block_json_path
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

		$wrapper_attr = get_block_wrapper_attributes(
			[
				'class' => 'sensei-lms-quiz-back-to-lesson',
			]
		);
		return sprintf(
			'<a href="%s" %s>&lt; %s</a>',
			esc_url( $lesson_url ),
			$wrapper_attr,
			esc_html( $text )
		);
	}
}

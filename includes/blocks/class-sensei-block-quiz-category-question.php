<?php
/**
 * File containing the Sensei_Block_Quiz_Category_Question class.
 *
 * @package sensei
 * @since 3.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Block_Quiz_Category_Question is responsible for handling the 'Category Question' block.
 */
class Sensei_Block_Quiz_Category_Question {

	/**
	 * Sensei_Block_Quiz_Category_Question constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/quiz-category-question',
			[
				'render_callback' => [ $this, 'render_quiz_category_question' ],
			],
			Sensei()->assets->src_path( 'blocks/quiz/category-question-block' )
		);
	}

	/**
	 * Renders the block as an empty string.
	 *
	 * @param array  $attributes The block attributes.
	 * @param string $content    The block content.
	 *
	 * @return string The block HTML.
	 */
	public function render_quiz_category_question( array $attributes, string $content ) : string {
		return '';
	}
}

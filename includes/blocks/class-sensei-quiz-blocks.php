<?php
/**
 * File containing the class Sensei_Quiz_Blocks.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Quiz_Blocks
 */
class Sensei_Quiz_Blocks extends Sensei_Blocks_Initializer {

	/**
	 * Sensei_Quiz_Blocks constructor.
	 */
	public function __construct() {
		parent::__construct( [ 'lesson', 'question' ] );
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_editor_assets() {
		if ( ! Sensei()->quiz->is_block_based_editor_enabled() ) {
			return;
		}

		Sensei()->assets->enqueue( 'sensei-quiz-blocks', 'blocks/quiz/index.js', [], true );
		Sensei()->assets->enqueue( 'sensei-quiz-blocks-editor', 'blocks/quiz/quiz.editor.css', [ 'sensei-shared-blocks-editor-style', 'sensei-editor-components-style' ] );

		wp_localize_script( 'sensei-quiz-blocks', 'sensei_quiz_blocks', [ 'category_question_enabled' => Sensei()->feature_flags->is_enabled( 'block_editor_enable_category_questions' ) ] );

		global $post;
		if ( null !== $post ) {
			Sensei()->assets->preload_data( [ sprintf( '/sensei-internal/v1/lesson-quiz/%d?context=edit', $post->ID ) ] );
		}
	}

	/**
	 * Enqueue frontend and editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_assets() {
	}

	/**
	 * Initializes quiz blocks.
	 */
	public function initialize_blocks() {
		if ( ! Sensei()->quiz->is_block_based_editor_enabled() ) {
			return;
		}

		new Sensei_Block_Quiz();
		new Sensei_Block_Quiz_Question();
		new Sensei_Block_Quiz_Category_Question();

		$post_type_object = get_post_type_object( 'question' );

		$post_type_object->template      = [
			[ 'sensei-lms/quiz-question' ],
		];
		$post_type_object->template_lock = 'insert';
	}



}

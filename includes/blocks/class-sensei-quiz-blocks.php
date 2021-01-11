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
class Sensei_Quiz_Blocks {

	/**
	 * Sensei_Quiz_Blocks constructor.
	 */
	public function __construct() {

		add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );

	}

	/**
	 * Enqueue frontend and editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_assets() {
//		if ( 'quiz' !== get_post_type() ) {
//			return;
//		}

		//Sensei()->assets->enqueue( 'sensei-quiz', 'blocks/quiz/quiz-block.css' );

//		if ( ! is_admin() ) {
//			Sensei()->assets->enqueue( 'sensei-quiz-frontend', 'blocks/quiz/frontend.js' );
//		}
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_editor_assets() {
//		if ( 'quiz' !== get_post_type() ) {
//			return;
//		}

		Sensei()->assets->enqueue( 'sensei-quiz-block', 'blocks/quiz/index.js', [], true );
		Sensei()->assets->enqueue( 'sensei-quiz-editor', 'blocks/quiz/quiz.editor.css' );
	}


}

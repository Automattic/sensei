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
		parent::__construct( [ 'lesson', 'quiz' ] );
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_editor_assets() {
		Sensei()->assets->enqueue( 'sensei-quiz-blocks', 'blocks/quiz/index.js', [], true );
		Sensei()->assets->enqueue( 'sensei-quiz-blocks-editor', 'blocks/quiz/quiz.editor.css' );
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
		new Sensei_Block_Quiz();
		new Sensei_Block_Quiz_Question();
	}

}

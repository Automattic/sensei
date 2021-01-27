<?php
/**
 * File containing the class Sensei_Lesson_Blocks.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Lesson_Blocks
 */
class Sensei_Lesson_Blocks extends Sensei_Blocks_Initializer {
	/**
	 * Sensei_Blocks constructor.
	 */
	public function __construct() {
		parent::__construct( [ 'lesson' ] );
	}

	/**
	 * Enqueue frontend and editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_assets() {
		Sensei()->assets->enqueue( 'sensei-single-lesson', 'blocks/single-lesson.css' );
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_editor_assets() {
		Sensei()->assets->enqueue( 'sensei-single-lesson-blocks', 'blocks/sensei-single-lesson-blocks.js', [], true );
		Sensei()->assets->enqueue( 'sensei-single-lesson-editor', 'blocks/single-lesson.editor.css' );
		Sensei()->assets->enqueue( 'sensei-editor-components', 'blocks/editor-components/style.css' );
	}

	/**
	 * Initializes the blocks.
	 */
	public function initialize_blocks() {
		new Sensei_Lesson_Actions_Block();
		new Sensei_Next_Lesson_Block();
	}
}

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
		parent::__construct( 'lesson' );

		add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
	}

	/**
	 * Enqueue frontend and editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_assets() {
		if ( 'lesson' !== get_post_type() ) {
			return;
		}

		Sensei()->assets->enqueue( 'sensei-single-lesson-blocks-style', 'blocks/single-lesson-style.css' );
		Sensei()->assets->enqueue_style( 'sensei-shared-blocks-style' );

		if ( ! is_admin() ) {
			Sensei()->assets->enqueue_script( 'sensei-blocks-frontend' );
		}
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_editor_assets() {
		if ( 'lesson' !== get_post_type() ) {
			return;
		}

		Sensei()->assets->enqueue( 'sensei-single-lesson-blocks', 'blocks/single-lesson.js', [], true );
		Sensei()->assets->enqueue( 'sensei-single-lesson-blocks-editor-style', 'blocks/single-lesson-style.editor.css' );

		Sensei()->assets->enqueue_script( 'sensei-shared-blocks' );
		Sensei()->assets->enqueue_style( 'sensei-shared-blocks-editor-style' );
		Sensei()->assets->enqueue_style( 'sensei-editor-components-style' );
	}

	/**
	 * Initializes the blocks.
	 */
	public function initialize_blocks() {
		new Sensei_Lesson_Actions_Block();
		new Sensei_Next_Lesson_Block();
		new Sensei_Block_Contact_Teacher();
	}
}

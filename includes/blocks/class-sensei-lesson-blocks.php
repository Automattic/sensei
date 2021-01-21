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
class Sensei_Lesson_Blocks {
	/**
	 * Sensei_Blocks constructor.
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
		if ( 'lesson' !== get_post_type() ) {
			return;
		}

		Sensei()->assets->enqueue( 'sensei-single-lesson', 'blocks/single-lesson.css' );
		Sensei()->assets->enqueue( 'sensei-shared-blocks', 'blocks/shared-blocks.css' );
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

		Sensei()->assets->enqueue( 'sensei-single-lesson-blocks', 'blocks/sensei-single-lesson-blocks.js', [], true );
		Sensei()->assets->enqueue( 'sensei-single-lesson-editor', 'blocks/single-lesson.editor.css' );
		Sensei()->assets->enqueue( 'sensei-shared-blocks-editor', 'blocks/shared-blocks.editor.css' );
		Sensei()->assets->enqueue( 'sensei-editor-components', 'blocks/editor-components/style.css' );
	}
}

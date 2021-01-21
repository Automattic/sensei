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
		add_action( 'template_redirect', [ $this, 'maybe_initialize_blocks' ] );
		add_action( 'current_screen', [ $this, 'maybe_initialize_blocks' ] );
	}

	/**
	 * Check if course blocks should be initialized and do initialization.
	 *
	 * @access private
	 */
	public function maybe_initialize_blocks() {
		if ( is_admin() ) {
			$screen = get_current_screen();

			if ( ! $screen->is_block_editor || 'lesson' !== $screen->post_type ) {
				return;
			}
		} elseif ( 'lesson' !== get_post_type() ) {
			return;
		}

		$this->initialize_blocks();
	}

	/**
	 * Initialize blocks that are used in course pages.
	 */
	public function initialize_blocks() {
		$this->contact_teacher = new Sensei_Block_Contact_Teacher();
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
		Sensei()->assets->enqueue( 'sensei-shared-blocks-style', 'blocks/shared-blocks-style.css' );

		if ( ! is_admin() ) {
			Sensei()->assets->enqueue( 'sensei-shared-blocks-frontend', 'blocks/shared-blocks-frontend.js' );
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

		Sensei()->assets->enqueue( 'sensei-single-lesson-blocks', 'blocks/sensei-single-lesson-blocks.js', [], true );
		Sensei()->assets->enqueue( 'sensei-single-lesson-editor', 'blocks/single-lesson.editor.css' );

		Sensei()->assets->enqueue( 'sensei-shared-blocks', 'blocks/shared-blocks.js', [], true );
		Sensei()->assets->enqueue( 'sensei-shared-blocks-editor-style', 'blocks/shared-blocks-style.editor.css' );

		Sensei()->assets->enqueue( 'sensei-editor-components', 'blocks/editor-components/style.css' );
	}
}

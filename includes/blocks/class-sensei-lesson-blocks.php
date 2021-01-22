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
		add_filter( 'sensei_use_sensei_template', [ $this, 'use_single_lesson_template' ] );
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
		Sensei()->assets->enqueue( 'sensei-editor-components', 'blocks/editor-components/style.css' );
	}

	/**
	 * Initializes the blocks.
	 */
	public function initialize_blocks() {
		new Sensei_Lesson_Actions_Block();
		new Sensei_Next_Lesson_Block();
	}

	/**
	 * Disable single lesson template if lesson is block based.
	 *
	 * @access private
	 *
	 * @param bool $enabled
	 *
	 * @return bool
	 */
	public function use_single_lesson_template( bool $enabled ) : bool {
		if ( is_single() && 'lesson' === get_post_type() && ! Sensei()->lesson->is_legacy_lesson( get_post() ) ) {
			add_filter( 'the_content', [ $this, 'hide_lesson_content' ] );
			return false;
		}

		return $enabled;
	}

	/**
	 * Hides the post content when the user can't access the lesson.
	 *
	 * @param string $content The post content.
	 *
	 * @access private
	 *
	 * @return string The modified content.
	 */
	public function hide_lesson_content( string $content ) : string {
		if ( in_the_loop() && is_main_query() && ! sensei_can_user_view_lesson() ) {
			return '';
		}

		return $content;
	}
}

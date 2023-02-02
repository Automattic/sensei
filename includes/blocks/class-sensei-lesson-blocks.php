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

		add_action( 'template_redirect', [ $this, 'remove_block_related_content' ] );
	}

	/**
	 * Enqueue frontend and editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_assets() {

		Sensei()->assets->enqueue( 'sensei-shared-blocks-style', 'blocks/shared-style.css' );

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

		Sensei()->assets->enqueue(
			'sensei-single-lesson-blocks',
			'blocks/single-lesson.js',
			[ 'sensei-shared-blocks' ],
			true
		);

		$course_id         = Sensei_Utils::get_current_course();
		$has_learning_mode = ! empty( $course_id ) && Sensei_Course_Theme_Option::has_learning_mode_enabled( $course_id );

		wp_add_inline_script(
			'sensei-single-lesson-blocks',
			'window.sensei = window.sensei || {}; ' .
			sprintf( 'window.sensei.courseThemeEnabled = %s;', $has_learning_mode ? 'true' : 'false' ) .
			sprintf( 'window.sensei.assetUrl = "%s";', Sensei()->assets->asset_url( '' ) ),
			'before'
		);

		Sensei()->assets->enqueue(
			'sensei-single-lesson-blocks-editor-style',
			'blocks/single-lesson-style-editor.css',
			[ 'sensei-shared-blocks-editor-style', 'sensei-editor-components-style' ]
		);

	}

	/**
	 * Initializes the blocks.
	 */
	public function initialize_blocks() {

		$post_type_object = get_post_type_object( 'lesson' );

		// We are using the default lesson pattern for the template. It includes some lesson actions that won't be shown if Learning Mode is enabled.
		$block_template = [
			[
				'core/pattern',
				[
					'slug' => 'sensei-lms/default',
				],
			],
		];

		/**
		 * Customize the lesson block template.
		 *
		 * @hook  sensei_lesson_block_template
		 * @since 3.9.0
		 *
		 * @param {string[][]} $template          Array of blocks to use as the default initial state for a lesson.
		 * @param {string[][]} $original_template Original block template.
		 *
		 * @return {string[][]} Array of blocks to use as the default initial state for a lesson.
		 */
		$post_type_object->template = apply_filters( 'sensei_lesson_block_template', $block_template, $post_type_object->template ?? [] );

		new Sensei_Conditional_Content_Block();
		new Sensei_Lesson_Actions_Block();
		new Sensei_Lesson_Properties_Block();
		new Sensei_Next_Lesson_Block();
		new Sensei_Complete_Lesson_Block();
		new Sensei_Reset_Lesson_Block();
		new Sensei_View_Quiz_Block();
		new Sensei_Featured_Video_Block();
		new Sensei_Block_Contact_Teacher();
	}

	/**
	 * Remove functionality which is provided by blocks.
	 *
	 * @access private
	 */
	public function remove_block_related_content() {

		if ( has_block( 'sensei-lms/lesson-actions' ) ) {
			remove_action( 'sensei_single_lesson_content_inside_after', [ 'Sensei_Lesson', 'footer_quiz_call_to_action' ] );
		}

		if ( Sensei()->lesson->has_sensei_blocks() ) {
			remove_action( 'sensei_single_lesson_content_inside_before', [ Sensei()->post_types->messages, 'send_message_link' ], 30 );
		}

	}
}

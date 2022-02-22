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
		add_action( 'init', [ $this, 'register_lesson_post_metas' ] );
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

		$course_id = Sensei_Utils::get_current_course();
		if ( ! empty( $course_id ) ) {
			wp_add_inline_script(
				'sensei-single-lesson-blocks',
				sprintf(
					'window.sensei = window.sensei || {}; window.sensei.courseThemeEnabled = %s;',
					Sensei_Course_Theme_Option::has_learning_mode_enabled( $course_id ) ? 'true' : 'false'
				),
				'before'
			);
		}

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

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$lesson_id = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : 0; // WP query is not ready yet.
		$course_id = Sensei()->lesson->get_course_id( $lesson_id );

		// Notice that for new Lessons, the `lesson_id` will return `0` (post query string not set).
		// It means the following check will return `false`. It's expected and works well because
		// new lessons don't have associated courses yet.
		$sensei_theme_enabled = Sensei_Course_Theme_Option::has_learning_mode_enabled( $course_id );

		if ( $sensei_theme_enabled ) {
			$block_template = [
				[ 'sensei-lms/lesson-properties' ],
				[
					'core/paragraph',
					[ 'placeholder' => __( 'Write lesson content...', 'sensei-lms' ) ],
				],
			];
		} else {
			$block_template = [
				[ 'sensei-lms/lesson-properties' ],
				[ 'sensei-lms/button-contact-teacher' ],
				[
					'core/paragraph',
					[ 'placeholder' => __( 'Write lesson content...', 'sensei-lms' ) ],
				],
				[ 'sensei-lms/lesson-actions' ],
			];
		}

		if ( Sensei()->quiz->is_block_based_editor_enabled() ) {
			$block_template[] = [ 'sensei-lms/quiz', [ 'isPostTemplate' => true ] ];
		}

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

		if ( ! Sensei()->lesson->has_sensei_blocks() ) {
			return;
		}

		new Sensei_Lesson_Actions_Block();
		new Sensei_Lesson_Properties_Block();
		new Sensei_Next_Lesson_Block();
		new Sensei_Complete_Lesson_Block();
		new Sensei_Reset_Lesson_Block();
		new Sensei_View_Quiz_Block();
		new Sensei_Block_Contact_Teacher();

		$this->remove_block_related_content();

	}

	/**
	 * Helper method to remove functionality which is provided by blocks.
	 */
	private function remove_block_related_content() {
		// Remove contact teacher button.
		remove_action( 'sensei_single_lesson_content_inside_before', [ Sensei()->post_types->messages, 'send_message_link' ], 30 );

		// Remove footer buttons.
		remove_action( 'sensei_single_lesson_content_inside_after', [ 'Sensei_Lesson', 'footer_quiz_call_to_action' ] );
	}


	/**
	 * Register lesson post metas.
	 *
	 * @access private
	 */
	public function register_lesson_post_metas() {
		register_post_meta(
			'lesson',
			'_needs_template',
			[
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'boolean',
				'auth_callback' => function( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			]
		);
	}
}

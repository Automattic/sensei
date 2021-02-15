<?php
/**
 * File containing the Sensei_Complete_Lesson_Block class.
 *
 * @package sensei
 * @since 3.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Reset_Lesson_Block is responsible for rendering the 'Reset Lesson' block.
 */
class Sensei_Reset_Lesson_Block {

	/**
	 * Sensei_Complete_Lesson_Block constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/button-reset-lesson',
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array  $attributes The block attributes.
	 * @param string $content    The block content.
	 *
	 * @access private
	 *
	 * @return string The block HTML.
	 */
	public function render( array $attributes, string $content ) : string {
		$lesson = get_post();

		if ( empty( $lesson ) ) {
			return '';
		}

		$course_id = Sensei()->lesson->get_course_id( $lesson->ID );

		if ( ! Sensei_Course::is_user_enrolled( $course_id ) || ! Sensei_Utils::user_completed_lesson( $lesson->ID ) ) {
			return '';
		}

		$quiz_id = Sensei()->lesson->lesson_quizzes( $lesson->ID );

		if ( $quiz_id && Sensei()->lesson::lesson_quiz_has_questions( $lesson->ID ) && empty( get_post_meta( $quiz_id, '_enable_quiz_reset', true ) ) ) {
			return '';
		}

		return $this->render_with_form( $attributes, $content );
	}

	/**
	 * Helper method to wrap the button content into a form.
	 *
	 * @param array  $attributes The block attributes.
	 * @param string $content The block content.
	 *
	 * @return string The HTML to render.
	 */
	private function render_with_form( array $attributes, string $content ) : string {
		wp_enqueue_script( 'sensei-stop-double-submission' );
		$nonce     = wp_nonce_field( 'woothemes_sensei_complete_lesson_noonce', 'woothemes_sensei_complete_lesson_noonce', false, false );
		$permalink = esc_url( get_permalink() );

		if ( ! empty( $attributes['className'] ) && false !== strpos( $attributes['className'], 'is-style-link' ) ) {

			$content = preg_replace(
				'/<a /',
				'<a href="javascript:void(0)" onclick="this.closest(\'form\').submit()"',
				$content,
				1
			);
		}

		return '
		<form class="lesson_button_form" method="POST" action="' . $permalink . '">
			' . $nonce . '
			<input type="hidden" name="quiz_action" value="lesson-reset" />
			' . $content . '
		</form>';
	}
}

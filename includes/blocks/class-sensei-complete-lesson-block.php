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
 * Class Sensei_Complete_Lesson_Block is responsible for rendering the 'Complete Lesson' block.
 */
class Sensei_Complete_Lesson_Block {

	/**
	 * Sensei_Complete_Lesson_Block constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/button-complete-lesson',
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

		if ( ! Sensei_Course::is_user_enrolled( $course_id ) || Sensei_Utils::user_completed_lesson( $lesson->ID ) ) {
			return '';
		}

		if ( false === Sensei()->lesson->lesson_has_quiz_with_questions_and_pass_required( $lesson->ID ) ) {

			return $this->render_with_form( $attributes, $content );
		}

		return '';
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
			wp_enqueue_script( 'sensei-link-form-submit' );

			$content = preg_replace(
				'/<a /',
				'<a href="javascript:void(0)" onclick="this.closest(\'form\').submit()"',
				$content,
				1
			);
		}

		$content = preg_replace(
			'/<(button|a)/',
			'<$1 data-id="complete-lesson-button"',
			$content,
			1
		);

		return '
		<form class="lesson_button_form" data-id="complete-lesson-form" method="POST" action="' . $permalink . '">
			' . $nonce . '
			<input type="hidden" name="quiz_action" value="lesson-complete" />
			' . $content . '
		</form>';
	}
}

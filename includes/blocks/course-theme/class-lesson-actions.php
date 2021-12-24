<?php
/**
 * File containing the Lesson_Actions class.
 *
 * @package sensei
 * @since
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;
use \Sensei_Course;
use \Sensei_Lesson;
use \Sensei_Utils;

/**
 * Class Lesson_Actions is responsible for rendering the Lesson actions block.
 */
class Lesson_Actions {
	/**
	 * Lesson_Actions constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-theme-lesson-actions',
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Renders complete lesson button.
	 *
	 * @param string $button_class Button class.
	 * @param bool   $is_disabled  Whether it is disabled.
	 *
	 * @return string The complete lesson button.
	 */
	private function render_complete_lesson( string $button_class, bool $is_disabled ) : string {
		$disabled_attribute = $is_disabled ? 'disabled' : '';

		$nonce     = wp_nonce_field( 'woothemes_sensei_complete_lesson_noonce', 'woothemes_sensei_complete_lesson_noonce', false, false );
		$lesson_id = Sensei_Utils::get_current_lesson();
		$permalink = esc_url( get_permalink( $lesson_id ) );
		$text      = esc_html( __( 'Complete lesson', 'sensei-lms' ) );

		return ( '
			<form data-id="complete-lesson-form" class="sensei-course-theme-lesson-actions__complete-lesson-form" method="POST" action="' . $permalink . '">
				' . $nonce . '
				<input type="hidden" name="quiz_action" value="lesson-complete" />
				<button type="submit" data-id="complete-lesson-button" class="sensei-course-theme__button ' . $button_class . '" ' . $disabled_attribute . '>
					' . $text . '
				</button>
			</form>
		' );
	}

	/**
	 * Renders take quiz button.
	 *
	 * @param string $quiz_permalink Quiz permalink.
	 * @param bool   $is_disabled    Whether it is disabled.
	 *
	 * @return string The take quiz button.
	 */
	private function render_take_quiz( string $quiz_permalink, bool $is_disabled ) : string {
		$disabled       = $is_disabled ? 'aria-disabled="true"' : '';
		$quiz_permalink = esc_url( $quiz_permalink );
		$text           = esc_html__( 'Take quiz', 'sensei-lms' );

		return '<a href="' . $quiz_permalink . '" class="sensei-course-theme__button is-primary" ' . $disabled . '>'
			. $text .
		'</a>';
	}

	/**
	 * Renders the block.
	 *
	 * @param array $attributes The block attributes.
	 *
	 * @access private
	 *
	 * @return string The block HTML.
	 */
	public function render( array $attributes = [] ) : string {
		$lesson_id = Sensei_Utils::get_current_lesson();
		$user_id   = get_current_user_id();

		if ( empty( $lesson_id ) || empty( $user_id ) ) {
			return '';
		}

		$course_id = Sensei()->lesson->get_course_id( $lesson_id );

		if (
			! Sensei_Course::is_user_enrolled( $course_id )
			|| Sensei_Utils::user_completed_lesson( $lesson_id )
		) {
			return '';
		}

		$has_incomplete_prerequisite = ! Sensei_Lesson::is_prerequisite_complete( $lesson_id, $user_id );
		$quiz_permalink              = Sensei()->lesson->get_quiz_permalink( $lesson_id );
		$is_quiz_submitted           = Sensei()->lesson->is_quiz_submitted( $lesson_id, $user_id );
		$is_pass_required            = Sensei()->lesson->lesson_has_quiz_with_questions_and_pass_required( $lesson_id );
		$actions                     = [];

		// Quiz button.
		if ( ! empty( $quiz_permalink ) && ! Sensei()->lesson->is_quiz_submitted( $lesson_id, $user_id ) ) {
			$take_quiz_button = $this->render_take_quiz( $quiz_permalink, $has_incomplete_prerequisite );
			$actions[]        = $take_quiz_button;
		}

		// Complete button.
		if ( ! $is_pass_required ) {
			$complete_button_class  = isset( $take_quiz_button ) ? 'is-secondary' : 'is-primary';
			$complete_lesson_button = $this->render_complete_lesson( $complete_button_class, $has_incomplete_prerequisite );
			$actions[]              = $complete_lesson_button;
		}

		if ( empty( $actions ) ) {
			return '';
		}

		return '<div class="sensei-course-theme-lesson-actions">
			' . implode( '', $actions ) . '
		</div>';
	}
}

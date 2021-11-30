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
	 * @param int  $lesson_id                   Lesson ID.
	 * @param bool $has_incomplete_prerequisite Whether it has incomplete prerequisite.
	 *
	 * @return string The complete lesson button.
	 */
	private function render_complete_lesson( $lesson_id, $has_incomplete_prerequisite ) {
		if (
			// Return empty if the lesson requires a quiz pass.
			Sensei()->lesson->lesson_has_quiz_with_questions_and_pass_required( $lesson_id ) ||

			// Return empty if user already completed the lesson.
			Sensei_Utils::user_completed_lesson( $lesson_id )
		) {
			return '';
		}

		// The button is disabled if the lesson requires a pre-requisite.
		$disabled = $has_incomplete_prerequisite ? 'disabled' : '';

		// The button is a secondary CTA if there is a quiz but not required to take/pass it.
		$button_style = 'is-primary';
		if ( Sensei_Lesson::lesson_quiz_has_questions( $lesson_id ) ) {
			$button_style = 'is-secondary';
		}

		// Render "Mark Complete" button.
		$nonce     = wp_nonce_field( 'woothemes_sensei_complete_lesson_noonce', 'woothemes_sensei_complete_lesson_noonce', false, false );
		$permalink = esc_url( get_permalink() );
		$text      = esc_html( __( 'Complete lesson', 'sensei-lms' ) );

		return ( '
			<form class="sensei-course-theme-lesson-actions__complete-lesson-form" method="POST" action="' . $permalink . '">
				' . $nonce . '
				<input type="hidden" name="quiz_action" value="lesson-complete" />
				<button type="submit" class="sensei-course-theme__button ' . $button_style . '" ' . $disabled . '>
					' . $text . '
				</button>
			</form>
		' );
	}

	/**
	 * Renders take quiz button.
	 *
	 * @param int  $lesson_id                   Lesson ID.
	 * @param int  $user_id                     User ID.
	 * @param bool $has_incomplete_prerequisite Whether it has incomplete prerequisite.
	 *
	 * @return string The take quiz button.
	 */
	private function render_take_quiz( $lesson_id, $user_id, $has_incomplete_prerequisite ) {
		$quiz_permalink = Sensei()->lesson->get_quiz_permalink( $lesson_id );

		if ( empty( $quiz_permalink ) ) {
			return '';
		}

		if ( Sensei()->lesson->is_quiz_submitted( $lesson_id, $user_id ) ) {
			return '';
		}

		$disabled       = $has_incomplete_prerequisite ? 'aria-disabled="true"' : '';
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
		$lesson_id = get_the_ID();
		$user_id   = get_current_user_id();

		if ( empty( $lesson_id ) || empty( $user_id ) ) {
			return '';
		}

		$course_id = Sensei()->lesson->get_course_id( $lesson_id );

		if ( ! Sensei_Course::is_user_enrolled( $course_id ) ) {
			return '';
		}

		$has_incomplete_prerequisite = ! Sensei_Lesson::is_prerequisite_complete( $lesson_id, $user_id );

		return '<ul class="sensei-course-theme-lesson-actions">
			<li>' . $this->render_complete_lesson( $lesson_id, $has_incomplete_prerequisite ) . '</li>
			<li>' . $this->render_take_quiz( $lesson_id, $user_id, $has_incomplete_prerequisite ) . '</li>
		</ul>';
	}
}

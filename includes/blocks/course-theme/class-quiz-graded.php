<?php
/**
 * File containing the Quiz_Graded class.
 *
 * @package sensei
 * @since
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;
use \Sensei_Quiz;
use \Sensei_Utils;

/**
 * Renders the Quiz grade results block.
 */
class Quiz_Graded {

	/**
	 * Quiz_Graded constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-theme-quiz-graded',
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @access private
	 *
	 * @return string The block HTML.
	 */
	public function render(): string {
		// If not a quiz page then bail.
		if ( 'quiz' !== get_post_type() ) {
			return '';
		}

		$lesson_id   = Sensei_Utils::get_current_lesson();
		$user_id     = get_current_user_id();
		$quiz_status = Sensei_Utils::user_lesson_status( $lesson_id, $user_id )->comment_approved;

		// If not graded then bail.
		if ( ! in_array( $quiz_status, [ 'graded', 'passed', 'failed' ], true ) ) {
			return '';
		}

		$grade         = Sensei_Quiz::get_user_quiz_grade( $lesson_id, $user_id );
		$reset_allowed = Sensei_Quiz::is_reset_allowed( $lesson_id );
		$title         = sprintf(
			// translators: The placeholder is the quiz grade.
			__( 'Your Grade: %1$s%%', 'sensei-lms' ),
			$grade
		);

		$message = __( "You've passed the quiz and can continue to the next lesson.", 'sensei-lms' );
		if ( 'failed' === $quiz_status ) {
			$quiz_id  = \Sensei()->lesson->lesson_quizzes( $lesson_id );
			$passmark = absint( get_post_meta( $quiz_id, '_quiz_passmark', true ), 2 );
			$message  = sprintf(
				// translators: The first placeholder is the minimum grade required, and the second placeholder is the actual grade.
				__( 'You require %1$s%% to pass this quiz. Your grade is %2$s%%.', 'sensei-lms' ),
				$passmark,
				$grade
			);
		}

		$is_lesson_completed = Sensei_Utils::user_completed_lesson( $lesson_id, $user_id );
		$complete_lesson     = ( 'failed' !== $quiz_status && ! $is_lesson_completed ) ? self::render_complete_lesson() : '';
		$reset_quiz          = $reset_allowed ? self::render_reset_quiz() : '';
		$contact_teacher     = self::render_contact_teacher();

		return ( "
			<div class='sensei-course-theme-quiz-graded__container'>
				<h1 class='sensei-course-theme-quiz-graded__title'>{$title}</h1>
				<p class='sensei-course-theme-quiz-graded__message'>{$message}</p>
				<div class='sensei-course-theme-quiz-graded__actions'>
					{$complete_lesson}
					{$reset_quiz}
					{$contact_teacher}
				</div>
			</div>
		" );
	}

	/**
	 * Renders the complete lesson button.
	 */
	public static function render_complete_lesson() {
		$nonce     = wp_nonce_field( 'woothemes_sensei_complete_lesson_noonce', 'woothemes_sensei_complete_lesson_noonce', false, false );
		$permalink = esc_url( get_permalink( Sensei_Utils::get_current_lesson() ) );
		$text      = esc_html( __( 'Complete lesson', 'sensei-lms' ) );

		return ( "
			<form class='sensei-course-theme-quiz-graded__complete-lesson-form' method='POST' action='{$permalink}'>
				{$nonce}
				<input type='hidden' name='quiz_action' value='lesson-complete' />
				<button type='submit' class='sensei-course-theme__button is-primary'>
					{$text}
				</button>
			</form>
		" );
	}

	/**
	 * Renders the reset quiz button.
	 */
	public static function render_reset_quiz() {
		$nonce     = wp_nonce_field( 'woothemes_sensei_reset_quiz_nonce', 'woothemes_sensei_reset_quiz_nonce', false, false );
		$permalink = esc_url( get_permalink() );
		$text      = __( 'Restart Quiz', 'sensei-lms' );

		return ( "
			<form class='sensei-course-theme-quiz-graded__reset-quiz-form' method='POST' action='{$permalink}'>
				{$nonce}
				<input type='hidden' name='quiz_reset' value='true' />
				<button type='submit' class='sensei-course-theme-quiz-graded__reset-quiz-button'>
					{$text}
				</button>
			</form>
		" );
	}

	/**
	 * Renders the contact teacher button.
	 */
	public static function render_contact_teacher() {
		$link  = '<a href="#" class="sensei-course-theme-quiz-graded__contact-teacher-link">' . __( 'Contact teacher', 'sensei-lms' ) . '</a>';
		$block = new \Sensei_Block_Contact_Teacher();
		return $block->render_contact_teacher_block( null, $link );
	}
}

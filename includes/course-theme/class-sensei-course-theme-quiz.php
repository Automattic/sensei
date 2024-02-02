<?php
/**
 * File containing Sensei_Course_Theme_Quiz class.
 *
 * @package sensei-lms
 * @since 3.15.0
 */

use Sensei\Internal\Emails\Email_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei_Course_Theme_Quiz class.
 */
class Sensei_Course_Theme_Quiz {

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_Course_Theme_Quiz constructor. Prevents other instances from being created outside of `self::instance()`.
	 */
	private function __construct() {}

	/**
	 * Fetches an instance of the class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initializes the class.
	 */
	public function init() {
		if ( 'quiz' !== get_post_type() ) {
			return;
		}

		$this->maybe_add_quiz_results_notice();
	}

	/**
	 * Renders the block.
	 *
	 * @access private
	 */
	private function maybe_add_quiz_results_notice() {
		$actions   = [];
		$lesson_id = Sensei_Utils::get_current_lesson();
		$quiz_id   = Sensei()->lesson->lesson_quizzes( $lesson_id );
		$user_id   = get_current_user_id();

		if ( empty( $user_id ) ) {
			return;
		}

		$quiz_progress = Sensei()->quiz_progress_repository->get( $quiz_id, $user_id );

		// If quiz is not submitted, then nothing else to do.
		if ( ! Sensei()->lesson->is_quiz_submitted( $lesson_id, $user_id ) || empty( $quiz_progress ) ) {
			return;
		}

		// Prepare title.
		$grade         = Sensei_Quiz::get_user_quiz_grade( $lesson_id, $user_id );
		$grade_rounded = Sensei_Utils::round( $grade, 2 );
		$reset_allowed = Sensei_Quiz::is_reset_allowed( $lesson_id );
		$title         = sprintf(
			// translators: The placeholder is the quiz grade.
			__( 'Your Grade: %1$s%%', 'sensei-lms' ),
			$grade_rounded
		);
		if ( 'ungraded' === $quiz_progress->get_status() ) {
			$title = __( 'Awaiting grade', 'sensei-lms' );
		}

		// Prepare message.
		$text = __( "You've passed the quiz and can continue to the next lesson.", 'sensei-lms' );

		if ( 'ungraded' === $quiz_progress->get_status() ) {
			$email_enabled = false;
			$text          = __( 'Your answers have been submitted and the quiz will be graded soon.', 'sensei-lms' );
			$actions[]     = Sensei_Quiz::get_primary_button_html(
				__( 'Pending teacher grade', 'sensei-lms' ),
				null,
				[ 'sensei-course-theme-quiz-graded-notice__pending-grade' ]
			);

			// New quiz graded email.
			if ( Sensei()->feature_flags->is_enabled( 'email_customization' ) ) {
				$repository        = new Email_Repository();
				$quiz_graded_email = $repository->get( 'quiz_graded' );

				if ( $quiz_graded_email && 'publish' === $quiz_graded_email->post_status ) {
					$email_enabled = true;
				}
			} else { // Old quiz graded email.
				if ( isset( Sensei()->settings->settings['email_learners'] ) &&
					in_array( 'learner-graded-quiz', (array) Sensei()->settings->settings['email_learners'], true ) ) {
					$email_enabled = true;
				}
			}

			if ( $email_enabled ) {
				$text .= __( ' You\'ll receive an email once it\'s ready to view.', 'sensei-lms' );
			}
		} elseif ( 'failed' === $quiz_progress->get_status() ) {
			$passmark         = get_post_meta( $quiz_id, '_quiz_passmark', true );
			$passmark_rounded = Sensei_Utils::round( $passmark, 2 );
			$text             = sprintf(
				// translators: The first placeholder is the minimum grade required, and the second placeholder is the actual grade.
				__( 'You require %1$s%% to pass this quiz. Your grade is %2$s%%.', 'sensei-lms' ),
				$passmark_rounded,
				$grade_rounded
			);

			// Display Contact Teacher button.
			if ( ! $reset_allowed ) {
				$block     = new Sensei_Block_Contact_Teacher();
				$button    = Sensei_Quiz::get_primary_button_html( __( 'Contact teacher', 'sensei-lms' ) );
				$actions[] = $block->render_contact_teacher_block( [], $button );
			}
		}

		// "Continue to next lesson" button.
		if ( in_array( $quiz_progress->get_status(), array( 'graded', 'passed' ), true ) ) {
			$prev_next_urls  = sensei_get_prev_next_lessons( $lesson_id );
			$next_lesson_url = $prev_next_urls['next']['url'] ?? null;
			$actions[]       = Sensei_Quiz::get_primary_button_html( __( 'Continue to next lesson', 'sensei-lms' ), $next_lesson_url );
		}

		// "Restart Quiz" button.
		if ( $reset_allowed ) {
			$actions[] = self::render_reset_quiz();
		}

		$notices = \Sensei_Context_Notices::instance( 'course_theme_quiz_grade' );
		$notices->add_notice( 'course-theme-quiz-grade', $text, $title, $actions );
	}

	/**
	 * Renders the reset quiz button.
	 */
	private static function render_reset_quiz() {
		$nonce     = wp_nonce_field( 'woothemes_sensei_reset_quiz_nonce', 'woothemes_sensei_reset_quiz_nonce', false, false );
		$permalink = esc_url( get_permalink() );
		$text      = __( 'Restart Quiz', 'sensei-lms' );

		return ( "
			<form class='sensei-course-theme-quiz-graded-notice__reset-quiz-form' method='POST' action='{$permalink}'>
				{$nonce}
				<input type='hidden' name='quiz_reset' value='true' />
				<button type='submit' class='sensei-course-theme__button is-link'>
					{$text}
				</button>
			</form>
		" );
	}
}

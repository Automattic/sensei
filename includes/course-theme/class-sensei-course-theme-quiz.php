<?php
/**
 * File containing Sensei_Course_Theme_Quiz class.
 *
 * @package sensei-lms
 * @since 3.15.0
 */

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
		$lesson_id = Sensei_Utils::get_current_lesson();
		$quiz_id   = Sensei()->lesson->lesson_quizzes( $lesson_id );
		$user_id   = get_current_user_id();

		if ( empty( $user_id ) ) {
			return;
		}

		$lesson_status = Sensei_Utils::user_lesson_status( $lesson_id, $user_id );

		// If quiz is not submitted, then nothing else to do.
		if ( ! Sensei()->lesson->is_quiz_submitted( $lesson_id, $user_id ) || empty( $lesson_status ) ) {
			return;
		}

		// Prepare title.
		$grade         = Sensei_Quiz::get_user_quiz_grade( $lesson_id, $user_id );
		$grade_rounded = Sensei_Utils::round( $grade, 2 );
		$title         = sprintf(
			// translators: The placeholder is the quiz grade.
			__( 'Your Grade: %1$s%%', 'sensei-lms' ),
			$grade_rounded
		);
		if ( 'ungraded' === $lesson_status->comment_approved ) {
			$title = __( 'Awaiting grade', 'sensei-lms' );
		}

		// Prepare message.
		$text = __( "You've passed the quiz and can continue to the next lesson.", 'sensei-lms' );
		if ( 'ungraded' === $lesson_status->comment_approved ) {
			$text = __( 'Your answers have been submitted and the quiz will be graded soon. You\'ll receive an email once it\'s ready to view.', 'sensei-lms' );
		} elseif ( 'failed' === $lesson_status->comment_approved ) {
			$passmark         = get_post_meta( $quiz_id, '_quiz_passmark', true );
			$passmark_rounded = Sensei_Utils::round( $passmark, 2 );
			$text             = sprintf(
				// translators: The first placeholder is the minimum grade required, and the second placeholder is the actual grade.
				__( 'You require %1$s%% to pass this quiz. Your grade is %2$s%%.', 'sensei-lms' ),
				$passmark_rounded,
				$grade_rounded
			);
		}

		$actions[] = self::get_pending_grade_button_html();

		// Prepare Restart Quiz button.
		$reset_allowed = Sensei_Quiz::is_reset_allowed( $lesson_id );

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

	/**
	 * Generates the HTML markup for the "Pending teacher grade" button.
	 */
	private static function get_pending_grade_button_html() {
		return
			'<div class="wp-block-buttons">
				<div class="wp-block-button sensei-course-theme-quiz-graded-notice__pending-grade">
					<button class="wp-block-button__link">'
						. __( 'Pending teacher grade', 'sensei-lms' ) .
					'</button>
				</div>
			</div>';
	}
}

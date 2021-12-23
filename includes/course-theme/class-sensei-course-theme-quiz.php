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
	 *
	 * @return string The block HTML.
	 */
	public function maybe_add_quiz_results_notice() {

		$lesson_id   = Sensei_Utils::get_current_lesson();
		$quiz_id     = \Sensei()->lesson->lesson_quizzes( $lesson_id );
		$user_id     = get_current_user_id();
		$quiz_status = Sensei_Utils::user_lesson_status( $lesson_id, $user_id )->comment_approved;

		// If not one of the statuses that we handle then bail.
		if ( ! \Sensei()->lesson->is_quiz_submitted( $lesson_id, $user_id ) ) {
			return '';
		}

		// Prepare title.
		$grade = Sensei_Quiz::get_user_quiz_grade( $lesson_id, $user_id );
		$title = sprintf(
			// translators: The placeholder is the quiz grade.
			__( 'Your Grade: %1$s%%', 'sensei-lms' ),
			$grade
		);
		if ( 'ungraded' === $quiz_status ) {
			$title = __( 'Awaiting grade', 'sensei-lms' );
		}

		// Prepare message.
		$text = __( "You've passed the quiz and can continue to the next lesson.", 'sensei-lms' );
		if ( 'ungraded' === $quiz_status ) {
			$text = __( 'Your answers have been submitted and your teacher will grade this quiz shortly.', 'sensei-lms' );
		} elseif ( 'failed' === $quiz_status ) {
			$passmark = absint( get_post_meta( $quiz_id, '_quiz_passmark', true ), 2 );
			$text     = sprintf(
				// translators: The first placeholder is the minimum grade required, and the second placeholder is the actual grade.
				__( 'You require %1$s%% to pass this quiz. Your grade is %2$s%%.', 'sensei-lms' ),
				$passmark,
				$grade
			);
		}

		$actions = [];

		// Prepare reset quiz button.
		$reset_allowed = Sensei_Quiz::is_reset_allowed( $lesson_id );
		if ( $reset_allowed ) {
			$actions[] = self::render_reset_quiz();
		}

		// Prepare contact teacher button.
		$actions[] = self::render_contact_teacher();

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
				<button type='submit' class='sensei-course-theme-quiz-graded-notice__reset-quiz-button'>
					{$text}
				</button>
			</form>
		" );
	}

	/**
	 * Renders the contact teacher button.
	 */
	private static function render_contact_teacher() {
		$link  = '<a href="#" class="sensei-course-theme-quiz-graded-notice__contact-teacher-link">' . __( 'Contact teacher', 'sensei-lms' ) . '</a>';
		$block = new \Sensei_Block_Contact_Teacher();
		return $block->render_contact_teacher_block( null, $link );
	}
}

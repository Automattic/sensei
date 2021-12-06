<?php
/**
 * File containing Sensei_Course_Theme_Lesson class.
 *
 * @package sensei-lms
 * @since 3.15.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Course_Theme_Lesson class.
 *
 * @since 3.15.0
 */
class Sensei_Course_Theme_Lesson {
	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_Course_Theme_Lesson constructor. Prevents other instances from being created outside of `self::instance()`.
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
		if ( 'lesson' !== get_post_type() ) {
			return;
		}

		$this->maybe_add_quiz_results_notice();
		$this->maybe_add_prerequisite_notice();
	}

	/**
	 * Maybe add lesson quiz results notice.
	 */
	private function maybe_add_quiz_results_notice() {
		$lesson_id = \Sensei_Utils::get_current_lesson();
		$user_id   = wp_get_current_user()->ID;

		if ( empty( $lesson_id ) || empty( $user_id ) ) {
			return;
		}

		$quiz_permalink = Sensei()->lesson->get_quiz_permalink( $lesson_id );

		if ( empty( $quiz_permalink ) ) {
			return;
		}

		if ( ! Sensei()->lesson->is_quiz_submitted( $lesson_id, $user_id ) ) {
			return;
		}

		$user_lesson_status = \Sensei_Utils::user_lesson_status( $lesson_id, $user_id );
		$user_grade         = 0;

		if ( $user_lesson_status ) {
			if ( isset( $user_lesson_status->comment_ID ) ) {
				$user_grade = \Sensei_Utils::round( get_comment_meta( $user_lesson_status->comment_ID, 'grade', true ) );
			}
		}

		$quiz_id       = Sensei()->lesson->lesson_quizzes( $lesson_id );
		$quiz_passmark = absint( get_post_meta( $quiz_id, '_quiz_passmark', true ) );
		$pass_required = get_post_meta( $quiz_id, '_pass_required', true );

		if ( 'ungraded' === $user_lesson_status->comment_approved ) {
			$text = __( 'Awaiting grade', 'sensei-lms' );
		} elseif ( 'failed' === $user_lesson_status->comment_approved ) {
			// translators: Placeholders are the required grade and the actual grade, respectively.
			$text = sprintf( __( 'You require %1$s%% to pass this course. Your grade is %2$s%%.', 'sensei-lms' ), '<strong>' . $quiz_passmark . '</strong>', '<strong>' . $user_grade . '</strong>' );
		} else {
			// translators: Placeholder is the quiz grade.
			$text = sprintf( __( 'Your Grade %s%%', 'sensei-lms' ), '<strong class="sensei-course-theme-lesson-quiz-notice__grade">' . $user_grade . '</strong>' );
		}

		$notices = \Sensei_Context_Notices::instance( 'course_theme_lesson_quiz' );
		$actions = [
			[
				'label' => __( 'View quiz', 'sensei-lms' ),
				'url'   => $quiz_permalink,
				'style' => 'link',
			],
		];

		$notices->add_notice( 'lesson_quiz_results', $text, __( 'Quiz completed', 'sensei-lms' ), $actions );
	}

	/**
	 * Maybe add lesson prerequisite notice.
	 */
	private function maybe_add_prerequisite_notice() {
		$user_id             = get_current_user_id();
		$lesson_id           = \Sensei_Utils::get_current_lesson();
		$lesson_prerequisite = \Sensei_Lesson::find_first_prerequisite_lesson( $lesson_id, $user_id );

		if ( $lesson_prerequisite > 0 ) {
			$user_lesson_status = \Sensei_Utils::user_lesson_status( $lesson_prerequisite, $user_id );

			$prerequisite_lesson_link = '<a href="'
				. esc_url( get_permalink( $lesson_prerequisite ) )
				. '" title="'
				// translators: Placeholder is the lesson prerequisite title.
				. sprintf( esc_attr__( 'You must first complete: %1$s', 'sensei-lms' ), get_the_title( $lesson_prerequisite ) )
				. '">'
				. esc_html__( 'prerequisites', 'sensei-lms' )
				. '</a>';

			$text = ! empty( $user_lesson_status ) && 'ungraded' === $user_lesson_status->comment_approved
				// translators: Placeholder is the link to the prerequisite lesson.
				? sprintf( esc_html__( 'You will be able to view this lesson once the %1$s are completed. And a prerequisite is waiting for quiz grading.', 'sensei-lms' ), $prerequisite_lesson_link )
				// translators: Placeholder is the link to the prerequisite lesson.
				: sprintf( esc_html__( 'Please complete the %1$s to view this lesson content.', 'sensei-lms' ), $prerequisite_lesson_link );

			$notices = \Sensei_Context_Notices::instance( 'course_theme_locked_lesson' );
			$notices->add_notice( 'locked_lesson', $text, __( 'You don\'t have access to this lesson', 'sensei-lms' ) );
		}
	}
}

<?php
/**
 * File containing the Quiz_Graded class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails\Generators;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Quiz_Graded
 *
 * @internal
 *
 * @since 4.12.0
 */
class Quiz_Graded extends Email_Generators_Abstract {
	/**
	 * Identifier of the email.
	 *
	 * @var string
	 */
	const IDENTIFIER_NAME = 'quiz_graded';

	/**
	 * Identifier used in usage tracking.
	 *
	 * @var string
	 */
	const USAGE_TRACKING_TYPE = 'learner-graded-quiz';

	/**
	 * Initialize the email hooks.
	 *
	 * @access public
	 * @since 4.12.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'sensei_user_quiz_grade', array( $this, 'quiz_graded_mail_to_student' ), 10, 5 );
	}

	/**
	 * Send student an email when a quiz is graded.
	 *
	 * @access private
	 *
	 * @param int $user_id  Student ID.
	 * @param int $quiz_id  Quiz ID.
	 * @param int $grade    Grade.
	 * @param int $passmark Passmark.
	 */
	public function quiz_graded_mail_to_student( $user_id, $quiz_id, $grade, $passmark ) {
		$lesson_id = \Sensei()->quiz->get_lesson_id( $quiz_id );

		$lesson = get_post( $lesson_id );

		if ( ! $lesson ) {
			return;
		}

		if ( ! \Sensei_Utils::user_started_lesson( $lesson_id, $user_id ) ) {
			return;
		}

		$course_id = \Sensei()->lesson->get_course_id( $lesson->ID );

		if ( ! $course_id ) {
			return;
		}

		$course = get_post( $course_id );

		if ( ! $course ) {
			return;
		}

		$student      = new \WP_User( $user_id );
		$recipient    = stripslashes( $student->user_email );
		$pass_or_fail = __( 'You Did Not Pass', 'sensei-lms' );
		$quiz_url     = esc_url( get_permalink( $quiz_id ) );

		if ( $grade >= $passmark ) {
			$pass_or_fail = __( 'You Passed!', 'sensei-lms' );
		}

		$this->send_email_action(
			[
				$recipient => [
					'grade:validation' => $pass_or_fail,
					'course:name'      => get_the_title( $course->ID ),
					'lesson:name'      => get_the_title( $lesson->ID ),
					'grade:percentage' => $grade . '%',
					'quiz:url'         => $quiz_url,
				],
			]
		);
	}
}

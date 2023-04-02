<?php
/**
 * File containing the Student_Submits_Quiz class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails\Generators;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Student_Submits_Quiz
 *
 * @internal
 *
 * @since 4.12.0
 */
class Student_Submits_Quiz extends Email_Generators_Abstract {
	/**
	 * Identifier of the email.
	 *
	 * @var string
	 */
	const IDENTIFIER_NAME = 'student_submits_quiz';

	/**
	 * Identifier used in usage tracking.
	 *
	 * @var string
	 */
	const USAGE_TRACKING_TYPE = 'teacher-quiz-submitted';

	/**
	 * Initialize the email hooks.
	 *
	 * @access public
	 * @since 4.12.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'sensei_user_quiz_submitted', array( $this, 'student_submits_quiz_mail_to_teacher' ), 10, 5 );
	}

	/**
	 * Send email to teacher when student submits quiz.
	 *
	 * @access private
	 *
	 * @param int    $student_id      Student ID.
	 * @param int    $quiz_id         Quiz ID.
	 * @param int    $grade           Grade.
	 * @param int    $passmark        Passmark.
	 * @param string $quiz_grade_type Quiz grade type.
	 */
	public function student_submits_quiz_mail_to_teacher( $student_id = 0, $quiz_id = 0, $grade = 0, $passmark = 0, $quiz_grade_type = 'manual' ) {
		// Only trigger if the quiz was marked as manual grading, or auto grading didn't complete.
		if ( 'manual' !== $quiz_grade_type && ! is_wp_error( $grade ) ) {
			return;
		}

		$student   = new \WP_User( $student_id );
		$lesson_id = \Sensei()->quiz->get_lesson_id( $quiz_id );

		if ( ! $lesson_id ) {
			return;
		}

		$lesson = get_post( $lesson_id );

		if ( ! $lesson ) {
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

		$teacher_id = get_post_field( 'post_author', $course->ID, 'raw' );
		$teacher    = new \WP_User( $teacher_id );
		$recipient  = stripslashes( $teacher->user_email );
		$grade_url  = esc_url(
			add_query_arg(
				array(
					'page'    => 'sensei_grading',
					'quiz_id' => $quiz_id,
					'user'    => $student_id,
				),
				admin_url( 'admin.php' )
			)
		);

		$this->send_email_action(
			[
				$recipient => [
					'student:displayname' => $student->display_name,
					'course:name'         => get_the_title( $course->ID ),
					'lesson:name'         => get_the_title( $lesson->ID ),
					'grade:quiz'          => $grade_url,
				],
			]
		);
	}
}

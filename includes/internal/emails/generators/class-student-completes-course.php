<?php
/**
 * File containing the Student_Completes_Course class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails\Generators;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Student_Completes_Course
 *
 * @internal
 *
 * @since 4.12.0
 */
class Student_Completes_Course extends Email_Generators_Abstract {
	/**
	 * Identifier of the email.
	 *
	 * @var string
	 */
	const IDENTIFIER_NAME = 'student_completes_course';

	/**
	 * Identifier used in usage tracking.
	 *
	 * @var string
	 */
	const USAGE_TRACKING_TYPE = 'teacher-completed-course';

	/**
	 * Initialize the email hooks.
	 *
	 * @access public
	 * @since 4.12.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'sensei_course_status_updated', [ $this, 'student_completed_course_mail_to_teacher' ], 10, 3 );
	}

	/**
	 * Send email to teacher when a student completes a course.
	 *
	 * @param string $status      The status.
	 * @param int    $student_id  The learner ID.
	 * @param int    $course_id   The course ID.
	 *
	 * @access private
	 */
	public function student_completed_course_mail_to_teacher( $status = 'in-progress', $student_id = 0, $course_id = 0 ) {

		if ( 'complete' !== $status || ! \Sensei_Course::is_user_enrolled( $course_id, $student_id ) ) {
			return;
		}

		$student    = new \WP_User( $student_id );
		$teacher_id = get_post_field( 'post_author', $course_id, 'raw' );
		$teacher    = new \WP_User( $teacher_id );
		$recipient  = stripslashes( $teacher->user_email );
		$grade      = __( 'N/A', 'sensei-lms' );
		$lesson_ids = \Sensei()->course->course_lessons( $course_id, 'any', 'ids' );
		$manage_url = esc_url(
			add_query_arg(
				array(
					'page'      => 'sensei_learners',
					'course_id' => $course_id,
					'view'      => 'learners',
				),
				admin_url( 'admin.php' )
			)
		);

		if ( ! empty( $lesson_ids ) && \Sensei()->course->course_quizzes( $course_id, true ) ) {
			$grade = \Sensei_Utils::sensei_course_user_grade( $course_id, $student_id ) . '%';
		}

		$this->send_email_action(
			[
				$recipient => [
					'student:id'          => $student_id,
					'student:displayname' => $student->display_name,
					'course:id'           => $course_id,
					'course:name'         => get_the_title( $course_id ),
					'grade:percentage'    => $grade,
					'manage:students'     => $manage_url,
				],
			]
		);
	}
}

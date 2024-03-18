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

	use Course_Teachers_Trait;

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
		$this->maybe_add_action( 'sensei_course_status_updated', [ $this, 'student_completed_course_mail_to_teacher' ], 10, 3 );
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

		$email_replacements = [
			'student:id'          => $student_id,
			'student:displayname' => $student->display_name,
			'course:id'           => $course_id,
			'course:name'         => html_entity_decode( get_the_title( $course_id ) ),
			'grade:percentage'    => $grade,
			'manage:students'     => $manage_url,
		];

		$teacher_ids   = $this->get_course_teachers( $course_id );
		$recipients    = $this->get_recipients( $teacher_ids );
		$emais_to_send = array();
		foreach ( $recipients as $recipient ) {
			$emais_to_send[ $recipient ] = $email_replacements;
		}

		$this->send_email_action( $emais_to_send );
	}
}

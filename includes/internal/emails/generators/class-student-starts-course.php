<?php
/**
 * File containing the Student_Starts_Course class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails\Generators;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Student_Starts_Course
 *
 * @internal
 *
 * @since 4.12.0
 */
class Student_Starts_Course extends Email_Generators_Abstract {
	/**
	 * Identifier of the email.
	 *
	 * @var string
	 */
	const IDENTIFIER_NAME = 'student_starts_course';

	/**
	 * Identifier used in usage tracking.
	 *
	 * @var string
	 */
	const USAGE_TRACKING_TYPE = 'teacher-started-course';

	/**
	 * Initialize the email hooks.
	 *
	 * @access public
	 * @since 4.12.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'sensei_user_course_start', [ $this, 'student_started_course_mail_to_teacher' ], 10, 2 );
	}

	/**
	 * Send email to teacher when a student starts a course.
	 *
	 * @param int $student_id The student ID.
	 * @param int $course_id  The course ID.
	 *
	 * @access private
	 */
	public function student_started_course_mail_to_teacher( $student_id, $course_id ) {
		$course = get_post( $course_id );

		if ( ! $course || 'publish' !== $course->post_status ) {
			return;
		}

		$teacher    = new \WP_User( $course->post_author );
		$student    = new \WP_User( $student_id );
		$recipient  = stripslashes( $teacher->user_email );
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

		$this->send_email_action(
			[
				$recipient => [
					'student:displayname' => $student->display_name,
					'course:name'         => $course->post_title,
					'manage:students'     => $manage_url,
				],
			]
		);
	}
}

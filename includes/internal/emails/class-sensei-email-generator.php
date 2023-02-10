<?php
/**
 * File containing the Sensei_Email_Generator class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Sensei_Email_Generator
 *
 * @package Sensei\Internal\Emails
 */
class Sensei_Email_Generator {

	/**
	 * The constructor.
	 */
	public function __construct() {
		add_action( 'sensei_user_course_start', array( $this, 'student_started_course_mail_to_teacher' ), 10, 2 );
	}

	/**
	 * Send email to teacher when a student starts a course.
	 *
	 * @param int $student_id The student ID.
	 * @param int $course_id The course ID.
	 *
	 * @access private
	 */
	public function student_started_course_mail_to_teacher( $student_id, $course_id ) {
		$email_type = 'student_started_course_to_teacher';
		$course     = get_post( $course_id );
		$teacher    = new \WP_User( $course->post_author );
		$student    = new \WP_User( $student_id );
		$recipient  = stripslashes( $teacher->user_email );

		do_action(
			'sensei_send_html_email',
			$email_type,
			[
				$recipient => [
					'student:displayname' => $student->display_name,
					'course.name'         => $course->post_title,
				],
			]
		);
	}
}

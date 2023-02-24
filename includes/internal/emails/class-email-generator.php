<?php
/**
 * File containing the Email_Generator class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Email_Generator
 *
 * @package Sensei\Internal\Emails
 */
class Email_Generator {

	/**
	 * Initialize the class and add hooks.
	 *
	 * @internal
	 */
	public function init(): void {
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
		$email_name = 'student_starts_course';
		$course     = get_post( $course_id );

		if ( ! $course || 'publish' !== $course->post_status ) {
			return;
		}

		$teacher   = new \WP_User( $course->post_author );
		$student   = new \WP_User( $student_id );
		$recipient = stripslashes( $teacher->user_email );

		$this->send_email_action(
			$email_name,
			[
				$recipient => [
					'student:displayname' => $student->display_name,
					'course:name'         => $course->post_title,
				],
			]
		);
	}

	/**
	 * Invokes the sensei_email_send action.
	 *
	 * @param string $email_name    The email name.
	 * @param array  $replacements  The replacements.
	 *
	 * @access private
	 */
	private function send_email_action( $email_name, $replacements ) {
		/**
		 * Send HTML email.
		 *
		 * @since $$next-version$$
		 * @hook sensei_email_send
		 *
		 * @param {string} $email_name    The email name.
		 * @param {Array}  $replacements  The replacements.
		 */
		do_action( 'sensei_email_send', $email_name, $replacements );
	}
}

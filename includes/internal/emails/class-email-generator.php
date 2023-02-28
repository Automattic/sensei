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
		add_action( 'sensei_course_status_updated', [ $this, 'student_completed_course_mail_to_teacher' ], 10, 3 );
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
			$email_name,
			[
				$recipient => [
					'student:displayname' => $student->display_name,
					'course:name'         => $course->post_title,
					'manage:students'     => $manage_url,
				],
			]
		);
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

		$email_type = 'student_completes_course';
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
			$email_type,
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

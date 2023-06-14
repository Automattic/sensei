<?php
/**
 * File containing the Student_Sends_Message class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails\Generators;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Student_Sends_Message
 * Teacher receives email when a student sends a new message.
 *
 * @internal
 *
 * @since 4.12.0
 */
class Student_Sends_Message extends Email_Generators_Abstract {
	/**
	 * Identifier of the email.
	 *
	 * @var string
	 */
	const IDENTIFIER_NAME = 'student_sends_message';

	/**
	 * Identifier used in usage tracking.
	 *
	 * @var string
	 */
	const USAGE_TRACKING_TYPE = 'teacher-new-message';

	/**
	 * Initialize the email hooks.
	 *
	 * @access private
	 * @since 4.12.0
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'sensei_new_private_message', array( $this, 'new_message_mail_to_teacher' ), 10, 2 );
	}

	/**
	 * Send email to teacher when student sends a new private message.
	 *
	 * @access private
	 *
	 * @param int $message_id ID of The message post.
	 *
	 * @return void
	 */
	public function new_message_mail_to_teacher( $message_id ) {
		$message = get_post( $message_id );

		$student_username = get_post_meta( $message_id, '_sender', true );
		$student          = get_user_by( 'login', $student_username );
		$teacher_username = get_post_meta( $message_id, '_receiver', true );
		$teacher          = get_user_by( 'login', $teacher_username );

		if ( ! $teacher || ! $student ) {
			return;
		}

		$recipient = stripslashes( $teacher->user_email );
		$course_id = get_post_meta( $message->ID, '_post', true );
		$course    = get_post( $course_id );

		$this->send_email_action(
			[
				$recipient => [
					'student:displayname'    => esc_html( $student->display_name ),
					'course:name'            => esc_html( get_the_title( $course->ID ) ),
					'message:displaymessage' => esc_html( $message->post_content ),
					'subject:displaysubject' => esc_html( $message->post_title ),
					'reply:url'              => esc_url( get_permalink( absint( $message_id ) ) ),
				],
			]
		);
	}
}

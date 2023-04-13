<?php
/**
 * File containing the Student_Message_Reply class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails\Generators;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Student_Message_Reply
 * Student receives email when the teacher replies to a message.
 *
 * @internal
 *
 * @since 4.12.0
 */
class Student_Message_Reply extends Email_Generators_Abstract {
	/**
	 * Identifier of the email.
	 *
	 * @var string
	 */
	const IDENTIFIER_NAME = 'student_message_reply';

	/**
	 * Identifier used in usage tracking.
	 *
	 * @var string
	 */
	const USAGE_TRACKING_TYPE = 'learner-new-message-reply';

	/**
	 * Initialize the email hooks.
	 *
	 * @access public
	 * @since 4.12.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'sensei_private_message_reply', array( $this, 'message_reply_mail_to_student' ), 10, 2 );
	}

	/**
	 * Send email to student when the teacher replies to a private message.
	 *
	 * @access private
	 *
	 * @param \WP_Comment $comment The comment object.
	 * @param \WP_Post    $message The message post.
	 *
	 * @return void
	 */
	public function message_reply_mail_to_student( $comment, $message ) {
		$post_id = get_post_meta( $message->ID, '_post', true );
		$post    = get_post( $post_id );

		if ( ! $post || ! in_array( $post->post_type, array( 'course', 'lesson', 'quiz' ), true ) ) {
			return;
		}

		if ( 'lesson' === $post->post_type ) {
			$course_id = intval( get_post_meta( $post_id, '_lesson_course', true ) );
		} elseif ( 'quiz' === $post->post_type ) {
			$lesson_id = Sensei()->quiz->get_lesson_id( $post_id );

			if ( ! $lesson_id ) {
				return;
			}

			$course_id = intval( get_post_meta( $lesson_id, '_lesson_course', true ) );
		} else { // Message sent from course page.
			$course_id = $post_id;
		}

		$teacher_id = $post->post_author;

		if ( $comment->user_id !== $teacher_id ) {
			return;
		}

		$teacher = get_userdata( $teacher_id );

		$message_sender_login = get_post_meta( $message->ID, '_sender', true );
		$message_sender_user  = get_user_by( 'login', $message_sender_login );

		$message_receiver_login = get_post_meta( $message->ID, '_receiver', true );
		$message_receiver_user  = get_user_by( 'login', $message_receiver_login );

		$recipient_id = $message_receiver_user->ID === $teacher->ID ? $message_sender_user->ID : $message_receiver_user->ID;

		$recipient = stripslashes( get_userdata( $recipient_id )->user_email );

		$this->send_email_action(
			[
				$recipient => [
					'teacher:displayname'    => esc_html( $teacher->display_name ),
					'course:name'            => esc_html( get_the_title( $course_id ) ),
					'message:displaymessage' => esc_html( $comment->comment_content ),
					'subject:displaysubject' => esc_html( $message->post_title ),
					'reply:url'              => esc_url( get_comment_link( $comment ) ),
				],
			]
		);
	}
}

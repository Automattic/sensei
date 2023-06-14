<?php
/**
 * File containing the Teacher_Message_Reply class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails\Generators;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Teacher_Message_Reply
 * Teacher receives email when student replies to a message.
 *
 * @internal
 *
 * @since 4.12.0
 */
class Teacher_Message_Reply extends Email_Generators_Abstract {
	/**
	 * Identifier of the email.
	 *
	 * @var string
	 */
	const IDENTIFIER_NAME = 'teacher_message_reply';

	/**
	 * Identifier used in usage tracking.
	 *
	 * @var string
	 */
	const USAGE_TRACKING_TYPE = 'teacher-new-message-reply';

	/**
	 * Initialize the email hooks.
	 *
	 * @access public
	 * @since 4.12.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'sensei_private_message_reply', array( $this, 'message_reply_mail_to_teacher' ), 10, 2 );
	}

	/**
	 * Send email to teacher when student replies to a private message.
	 *
	 * @access private
	 *
	 * @param \WP_Comment $comment The comment object.
	 * @param \WP_Post    $message The message post.
	 *
	 * @return void
	 */
	public function message_reply_mail_to_teacher( $comment, $message ) {
		$course_id = get_post_meta( $message->ID, '_post', true );
		$course    = get_post( $course_id );

		if ( ! $course ) {
			return;
		}

		$teacher_id = $course->post_author;

		if ( $comment->user_id === $teacher_id ) {
			return;
		}

		$recipient = stripslashes( get_userdata( $teacher_id )->user_email );
		$commenter = get_userdata( $comment->user_id );

		$this->send_email_action(
			[
				$recipient => [
					'student:displayname'    => esc_html( $commenter->display_name ),
					'course:name'            => esc_html( get_the_title( $course->ID ) ),
					'message:displaymessage' => esc_html( $comment->comment_content ),
					'subject:displaysubject' => esc_html( $message->post_title ),
					'reply:url'              => esc_url( get_comment_link( $comment ) ),
				],
			]
		);
	}
}

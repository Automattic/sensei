<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WooThemes_Sensei_Email_New_Message_Reply' ) ) :

/**
 * Teacher New Message
 *
 * An email sent to the a user when they receive a reply to the private message.
 *
 * @class 		WooThemes_Sensei_Email_New_Message_Reply
 * @version		1.6.0
 * @package		Sensei/Classes/Emails
 * @author 		WooThemes
 */
class WooThemes_Sensei_Email_New_Message_Reply {

	var $template;
	var $subject;
	var $heading;
	var $recipient;
	var $original_sender;
	var $original_receiver;
	var $commenter;
	var $message;
	var $comment;

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		$this->template = 'new-message-reply';
		$this->subject = apply_filters( 'sensei_email_subject', sprintf( __( '[%1$s] You have a new message', 'woothemes-sensei' ), get_bloginfo( 'name' ) ), $this->template );
		$this->heading = apply_filters( 'sensei_email_heading', __( 'You have received a reply to your private message', 'woothemes-sensei' ), $this->template );
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @return void
	 */
	function trigger( $comment, $message ) {
		global $woothemes_sensei, $sensei_email_data;

		$this->comment = $comment;
		$this->message = $message;

		$this->commenter = get_userdata( $comment->user_id );

		$original_sender = get_post_meta( $this->message->ID, '_sender', true );
		$this->original_sender = get_user_by( 'login', $original_sender );

		$original_receiver = get_post_meta( $this->message->ID, '_receiver', true );
		$this->original_receiver = get_user_by( 'login', $original_receiver );

		$content_type = get_post_meta( $this->message->ID, '_posttype', true );
		$content_id = get_post_meta( $this->message->ID, '_post', true );
		$content_title = get_the_title( $content_id );

		$comment_link = get_comment_link( $comment );

        // setup the post type parameter
        $content_type = get_post_type( $content_id );
        if( !$content_type ){
            $content_type ='';
        }

        // Construct data array
        $sensei_email_data = apply_filters( 'sensei_email_data', array(
            'template'			=> $this->template,
            $content_type.'_id' => $content_id,
			'heading'			=> $this->heading,
			'commenter_name'	=> $this->commenter->display_name,
			'message'			=> $this->comment->comment_content,
			'comment_link'		=> $comment_link,
			'content_title'		=> $content_title,
			'content_type'		=> $content_type,
		), $this->template );

		// Set recipient
		if( $this->commenter->user_login == $original_sender ) {
			$this->recipient = stripslashes( $this->original_receiver->user_email );
		} else {
			$this->recipient = stripslashes( $this->original_sender->user_email );
		}

		// Send mail
		$woothemes_sensei->emails->send( $this->recipient, $this->subject, $woothemes_sensei->emails->get_content( $this->template ) );
	}
}

endif;

return new WooThemes_Sensei_Email_New_Message_Reply();
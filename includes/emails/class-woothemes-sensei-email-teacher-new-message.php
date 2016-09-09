<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WooThemes_Sensei_Email_Teacher_New_Message' ) ) :

/**
 * Teacher New Message
 *
 * An email sent to the teacher when one of their students sends them a private message.
 *
 * @package Users
 * @author Automattic
 *
 * @since		1.6.0
 */
class WooThemes_Sensei_Email_Teacher_New_Message {

	var $template;
	var $subject;
	var $heading;
	var $recipient;
	var $learner;
	var $teacher;
	var $message;

	/**
	 * Constructor
	 */
	function __construct() {
		$this->template = 'teacher-new-message';
	}

	/**
	 * trigger function.
	 *
	 * @access public
     * @param integer $message_id
	 * @return void
	 */
	function trigger( $message_id = 0 ) {
		global  $sensei_email_data;

		$this->message = get_post( $message_id );

		$learner_username = get_post_meta( $message_id, '_sender', true );
		$this->learner = get_user_by( 'login', $learner_username );

		$teacher_username = get_post_meta( $message_id, '_receiver', true );
		$this->teacher = get_user_by( 'login', $teacher_username );

		// Set recipient (teacher)
		$this->recipient = stripslashes( $this->teacher->user_email );
		
		do_action('sensei_before_mail', $this->recipient);
		
		$this->subject = apply_filters( 'sensei_email_subject', sprintf( __( '[%1$s] You have received a new private message', 'woothemes-sensei' ), get_bloginfo( 'name' ) ), $this->template );
		$this->heading = apply_filters( 'sensei_email_heading', __( 'Your student has sent you a private message', 'woothemes-sensei' ), $this->template );
 

		$content_type = get_post_meta( $message_id, '_posttype', true );
		$content_id = get_post_meta( $message_id, '_post', true );
		$content_title = get_the_title( $content_id );

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
			'teacher_id'		=> $this->teacher->ID,
			'learner_id'		=> $this->learner->ID,
			'learner_name'		=> $this->learner->display_name,
			'message_id'		=> $message_id,
			'message'			=> $this->message->post_content,
			'content_title'		=> $content_title,
			'content_type'		=> $content_type,
		), $this->template );

		// Send mail
		Sensei()->emails->send( $this->recipient, $this->subject, Sensei()->emails->get_content( $this->template ) );

		do_action('sensei_after_sending_email');

		wp_safe_redirect( esc_url_raw( add_query_arg( array( 'send' => 'complete' ) ) ) );
		exit;
	}
}

endif;

return new WooThemes_Sensei_Email_Teacher_New_Message();
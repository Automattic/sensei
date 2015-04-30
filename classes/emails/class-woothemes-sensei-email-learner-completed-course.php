<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WooThemes_Sensei_Email_Learner_Completed_Course' ) ) :

/**
 * Learner Completed Course
 *
 * An email sent to the learner when they complete a course.
 *
 * @class 		WooThemes_Sensei_Email_Learner_Completed_Course
 * @version		1.6.0
 * @package		Sensei/Classes/Emails
 * @author 		WooThemes
 */
class WooThemes_Sensei_Email_Learner_Completed_Course {

	var $template;
	var $subject;
	var $heading;
	var $recipient;
	var $user;

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		$this->template = 'learner-completed-course';
		$this->subject = apply_filters( 'sensei_email_subject', sprintf( __( '[%1$s] You have completed a course', 'woothemes-sensei' ), get_bloginfo( 'name' ) ), $this->template );
		$this->heading = apply_filters( 'sensei_email_heading', __( 'You have completed a course', 'woothemes-sensei' ), $this->template );
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @return void
	 */
	function trigger( $user_id = 0, $course_id = 0 ) {
		global $woothemes_sensei, $sensei_email_data;

		// Get learner user object
		$this->user = new WP_User( $user_id );

		// Get passed status
		$passed = __( 'passed', 'woothemes-sensei' );
		if( ! WooThemes_Sensei_Utils::sensei_user_passed_course( $course_id, $user_id ) ) {
			$passed = __( 'failed', 'woothemes-sensei' );
		}

		// Construct data array
		$sensei_email_data = apply_filters( 'sensei_email_data', array(
			'template'			=> $this->template,
			'heading'			=> $this->heading,
			'user_id'			=> $user_id,
			'course_id'			=> $course_id,
			'passed'			=> $passed,
		), $this->template );

		// Set recipient (learner)
		$this->recipient = stripslashes( $this->user->user_email );

		// Send mail
		$woothemes_sensei->emails->send( $this->recipient, $this->subject, $woothemes_sensei->emails->get_content( $this->template ) );
	}
}

endif;

return new WooThemes_Sensei_Email_Learner_Completed_Course();
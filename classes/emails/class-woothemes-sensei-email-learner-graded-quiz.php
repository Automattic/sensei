<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WooThemes_Sensei_Email_Learner_Graded_Quiz' ) ) :

/**
 * Learner Graded Quiz
 *
 * An email sent to the learner when their quiz has been graded (auto or manual).
 *
 * @class 		WooThemes_Sensei_Email_Learner_Graded_Quiz
 * @version		1.6.0
 * @package		Sensei/Classes/Emails
 * @author 		WooThemes
 */
class WooThemes_Sensei_Email_Learner_Graded_Quiz {

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
		$this->template = 'learner-graded-quiz';
		$this->subject = apply_filters( 'sensei_email_subject', sprintf( __( '[%1$s] Your quiz has been graded', 'woothemes-sensei' ), get_bloginfo( 'name' ) ), $this->template );
		$this->heading = apply_filters( 'sensei_email_heading', __( 'Your quiz has been graded', 'woothemes-sensei' ), $this->template );
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @return void
	 */
	function trigger( $user_id = 0, $quiz_id = 0, $grade = 0, $passmark = 0 ) {
		global $woothemes_sensei, $sensei_email_data;

		// Get learner user object
		$this->user = new WP_User( $user_id );

		// Get passed flag
		$passed = 'failed';
		if( $grade >= $passmark ) {
			$passed = 'passed';
		}

		// Get grade tye (auto/manual)
		$grade_type = get_post_meta( $quiz_id, '_quiz_grade_type', true );

		if( 'auto' == $grade_type ) {
			$this->subject = apply_filters( 'sensei_email_subject', sprintf( __( '[%1$s] You have completed a quiz', 'woothemes-sensei' ), get_bloginfo( 'name' ) ), $this->template );
			$this->heading = apply_filters( 'sensei_email_heading', __( 'You have completed a quiz', 'woothemes-sensei' ), $this->template );
		}

		$lesson_id = get_post_meta( $quiz_id, '_quiz_lesson', true );

		// Construct data array
		$sensei_email_data = apply_filters( 'sensei_email_data', array(
			'template'			=> $this->template,
			'heading'			=> $this->heading,
			'user_id'			=> $user_id,
			'user_name'         => stripslashes( $this->user->display_name ),
			'lesson_id'			=> $lesson_id,
			'quiz_id'			=> $quiz_id,
			'grade'				=> $grade,
			'passmark'			=> $passmark,
			'passed'			=> $passed,
			'grade_type'		=> $grade_type,
		), $this->template );

		// Set recipient (learner)
		$this->recipient = stripslashes( $this->user->user_email );

		// Send mail
		$woothemes_sensei->emails->send( $this->recipient, $this->subject, $woothemes_sensei->emails->get_content( $this->template ) );
	}
}

endif;

return new WooThemes_Sensei_Email_Learner_Graded_Quiz();
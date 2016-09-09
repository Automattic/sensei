<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WooThemes_Sensei_Email_Learner_Graded_Quiz' ) ) :

/**
 * Learner Graded Quiz
 *
 * An email sent to the learner when their quiz has been graded (auto or manual).
 *
 * @package Users
 * @author Automattic
 *
 * @since		1.6.0
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
	 */
	function __construct() {
		$this->template = 'learner-graded-quiz';
	}

	/**
	 * trigger function.
	 *
     * @param int $user_id
     * @param int $quiz_id
     * @param int $grade
     * @param int $passmark
     *
	 * @return void
	 */
	function trigger ( $user_id = 0, $quiz_id = 0, $grade = 0, $passmark = 0 ) {

		global  $sensei_email_data;

		$lesson_id = get_post_meta( $quiz_id, '_quiz_lesson', true );

		if ( ! Sensei_Utils::user_started_lesson( $lesson_id, $user_id ) ) {
			return;
		}

		// Get learner user object
		$this->user = new WP_User( $user_id );

		// Set recipient (learner)
		$this->recipient = stripslashes( $this->user->user_email );
 
		do_action('sensei_before_mail', $this->recipient);
		
		$this->subject = apply_filters( 'sensei_email_subject', sprintf( __( '[%1$s] Your quiz has been graded', 'woothemes-sensei' ), get_bloginfo( 'name' ) ), $this->template );
		$this->heading = apply_filters( 'sensei_email_heading', __( 'Your quiz has been graded', 'woothemes-sensei' ), $this->template );
		

		// Get passed flag
		$passed = __( 'failed', 'woothemes-sensei' );
		if( $grade >= $passmark ) {
			$passed = __( 'passed', 'woothemes-sensei' );
		}

		// Get grade tye (auto/manual)
		$grade_type = get_post_meta( $quiz_id, '_quiz_grade_type', true );

		if( 'auto' == $grade_type ) {
			$this->subject = apply_filters( 'sensei_email_subject', sprintf( __( '[%1$s] You have completed a quiz', 'woothemes-sensei' ), get_bloginfo( 'name' ) ), $this->template );
			$this->heading = apply_filters( 'sensei_email_heading', __( 'You have completed a quiz', 'woothemes-sensei' ), $this->template );
		}

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

		// Send mail
		Sensei()->emails->send( $this->recipient, $this->subject, Sensei()->emails->get_content( $this->template ) );

		do_action('sensei_after_sending_email');
	}
}

endif;

return new WooThemes_Sensei_Email_Learner_Graded_Quiz();
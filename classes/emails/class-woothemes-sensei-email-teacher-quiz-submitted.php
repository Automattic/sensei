<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WooThemes_Sensei_Email_Teacher_Quiz_Submitted' ) ) :

/**
 * Teacher Quiz Submitted
 *
 * An email sent to the teacher when one of their students submits a quiz for manual grading.
 *
 * @class 		WooThemes_Sensei_Email_Teacher_Quiz_Submitted
 * @version		1.6.0
 * @package		Sensei/Classes/Emails
 * @author 		WooThemes
 */
class WooThemes_Sensei_Email_Teacher_Quiz_Submitted {

	var $template;
	var $subject;
	var $heading;
	var $recipient;
	var $learner;
	var $teacher;

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		$this->template = 'teacher-quiz-submitted';
		$this->subject = apply_filters( 'sensei_email_subject', sprintf( __( '[%1$s] Your student has submitted a quiz for grading', 'woothemes-sensei' ), get_bloginfo( 'name' ) ), $this->template );
		$this->heading = apply_filters( 'sensei_email_heading', __( 'Your student has submitted a quiz for grading', 'woothemes-sensei' ), $this->template );
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @return void
	 */
	function trigger( $learner_id = 0, $quiz_id = 0 ) {
		global $woothemes_sensei, $sensei_email_data;

		// Get learner user object
		$this->learner = new WP_User( $learner_id );

		// Get teacher ID and user object
		$teacher_id = get_post_field( 'post_author', $course_id, 'raw' );
		$this->teacher = new WP_User( $teacher_id );

		$lesson_id = get_post_meta( $quiz_id, '_quiz_lesson', true );

		// Construct data array
		$sensei_email_data = apply_filters( 'sensei_email_data', array(
			'template'			=> $this->template,
			'heading'			=> $this->heading,
			'teacher_id'		=> $teacher_id,
			'learner_id'		=> $learner_id,
			'learner_name'		=> $this->learner->display_name,
			'quiz_id'			=> $quiz_id,
			'lesson_id'			=> $lesson_id,
		), $this->template );

		// Set recipient (teacher)
		$this->recipient = stripslashes( $this->teacher->user_email );

		// Send mail
		$woothemes_sensei->emails->send( $this->recipient, $this->subject, $woothemes_sensei->emails->get_content( $this->template ) );
	}
}

endif;

return new WooThemes_Sensei_Email_Teacher_Quiz_Submitted();
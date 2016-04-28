<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WooThemes_Sensei_Email_Teacher_Quiz_Submitted' ) ) :

/**
 * Teacher Quiz Submitted
 *
 * An email sent to the teacher when one of their students submits a quiz for manual grading.
 *
 * @package Users
 * @author Automattic
 *
 * @since		1.6.0
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
	 */
	function __construct() {
		$this->template = 'teacher-quiz-submitted';
	}

	/**
	 * trigger function.
     *
     * @param integer $learner_id
     * @param integer $quiz_id
     *
	 * @return void
	 */
	function trigger( $learner_id = 0, $quiz_id = 0 ) {
		global  $sensei_email_data;

		// Get learner user object
		$this->learner = new WP_User( $learner_id );

		// Get teacher ID and user object
        $lesson_id = get_post_meta( $quiz_id, '_quiz_lesson', true );

		if ( ! Sensei_Utils::user_started_lesson( $lesson_id, $learner_id ) ) {
			return;
		}

        $course_id = get_post_meta( $lesson_id, '_lesson_course', true );
		$teacher_id = get_post_field( 'post_author', $course_id, 'raw' );
		$this->teacher = new WP_User( $teacher_id );

		// Set recipient (teacher)
		$this->recipient = stripslashes( $this->teacher->user_email );
		
		do_action('sensei_before_mail', $this->recipient);
		
		$this->subject = apply_filters( 'sensei_email_subject', sprintf( __( '[%1$s] Your student has submitted a quiz for grading', 'woothemes-sensei' ), get_bloginfo( 'name' ) ), $this->template );
		$this->heading = apply_filters( 'sensei_email_heading', __( 'Your student has submitted a quiz for grading', 'woothemes-sensei' ), $this->template );
 

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

		// Send mail
		Sensei()->emails->send( $this->recipient, $this->subject, Sensei()->emails->get_content( $this->template ) );

		do_action('sensei_after_sending_email');
	}
}

endif;

return new WooThemes_Sensei_Email_Teacher_Quiz_Submitted();
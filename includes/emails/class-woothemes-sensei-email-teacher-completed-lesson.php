<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WooThemes_Sensei_Email_Teacher_Completed_Lesson' ) ) :

/**
 * Teacher Completed Lesson
 *
 * An email sent to the teacher when one of their students completes a Lesson.
 *
 * @package Users
 * @author Automattic
 *
 * @since		1.6.0
 */
class WooThemes_Sensei_Email_Teacher_Completed_Lesson {

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
		$this->template = 'teacher-completed-lesson';
		$this->subject = apply_filters( 'sensei_email_subject', sprintf( __( '[%1$s] Your student has completed a lesson', 'woothemes-sensei' ), get_bloginfo( 'name' ) ), $this->template );
		$this->heading = apply_filters( 'sensei_email_heading', __( 'Your student has completed a lesson', 'woothemes-sensei' ), $this->template );
	}

	/**
	 * trigger function.
	 *
     * @param int $learner_id
     * @param int $lesson_id
     *
	 * @return void
	 */
	function trigger( $learner_id = 0, $lesson_id = 0 ) {

		global $sensei_email_data;

		if ( ! Sensei_Utils::user_started_lesson( $lesson_id, $learner_id ) ) {
			return;
		}

		// Get learner user object
		$this->learner = new WP_User( $learner_id );

		// Get teacher ID and user object
		$teacher_id = get_post_field( 'post_author', $lesson_id, 'raw' );
		$this->teacher = new WP_User( $teacher_id );

        // Construct data array
		$sensei_email_data = apply_filters( 'sensei_email_data', array(
			'template'			=> $this->template,
			'heading'			=> $this->heading,
			'teacher_id'		=> $teacher_id,
			'learner_id'		=> $learner_id,
			'learner_name'		=> $this->learner->display_name,
			'lesson_id'			=> $lesson_id,
		), $this->template );

		// Set recipient (teacher)
		$this->recipient = stripslashes( $this->teacher->user_email );

		// Send mail
        Sensei()->emails->send( $this->recipient, $this->subject, Sensei()->emails->get_content( $this->template ) );
	}
}

endif;

return new WooThemes_Sensei_Email_Teacher_Completed_Lesson();
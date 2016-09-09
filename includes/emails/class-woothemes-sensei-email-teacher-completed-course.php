<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WooThemes_Sensei_Email_Teacher_Completed_Course' ) ) :

/**
 * Teacher Completed Course
 *
 * An email sent to the teacher when one of their students completes a course.
 *
 * @package Users
 * @author Automattic
 *
 * @since		1.6.0
 */
class WooThemes_Sensei_Email_Teacher_Completed_Course {

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
	 */
	function __construct() {
		$this->template = 'teacher-completed-course';
	}

	/**
	 * trigger function.
	 *
     * @param int $learner_id
     * @param int $course_id
     *
	 * @return void
	 */
	function trigger( $learner_id = 0, $course_id = 0 ) {
		global  $sensei_email_data;

		if ( ! Sensei_Utils::user_started_course( $course_id, $learner_id ) ) {
			return;
		}
		// Get learner user object
		$this->learner = new WP_User( $learner_id );

		// Get teacher ID and user object
		$teacher_id = get_post_field( 'post_author', $course_id, 'raw' );
		$this->teacher = new WP_User( $teacher_id );

		// Set recipient (learner)
		$this->recipient = stripslashes( $this->teacher->user_email );
		
		do_action('sensei_before_mail', $this->recipient);
		
		$this->subject = apply_filters( 'sensei_email_subject', sprintf( __( '[%1$s] Your student has completed a course', 'woothemes-sensei' ), get_bloginfo( 'name' ) ), $this->template );
		$this->heading = apply_filters( 'sensei_email_heading', __( 'Your student has completed a course', 'woothemes-sensei' ), $this->template );

		// Get passed status
		$passed = __( 'passed', 'woothemes-sensei' );
		if( ! Sensei_Utils::sensei_user_passed_course( $course_id, $learner_id ) ) {
			$passed = __( 'failed', 'woothemes-sensei' );
		}

		// Construct data array
		$sensei_email_data = apply_filters( 'sensei_email_data', array(
			'template'			=> $this->template,
			'heading'			=> $this->heading,
			'teacher_id'		=> $teacher_id,
			'learner_id'		=> $learner_id,
			'learner_name'		=> $this->learner->display_name,
			'course_id'			=> $course_id,
			'passed'			=> $passed,
		), $this->template );

		// Send mail
		Sensei()->emails->send( $this->recipient, $this->subject, Sensei()->emails->get_content( $this->template ) );

		do_action('sensei_after_sending_email');
	}
}

endif;

return new WooThemes_Sensei_Email_Teacher_Completed_Course();
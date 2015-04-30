<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WooThemes_Sensei_Email_Teacher_Completed_Course' ) ) :

/**
 * Teacher Completed Course
 *
 * An email sent to the teacher when one of their students completes a course.
 *
 * @class 		WooThemes_Sensei_Email_Teacher_Completed_Course
 * @version		1.6.0
 * @package		Sensei/Classes/Emails
 * @author 		WooThemes
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
	 * @return void
	 */
	function __construct() {
		$this->template = 'teacher-completed-course';
		$this->subject = apply_filters( 'sensei_email_subject', sprintf( __( '[%1$s] Your student has completed a course', 'woothemes-sensei' ), get_bloginfo( 'name' ) ), $this->template );
		$this->heading = apply_filters( 'sensei_email_heading', __( 'Your student has completed a course', 'woothemes-sensei' ), $this->template );
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @return void
	 */
	function trigger( $learner_id = 0, $course_id = 0 ) {
		global $woothemes_sensei, $sensei_email_data;

		// Get learner user object
		$this->learner = new WP_User( $learner_id );

		// Get teacher ID and user object
		$teacher_id = get_post_field( 'post_author', $course_id, 'raw' );
		$this->teacher = new WP_User( $teacher_id );

		// Get passed status
		$passed = __( 'passed', 'woothemes-sensei' );
		if( ! WooThemes_Sensei_Utils::sensei_user_passed_course( $course_id, $learner_id ) ) {
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

		// Set recipient (learner)
		$this->recipient = stripslashes( $this->teacher->user_email );

		// Send mail
		$woothemes_sensei->emails->send( $this->recipient, $this->subject, $woothemes_sensei->emails->get_content( $this->template ) );
	}
}

endif;

return new WooThemes_Sensei_Email_Teacher_Completed_Course();
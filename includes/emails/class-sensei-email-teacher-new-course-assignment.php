<?php
/**
 * This email will be sent to a teacher when a course is assigned to them.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (  class_exists('Sensei_Email_Teacher_New_Course_Assignment') ){
    return;
}

/**
 * Teacher Started Course
 *
 * An email sent to the teacher when one of their students starts a course.
 *
 * @package Users
 * @author Automattic
 *
 * @since		1.6.0
 */
class Sensei_Email_Teacher_New_Course_Assignment {

	var $template;
	var $subject;
	var $heading;
	var $recipient;
	var $learner;
	var $teacher;

	/**
	 * Constructor
	 * @since 1.8.0
	 * @access public
	 */
	function __construct() {

        $this->template = 'teacher-new-course-assignment';
        return;
	}

	/**
	 * trigger function.
	 *
	 * @access public
     * @param $teacher_id
     * @param $course_id
	 * @return void
	 */
	function trigger( $teacher_id = 0, $course_id = 0 ) {
		global $sensei_email_data;

		$this->teacher = new WP_User( $teacher_id );
        $this->recipient = stripslashes( $this->teacher->user_email );
        do_action('sensei_before_mail', $this->recipient);
				
		$this->heading = apply_filters( 'sensei_email_heading', __( 'Course assigned to you', 'woothemes-sensei' ), $this->template );
        $this->subject = apply_filters( 'sensei_email_subject', __( 'New course assigned to you', 'woothemes-sensei' ), $this->template );
 
        //course edit link
        $course_edit_link = admin_url('post.php?post=' . $course_id . '&action=edit' );

        // Course name
        $course = get_post( $course_id);
		// Construct data array
		$sensei_email_data = apply_filters( 'sensei_email_data', array(
			'template'			=> $this->template,
			'heading'			=> $this->heading,
			'teacher_id'		=> $teacher_id,
			'course_id'			=> $course_id,
            'course_name'			=> $course->post_title,
            'course_edit_link' => $course_edit_link,
		), $this->template );

		// Send mail
		Sensei()->emails->send( $this->recipient, $this->subject, Sensei()->emails->get_content( $this->template ) );

		do_action('sensei_after_sending_email');
	}
}

/**
 * Return a new instance of this files class
 */
return new Sensei_Email_Teacher_New_Course_Assignment();

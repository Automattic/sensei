<?php
// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis -- Prevent "Unused global variable $sensei_email_data"
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Sensei_Email_Teacher_Started_Course' ) ) :

	/**
	 * Teacher Started Course
	 *
	 * An email sent to the teacher when one of their students starts a course.
	 *
	 * @package Users
	 * @author Automattic
	 *
	 * @since       1.6.0
	 */
	class Sensei_Email_Teacher_Started_Course {

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
			$this->template = 'teacher-started-course';
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

			// Get learner user object
			$this->learner = new WP_User( $learner_id );

			// Get teacher ID and user object
			$teacher_id    = get_post_field( 'post_author', $course_id, 'raw' );
			$this->teacher = new WP_User( $teacher_id );

			// Set recipient (learner)
			$this->recipient = stripslashes( $this->teacher->user_email );

			do_action( 'sensei_before_mail', $this->recipient );

			//Get course name
			$course_post = get_post( $course_id );
			$course_name = esc_html( $course_post->post_title );

			// translators: Placeholders are the blog name and Course Name.
			$this->subject = apply_filters( 'sensei_email_subject', sprintf( __( '[%1$s] Your student has started a new course: %2$s ', 'sensei-lms' ), get_bloginfo( 'name' ), $course_title ), $this->template );
			// translators: Placeholder is the Course Name.
			$this->heading = apply_filters( 'sensei_email_heading', sprintf( __( 'Your student has started the course: %1$s', 'sensei-lms' ), $course_name ), $this->template );

			// Construct data array
			$sensei_email_data = apply_filters(
				'sensei_email_data',
				array(
					'template'     => $this->template,
					'heading'      => $this->heading,
					'teacher_id'   => $teacher_id,
					'learner_id'   => $learner_id,
					'learner_name' => $this->learner->display_name,
					'course_id'    => $course_id,
					'course_name'  => $course_name,
				),
				$this->template
			);

			// Send mail
			Sensei()->emails->send( $this->recipient, $this->subject, Sensei()->emails->get_content( $this->template ) );

			do_action( 'sensei_after_sending_email' );
		}
	}

endif;

return new Sensei_Email_Teacher_Started_Course();

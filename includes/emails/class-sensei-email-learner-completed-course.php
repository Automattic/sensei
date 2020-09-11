<?php
// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis -- Prevent "Unused global variable $sensei_email_data"
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Sensei_Email_Learner_Completed_Course', false ) ) :

	/**
	 * Learner Completed Course
	 *
	 * An email sent to the learner when they complete a course.
	 *
	 * @package Users
	 * @author Automattic
	 *
	 * @since       1.6.0
	 */
	class Sensei_Email_Learner_Completed_Course {

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
		}

		/**
		 * trigger function.
		 *
		 * @access public
		 *
		 * @param int $user_id
		 * @param int $course_id
		 *
		 * @return void
		 */
		function trigger( $user_id = 0, $course_id = 0 ) {
			global  $sensei_email_data;

			if ( ! Sensei_Course::is_user_enrolled( $course_id, $user_id ) ) {
				return;
			}

			// Get learner user object
			$this->user = new WP_User( $user_id );

			// Set recipient (learner)
			$this->recipient = stripslashes( $this->user->user_email );

			do_action( 'sensei_before_mail', $this->recipient );

			// translators: Placeholder is the blog name.
			$this->subject = apply_filters( 'sensei_email_subject', sprintf( __( '[%1$s] You have completed a course', 'sensei-lms' ), get_bloginfo( 'name' ) ), $this->template );
			$this->heading = apply_filters( 'sensei_email_heading', __( 'You have completed a course', 'sensei-lms' ), $this->template );

			// Get passed status
			$passed = __( 'passed', 'sensei-lms' );
			if ( ! Sensei_Utils::sensei_user_passed_course( $course_id, $user_id ) ) {
				$passed = __( 'failed', 'sensei-lms' );
			}

			// Construct data array
			$sensei_email_data = apply_filters(
				'sensei_email_data',
				array(
					'template'  => $this->template,
					'heading'   => $this->heading,
					'user_id'   => $user_id,
					'course_id' => $course_id,
					'passed'    => $passed,
				),
				$this->template
			);

			// Send mail
			Sensei()->emails->send( $this->recipient, $this->subject, Sensei()->emails->get_content( $this->template ) );

			do_action( 'sensei_after_sending_email' );
		}
	}

endif;

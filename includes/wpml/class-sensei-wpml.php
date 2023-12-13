<?php
/**
 * File containing the Sensei_WPML class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_WPML
 */
class Sensei_WPML {
	public function __construct() {
		add_action( 'sensei_before_mail', array( $this, 'sensei_before_mail' ) );
		add_action( 'sensei_after_sending_email', array( $this, 'sensei_after_sending_email' ) );
		add_action( 'sensei_course_structure_lesson_created', array( $this, 'set_language_details_when_lesson_created' ), 10, 2 );
		add_action( 'sensei_course_structure_quiz_created', array( $this, 'set_language_details_when_quiz_created' ), 10, 2 );
	}

	/**
	 * Switch language for email.
	 *
	 * @param string $email_address Recipient's email address.
	 */
	public function sensei_before_mail( $email_address ) {
		/**
		* Switch language for email
		*
		* Allows WPML to switch current language to one preferred by email recipient.
		* WPML checks language set in user preferences and applies it for email
		* string localisation
		* It runs before any email string is obtained and localised
		*
		* @since 1.9.7
		*
		* @param string  $email_address Recipient's email address
		*/
		do_action( 'wpml_switch_language_for_email', $email_address );
	}

	/**
	 * Restore language after sending email.
	 */
	public function sensei_after_sending_email() {
		/**
		* Restore language after sending email
		*
		* Allows WPML to switch language to the last one before switching with
		* action 'wpml_switch_language_for_email'
		* It runs just after wp_mail() call
		* No params
		*
		* @since 1.9.7
		*/
		do_action( 'wpml_restore_language_from_email' );
	}

	/**
	 * Set language details for the lesson when it is created.
	 *
	 * @since $$next-version$$
	 * @param int $lesson_id Lesson ID.
	 * @param int $course_id Course ID.
	 */
	public function set_language_details_when_lesson_created( $lesson_id, $course_id ) {

		// Get course language_code.
		$language_code = apply_filters(
			'wpml_element_language_code',
			null,
			array(
				'element_id'   => $course_id,
				'element_type' => 'post_course',
			)
		);
		if ( ! $language_code ) {
			// Use current language if course language is not set.
			$language_code = apply_filters( 'wpml_current_language', null );
		}

		$args = array(
			'element_id'    => $lesson_id,
			'element_type'  => 'post_lesson',
			'trid'          => false,
			'language_code' => $language_code,
		);

		// Set language details for the lesson.
		do_action( 'wpml_set_element_language_details', $args );
	}

	/**
	 * Set language details for the lesson when it is created.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $quiz_id   Quiz ID.
	 * @param int $lesson_id Lesson ID.
	 */
	public function set_language_details_when_quiz_created( $quiz_id, $lesson_id ) {

		// Get lesson language_code.
		$language_code = apply_filters(
			'wpml_element_language_code',
			null,
			array(
				'element_id'   => $lesson_id,
				'element_type' => 'post_lesson',
			)
		);
		if ( ! $language_code ) {
			// Use current language if lesson language is not set.
			$language_code = apply_filters( 'wpml_current_language', null );
		}

		$args = array(
			'element_id'    => $quiz_id,
			'element_type'  => 'post_quiz',
			'trid'          => false,
			'language_code' => $language_code,
		);

		// Set language details for the lesson.
		do_action( 'wpml_set_element_language_details', $args );
	}
}

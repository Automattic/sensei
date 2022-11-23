<?php
/**
 * File containing Sensei_Tool_Recalculate_Enrolment class.
 *
 * @package sensei-lms
 * @since 3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Tool_Recalculate_Enrolment class.
 *
 * @since 3.7.0
 */
class Sensei_Tool_Recalculate_Enrolment implements Sensei_Tool_Interface {
	/**
	 * Get the ID of the tool.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'recalculate-enrolment';
	}

	/**
	 * Get the name of the tool.
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Recalculate Enrollments', 'sensei-lms' );
	}

	/**
	 * Get the description of the tool.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Invalidate the cached enrollment and trigger recalculation for all users and courses.', 'sensei-lms' );
	}

	/**
	 * Run the tool.
	 */
	public function process() {
		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();
		$enrolment_manager->reset_site_salt();

		Sensei_Tools::instance()->add_user_message( __( 'Course enrollment cache has been invalidated and is being recalculated.', 'sensei-lms' ) );
	}

	/**
	 * Is the tool currently available?
	 *
	 * @return bool True if tool is available.
	 */
	public function is_available() {
		return true;
	}
}

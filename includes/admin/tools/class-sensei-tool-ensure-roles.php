<?php
/**
 * File containing Sensei_Tool_Ensure_Roles class.
 *
 * @package sensei-lms
 * @since 3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Tool_Ensure_Roles class.
 *
 * @since 3.7.0
 */
class Sensei_Tool_Ensure_Roles implements Sensei_Tool_Interface {
	/**
	 * Get the ID of the tool.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'ensure-roles';
	}

	/**
	 * Get the name of the tool.
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Ensure Roles', 'sensei-lms' );
	}

	/**
	 * Get the description of the tool.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Ensures Sensei LMS specific roles and capabilities are set up properly.', 'sensei-lms' );
	}

	/**
	 * Run the tool.
	 */
	public function process() {
		Sensei()->assign_role_caps();
		Sensei()->add_editor_caps();
		Sensei()->add_sensei_admin_caps();
		Sensei()->teacher->create_role();

		Sensei_Tools::instance()->add_user_message( __( 'Sensei LMS specific roles and capabilities have been set up again.', 'sensei-lms' ) );
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

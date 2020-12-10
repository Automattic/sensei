<?php
/**
 * File containing Sensei_Tool_Legacy_Data_Updates class.
 *
 * @package sensei-lms
 * @since 3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Tool_Legacy_Data_Updates class.
 *
 * @since 3.7.0
 */
class Sensei_Tool_Legacy_Data_Updates implements Sensei_Tool_Interface {
	/**
	 * Get the ID of the tool.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'legacy-data-updates';
	}

	/**
	 * Get the name of the tool.
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Legacy Data Updates', 'sensei-lms' );
	}

	/**
	 * Get the description of the tool.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Run legacy data updates.', 'sensei-lms' );
	}

	/**
	 * Is the tool a single action?
	 *
	 * @return bool
	 */
	public function is_single_action() {
		return false;
	}

	/**
	 * Run the tool.
	 */
	public function run() {
		Sensei()->updates->sensei_updates_page();
	}

}

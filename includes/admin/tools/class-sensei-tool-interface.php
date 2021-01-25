<?php
/**
 * File containing Sensei_Tool_Interface interface.
 *
 * @package sensei-lms
 * @since 3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Tool_Interface interface for all tools.
 *
 * @since 3.7.0
 */
interface Sensei_Tool_Interface {
	/**
	 * Get the ID of the tool.
	 *
	 * @return string
	 */
	public function get_id();

	/**
	 * Get the name of the tool.
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Get the description of the tool.
	 *
	 * @return string
	 */
	public function get_description();

	/**
	 * Process the tool action. Nonce will be checked for non-interactive tools.
	 */
	public function process();

	/**
	 * Is the tool currently available?
	 *
	 * @return bool True if tool is available.
	 */
	public function is_available();
}

<?php
/**
 * File containing the Sensei_Home_Task interface.
 *
 * @package sensei-lms
 * @since 4.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Home_Task interface.
 *
 * @since 4.8.0
 */
interface Sensei_Home_Task {

	/**
	 * The task ID.
	 *
	 * @return string
	 */
	public static function get_id() : string;

	/**
	 * Number used to sort in frontend.
	 *
	 * @return int
	 */
	public function get_priority() : int;

	/**
	 * The task title.
	 *
	 * @return string
	 */
	public function get_title() : string;

	/**
	 * The task url. Actions to be handled in JS can be returned with the custom protocol `sensei://`.
	 *
	 * @return string|null
	 */
	public function get_url() : ?string;

	/**
	 * Whether the task has been completed or not.
	 *
	 * @return bool
	 */
	public function is_completed() : bool;
}

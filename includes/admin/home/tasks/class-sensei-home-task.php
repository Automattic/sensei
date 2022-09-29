<?php
/**
 * File containing the Sensei_Home_Task interface.
 *
 * @package sensei-lms
 * @since $$next-version$$
 */

/**
 * Sensei_Home_Task interface.
 *
 * @since $$next-version$$
 */
interface Sensei_Home_Task {

	/**
	 * The task ID.
	 *
	 * @return string
	 */
	public function get_id() : string;

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
	 * An optional url to an image.
	 *
	 * @return string|null
	 */
	public function get_image() : ?string;

	/**
	 * Whether the task has been completed or not.
	 *
	 * @return bool
	 */
	public function is_completed() : bool;
}

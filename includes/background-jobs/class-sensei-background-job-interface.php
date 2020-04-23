<?php
/**
 * File containing the interface Sensei_Background_Job_Interface.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for async jobs.
 */
interface Sensei_Background_Job_Interface {
	/**
	 * Get the action name for the scheduled job.
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Run the job.
	 */
	public function run();

	/**
	 * After the job runs, check to see if it needs to be re-queued for the next batch.
	 *
	 * @return bool
	 */
	public function is_complete();

	/**
	 * Get the arguments to run with the job.
	 *
	 * @return array
	 */
	public function get_args();
}

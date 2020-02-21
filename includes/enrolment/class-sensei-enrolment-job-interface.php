<?php
/**
 * File containing the interface Sensei_Enrolment_Job_Interface.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for async jobs.
 */
interface Sensei_Enrolment_Job_Interface {
	/**
	 * Get the action name for the scheduled job.
	 *
	 * @return string
	 */
	public static function get_name();

	/**
	 * Run the job and return `true` if the job should be immediately rescheduled (for another batch) or `false`
	 * if the job can be considered complete.
	 *
	 * @return bool
	 */
	public function run();

	/**
	 * Get the arguments to run with the job.
	 *
	 * @return array
	 */
	public function get_args();
}

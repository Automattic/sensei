<?php
/**
 * File containing the Sensei_Data_Port_Task class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class has the shared logic for data port tasks.
 */
abstract class Sensei_Data_Port_Task {
	/**
	 * Data port job for this task.
	 *
	 * @var Sensei_Data_Port_Job
	 */
	private $job;

	/**
	 * Sensei_Data_Port_Task constructor.
	 *
	 * @param Sensei_Data_Port_Job $job Job object for this task.
	 */
	public function __construct( Sensei_Data_Port_Job $job ) {
		$this->job = $job;
	}

	/**
	 * Get the job for this task.
	 *
	 * @return Sensei_Data_Port_Job
	 */
	public function get_job() {
		return $this->job;
	}

	/**
	 * Save the current task's state.
	 */
	public function save_state() {
		// Silence is golden.
	}
}

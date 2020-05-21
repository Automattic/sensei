<?php
/**
 * File containing the Sensei_Import_Job class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class represents a data import job.
 */
class Sensei_Import_Job extends Sensei_Data_Port_Job {

	/**
	 * The array of the import tasks.
	 *
	 * @var Sensei_Data_Port_Task_Interface[]
	 */
	private $tasks;

	/**
	 * Sensei_Import_Job constructor.
	 *
	 * @param string $job_id  The job id.
	 * @param array  $args    Arguments for the import job.
	 * @param string $json    JSON string to restore the state from.
	 */
	public function __construct( $job_id, $args = [], $json = '' ) {
		parent::__construct( $job_id, $args, $json );
		// TODO: Generate the tasks from the arguments and/or from the internal state.
		$this->tasks = [];
	}

	/**
	 * Get the tasks of this import job.
	 *
	 * @return Sensei_Data_Port_Task_Interface[]
	 */
	public function get_tasks() {
		return $this->tasks;
	}

}

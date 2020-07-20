<?php
/**
 * File containing the Sensei_Import_File_Process_Task class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Export content to a CSV file for the given type.
 */
class Sensei_Export_Task
	extends Sensei_Data_Port_Task
	implements Sensei_Data_Port_Task_Interface {

	/**
	 * Sensei_Export_Task constructor.
	 *
	 * @param Sensei_Data_Port_Job $job  The job.
	 * @param string               $type Content type.
	 */
	public function __construct( Sensei_Data_Port_Job $job, $type ) {
		parent::__construct( $job );
	}

	/**
	 * Run export task.
	 */
	public function run() {
	}

	/**
	 * Returns true if the task is completed.
	 *
	 * @return boolean
	 */
	public function is_completed() {
		return true;
	}

	/**
	 * Returns the completion ratio of this task. The ration has the following format:
	 *
	 * {
	 *
	 *     @type integer $completed  Number of completed actions.
	 *     @type integer $total      Number of total actions.
	 * }
	 *
	 * @return array
	 */
	public function get_completion_ratio() {
		return [
			'total'     => 100,
			'completed' => 50,
		];
	}

	/**
	 * Performs any required cleanup of the task.
	 */
	public function clean_up() {

	}

}

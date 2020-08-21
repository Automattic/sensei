<?php
/**
 * File containing the interface Sensei_Data_Port_Task_Interface.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A data port task is a task that is executed as part of a data port job. Each job can define each own set of tasks
 * which will be executed sequentially by the job. A task will not be executed until the previous task is completed.
 */
interface Sensei_Data_Port_Task_Interface {

	/**
	 * Run this task.
	 */
	public function run();

	/**
	 * Returns true if the task is completed.
	 *
	 * @return boolean
	 */
	public function is_completed();

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
	public function get_completion_ratio();

	/**
	 * Save the current task's state.
	 */
	public function save_state();
}

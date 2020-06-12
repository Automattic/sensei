<?php
/**
 * File containing the Sensei_Import_Lessons class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class handles the import task for lessons.
 */
class Sensei_Import_Lessons
	extends Sensei_Import_File_Process_Task
	implements Sensei_Data_Port_Task_Interface {

	/**
	 * Return a unique key for the task.
	 *
	 * @return string
	 */
	public function get_task_key() {
		return 'lessons';
	}

	/**
	 * Get the class name of the model handled by this task.
	 *
	 * @return string
	 */
	public function get_model_class() {
		// @todo Implement.

		return null;
	}

	/**
	 * Performs any required cleanup of the task.
	 */
	public function clean_up() {
		// @todo Implement.
	}

	/**
	 * Validate an uploaded source file before saving it.
	 *
	 * @param string $file_path File path of the file to validate.
	 *
	 * @return true|WP_Error
	 */
	public static function validate_source_file( $file_path ) {
		// @todo Implement.

		return true;
	}
}

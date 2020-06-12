<?php
/**
 * File containing the Sensei_Import_Courses class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class handles the import task for courses.
 */
class Sensei_Import_Courses
	extends Sensei_Import_File_Process_Task
	implements Sensei_Data_Port_Task_Interface {

	/**
	 * Return a unique key for the task.
	 *
	 * @return string
	 */
	public function get_task_key() {
		return 'courses';
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
	 * Process a single CSV line.
	 *
	 * @param int   $line_number  The line number in the file.
	 * @param array $line         The current line as returned from Sensei_Import_CSV_Reader::read_lines().
	 *
	 * @return mixed
	 */
	protected function process_line( $line_number, $line ) {
		return true;
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

<?php
/**
 * File containing the Sensei_Import_Questions class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class handles the import task for questions.
 */
class Sensei_Import_Questions
	extends Sensei_Data_Port_Task
	implements Sensei_Data_Port_Task_Interface {

	/**
	 * Run this task.
	 */
	public function run() {
		// @todo Implement.
	}

	/**
	 * Returns true if the task is completed.
	 *
	 * @return boolean
	 */
	public function is_completed() {
		// @todo Implement.

		return false;
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
		// @todo Implement.

		return [
			'completed' => 0,
			'total'     => 0,
		];
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
		$required_columns = [
			'question',
			'answer',
		];

		$optional_columns = [
			'id',
			'slug',
			'description',
			'status',
			'type',
			'grade',
			'randomise',
			'media',
			'categories',
			'feedback',
			'text before gap',
			'gap',
			'text after gap',
			'upload notes',
			'teacher notes',
		];

		return Sensei_Import_CSV_Reader::validate_csv_file( $file_path, $required_columns, $optional_columns );
	}
}

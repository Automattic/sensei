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
	extends Sensei_Import_File_Process_Task
	implements Sensei_Data_Port_Task_Interface {

	/**
	 * Return a unique key for the task.
	 *
	 * @return string
	 */
	public function get_task_key() {
		return 'questions';
	}

	/**
	 * Get the class name of the model handled by this task.
	 *
	 * @return string
	 */
	public function get_model_class() {
		return Sensei_Import_Question_Model::class;
	}

	/**
	 * Performs any required cleanup of the task.
	 */
	public function clean_up() {
		// Nothing to clean.
	}

	/**
	 * Validate an uploaded source file before saving it.
	 *
	 * @param string $file_path File path of the file to validate.
	 *
	 * @return true|WP_Error
	 */
	public static function validate_source_file( $file_path ) {
		$required_fields = Sensei_Data_Port_Question_Model::get_required_fields();
		$optional_fields = Sensei_Data_Port_Question_Model::get_optional_fields();

		return Sensei_Import_CSV_Reader::validate_csv_file( $file_path, $required_fields, $optional_fields );
	}
}

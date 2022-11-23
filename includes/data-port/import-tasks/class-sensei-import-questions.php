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
	 * Get the model which handles this task.
	 *
	 * @param int   $line_number Line number for model.
	 * @param array $data        An associated array with the CSV line.
	 *
	 * @return Sensei_Import_Question_Model
	 */
	public function get_model( $line_number, $data ) {
		return Sensei_Import_Question_Model::from_source_array( $line_number, $data, new Sensei_Data_Port_Question_Schema(), $this );
	}

	/**
	 * Get the model key for this task.
	 *
	 * @return string
	 */
	public function get_model_key() {
		return Sensei_Import_Question_Model::MODEL_KEY;
	}

	/**
	 * Validate an uploaded source file before saving it.
	 *
	 * @param string $file_path File path of the file to validate.
	 *
	 * @return true|WP_Error
	 */
	public static function validate_source_file( $file_path ) {
		$schema          = new Sensei_Data_Port_Question_Schema();
		$required_fields = $schema->get_required_fields();
		$optional_fields = $schema->get_optional_fields();

		return Sensei_Import_CSV_Reader::validate_csv_file( $file_path, $required_fields, $optional_fields );
	}
}

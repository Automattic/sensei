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
class Sensei_Import_Courses extends Sensei_Import_File_Process_Task {

	/**
	 * Return a unique key for the task.
	 *
	 * @return string
	 */
	public function get_task_key() {
		return 'courses';
	}

	/**
	 * Get the model which handles this task.
	 *
	 * @param array $line  An associated array with the CSV line.
	 *
	 * @return Sensei_Import_Course_Model
	 */
	public function get_model( $line ) {
		return Sensei_Import_Course_Model::from_source_array( $line, new Sensei_Data_Port_Course_Schema(), $this->get_job()->get_user_id() );
	}

	/**
	 * Performs any required cleanup of the task.
	 */
	public function clean_up() {
		// Nothing to do.
	}

	/**
	 * Validate an uploaded source file before saving it.
	 *
	 * @param string $file_path File path of the file to validate.
	 *
	 * @return true|WP_Error
	 */
	public static function validate_source_file( $file_path ) {
		$schema          = new Sensei_Data_Port_Course_Schema();
		$required_fields = $schema->get_required_fields();
		$optional_fields = $schema->get_optional_fields();

		return Sensei_Import_CSV_Reader::validate_csv_file( $file_path, $required_fields, $optional_fields );
	}
}

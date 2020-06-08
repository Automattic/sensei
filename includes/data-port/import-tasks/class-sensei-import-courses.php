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
		if ( is_wp_error( $line ) ) {
			// @todo Mark as failed.
			error_log(print_r($line,true));
			return false;
		}

		error_log('in process line');
		$model = Sensei_Data_Port_Course_Model::from_source_array( $line );
		if ( ! $model->is_valid() ) {
			// @todo Mark as skipped.
			error_log('not valid');
			return false;
		}

		if ( ! $model->sync_post() ) {
			// @todo Mark as failed.

			return false;
		}

		// @todo Mark as successful.

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
		$required_fields = Sensei_Data_Port_Course_Model::get_required_fields();
		$optional_fields = Sensei_Data_Port_Course_Model::get_optional_fields();

		return Sensei_Import_CSV_Reader::validate_csv_file( $file_path, $required_fields, $optional_fields );
	}
}

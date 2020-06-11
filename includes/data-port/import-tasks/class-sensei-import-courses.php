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
		if ( empty( $line ) ) {
			return true;
		}

		if ( is_wp_error( $line ) ) {
			$this->get_job()->add_log_entry(
				$line->get_error_message(),
				Sensei_Data_Port_Job::LOG_LEVEL_ERROR,
				[
					'line' => $line_number,
				]
			);

			return false;
		}

		$model = Sensei_Data_Port_Course_Model::from_source_array( $line, $this->get_job()->get_user_id() );
		if ( ! $model->is_valid() ) {
			$this->get_job()->add_log_entry(
				__( 'A required field is missing or one of the fields is malformed. Line skipped.', 'sensei-lms' ),
				Sensei_Data_Port_Job::LOG_LEVEL_NOTICE,
				[
					'line' => $line_number,
				]
			);

			return false;
		}

		$result = $model->sync_post();
		if ( is_wp_error( $result ) ) {
			$this->get_job()->add_log_entry(
				$result->get_error_message(),
				Sensei_Data_Port_Job::LOG_LEVEL_ERROR,
				[
					'line' => $line_number,
				]
			);

			return false;
		}

		return true;
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
		$required_fields = Sensei_Data_Port_Course_Model::get_required_fields();
		$optional_fields = Sensei_Data_Port_Course_Model::get_optional_fields();

		return Sensei_Import_CSV_Reader::validate_csv_file( $file_path, $required_fields, $optional_fields );
	}
}

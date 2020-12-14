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
	use Sensei_Import_Prerequisite_Trait;

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
	 * @param int   $line_number Line number for model.
	 * @param array $data        An associated array with the CSV line.
	 *
	 * @return Sensei_Import_Course_Model
	 */
	public function get_model( $line_number, $data ) {
		return Sensei_Import_Course_Model::from_source_array( $line_number, $data, new Sensei_Data_Port_Course_Schema(), $this );
	}

	/**
	 * Get the model key for this task.
	 *
	 * @return string
	 */
	public function get_model_key() {
		return Sensei_Import_Course_Model::MODEL_KEY;
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

	/**
	 * Handle matching a prerequisite to a post.
	 *
	 * Note: Used by dynamic callback in `Sensei_Import_File_Process_Task::run_post_process_tasks`.
	 *
	 * @param array $task Prerequisite task arguments.
	 */
	protected function handle_prerequisite( $task ) {
		self::handle_prerequisite_helper(
			$task,
			'_course_prerequisite',
			Sensei_Data_Port_Course_Schema::POST_TYPE,
			Sensei_Import_Course_Model::MODEL_KEY
		);
	}
}

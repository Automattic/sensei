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
class Sensei_Import_Lessons extends Sensei_Import_File_Process_Task {
	use Sensei_Import_Prerequisite_Trait;

	/**
	 * Return a unique key for the task.
	 *
	 * @return string
	 */
	public function get_task_key() {
		return 'lessons';
	}

	/**
	 * Get the model which handles this task.
	 *
	 * @param int   $line_number Line number for model.
	 * @param array $data        An associated array with the CSV line.
	 *
	 * @return Sensei_Import_Lesson_Model
	 */
	public function get_model( $line_number, $data ) {
		return Sensei_Import_Lesson_Model::from_source_array( $line_number, $data, new Sensei_Data_Port_Lesson_Schema(), $this );
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
		$schema          = new Sensei_Data_Port_Lesson_Schema();
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
		$post_id           = (int) $task[0];
		$reference         = sanitize_text_field( $task[1] );
		$reference_post_id = $this->get_job()->translate_import_id( Sensei_Data_Port_Lesson_Schema::POST_TYPE, $reference );

		if (
			! $reference_post_id
			|| (int) $reference_post_id === $post_id
		) {
			$this->get_job()->add_log_entry(
				// translators: Placeholder is reference to another post.
				sprintf( __( 'Unable to set the prerequisite to "%s"', 'sensei-lms' ), $reference ),
				Sensei_Data_Port_Job::LOG_LEVEL_NOTICE,
				[
					'type'    => Sensei_Import_Lesson_Model::MODEL_KEY,
					'post_id' => $post_id,
				]
			);

			return;
		}

		update_post_meta( $post_id, '_lesson_prerequisite', $reference_post_id );
	}

	/**
	 * Add prerequisite task for lesson.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $reference Reference to the prerequisite.
	 */
	public function add_prerequisite_task( $post_id, $reference ) {
		return $this->add_post_process_task(
			'prerequisite',
			[
				$post_id,
				$reference,
			]
		);
	}
}

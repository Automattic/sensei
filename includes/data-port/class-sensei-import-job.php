<?php
/**
 * File containing the Sensei_Import_Job class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class represents a data import job.
 */
class Sensei_Import_Job extends Sensei_Data_Port_Job {

	/**
	 * The array of the import tasks.
	 *
	 * @var Sensei_Data_Port_Task_Interface[]
	 */
	private $tasks;

	/**
	 * Sensei_Import_Job constructor.
	 *
	 * @param string $job_id  The job id.
	 * @param array  $args    Arguments for the import job.
	 * @param string $json    JSON string to restore the state from.
	 */
	public function __construct( $job_id, $args = [], $json = '' ) {
		parent::__construct( $job_id, $args, $json );

		$this->import_task_files();

		// TODO: Generate the tasks from the arguments and/or from the internal state.
		$this->tasks              = [];
		$this->tasks['questions'] = $this->initialize_task( Sensei_Import_Questions::class );
		$this->tasks['lessons']   = $this->initialize_task( Sensei_Import_Lessons::class );
		$this->tasks['courses']   = $this->initialize_task( Sensei_Import_Courses::class );
	}

	/**
	 * Ensure the task files have been included.
	 */
	private function import_task_files() {
		include_once __DIR__ . '/import-tasks/class-sensei-import-questions.php';
		include_once __DIR__ . '/import-tasks/class-sensei-import-courses.php';
		include_once __DIR__ . '/import-tasks/class-sensei-import-lessons.php';
	}

	/**
	 * Get the tasks of this import job.
	 *
	 * @return Sensei_Data_Port_Task_Interface[]
	 */
	public function get_tasks() {
		return $this->tasks;
	}

	/**
	 * Get the configuration for expected files.
	 *
	 * @return array {
	 *    @type array $$component {
	 *        @type callable $validator  Callback to handle validating the file before save (optional).
	 *        @type array    $mime_types Expected mime-types for the file.
	 *    }
	 * }
	 */
	public static function get_file_config() {
		$files = [];

		$csv_mime_types = [
			'csv' => 'text/csv',
			'txt' => 'text/plain',
		];

		$files['questions'] = [
			'validator'  => [ Sensei_Import_Questions::class, 'validate_source_file' ],
			'mime_types' => $csv_mime_types,
		];

		$files['courses'] = [
			'validator'  => [ Sensei_Import_Courses::class, 'validate_source_file' ],
			'mime_types' => $csv_mime_types,
		];

		$files['lessons'] = [
			'validator'  => [ Sensei_Import_Lessons::class, 'validate_source_file' ],
			'mime_types' => $csv_mime_types,
		];

		return $files;
	}
}

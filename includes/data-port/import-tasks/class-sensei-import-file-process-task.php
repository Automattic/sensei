<?php
/**
 * File containing the Sensei_Import_File_Process_Task class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This task reads a CSV file and imports the entities that are included in each line.
 */
abstract class Sensei_Import_File_Process_Task
	extends Sensei_Data_Port_Task
	implements Sensei_Data_Port_Task_Interface {

	/**
	 * True if the task is completed.
	 *
	 * @var bool
	 */
	private $is_completed;

	/**
	 * Number of already completed lines.
	 *
	 * @var int
	 */
	private $completed_lines;

	/**
	 * Number of the total lines of the file.
	 *
	 * @var int
	 */
	private $total_lines;

	/**
	 * The CSV reader.
	 *
	 * @var Sensei_Import_CSV_Reader
	 */
	private $reader;

	/**
	 * Sensei_Import_File_Process_Task constructor.
	 *
	 * @param Sensei_Data_Port_Job $job
	 */
	public function __construct( Sensei_Data_Port_Job $job ) {
		parent::__construct( $job );

		$files = $this->get_job()->get_files();

		if ( ! isset( $files[ $this->get_task_key() ] ) ) {
			$this->is_completed    = true;
			$this->completed_lines = 0;
			$this->total_lines     = 0;
		} else {
			$attachment_id   = $files[ $this->get_task_key() ];
			$task_state      = $this->get_job()->get_state( $this->get_task_key() );
			$completed_lines = isset( $task_state['completed-lines'] ) ? $task_state['completed-lines'] : 0;
			$this->reader    = new Sensei_Import_CSV_Reader( get_attached_file( $attachment_id ), $completed_lines );

			$this->is_completed    = $this->reader->is_completed();
			$this->total_lines     = $this->reader->get_total_lines();
			$this->completed_lines = $completed_lines;
		}
	}

	/**
	 * Run this task.
	 */
	public function run() {
		if ( $this->is_completed() ) {
			return;
		}

		$lines = $this->reader->read_lines();

		$current_line = $this->completed_lines;

		foreach ( $lines as $line ) {
			$this->process_line( $current_line, $line );
			$current_line++;
		}

		$this->completed_lines = $this->reader->get_completed_lines();
		$this->total_lines     = $this->reader->get_total_lines();
		$this->is_completed    = $this->reader->is_completed();

		$this->get_job()->set_state( $this->get_task_key(), [ 'completed-lines' => $this->completed_lines ] );
		$this->get_job()->persist();
	}

	/**
	 * Returns true if the task is completed.
	 *
	 * @return boolean
	 */
	public function is_completed() {
		return $this->is_completed;
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
		return [
			'completed' => $this->completed_lines,
			'total'     => $this->total_lines,
		];
	}

	/**
	 * Return a unique key for the task. This is used as a key in both Sensei_Data_Port_Job::files array and
	 * Sensei_Data_Port_Job::state.
	 *
	 * @return string
	 */
	abstract public function get_task_key();

	/**
	 * Validate an uploaded source file before saving it.
	 *
	 * @param string $file_path File path of the file to validate.
	 *
	 * @return true|WP_Error
	 */
	abstract public static function validate_source_file( $file_path );

	/**
	 * Get the model which will handle the processing for a line.
	 *
	 * @param array $line  An associated array with the CSV line.
	 *
	 * @return Sensei_Data_Port_Model
	 */
	abstract public function get_model( $line );

	/**
	 * Process a single CSV line.
	 *
	 * @param int            $line_number  The line number in the file.
	 * @param WP_Error|array $line         The current line as returned from Sensei_Import_CSV_Reader::read_lines().
	 *
	 * @return mixed
	 */
	protected function process_line( $line_number, $line ) {
		if ( empty( $line ) ) {
			return true;
		}

		if ( $line instanceof WP_Error ) {
			$this->get_job()->add_log_entry(
				$line->get_error_message(),
				Sensei_Data_Port_Job::LOG_LEVEL_ERROR,
				[
					'line' => $line_number,
				]
			);

			return false;
		}

		$model = $this->get_model( $line );
		if ( ! is_a( $model, Sensei_Data_Port_Model::class ) ) {
			return false;
		}

		if ( ! $model->is_valid() ) {
			$this->get_job()->add_log_entry(
				__( 'A required field is missing or one of the fields is malformed. Line skipped.', 'sensei-lms' ),
				Sensei_Data_Port_Job::LOG_LEVEL_NOTICE,
				$model->get_error_data(
					[
						'line' => $line_number,
					]
				)
			);

			return false;
		}

		$result = $model->sync_post();
		if ( $result instanceof WP_Error ) {
			$this->get_job()->add_log_entry(
				$result->get_error_message(),
				Sensei_Data_Port_Job::LOG_LEVEL_ERROR,
				$model->get_error_data(
					[
						'line' => $line_number,
					]
				)
			);

			return false;
		}

		return true;
	}

}

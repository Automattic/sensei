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

	const STATE_COMPLETED_LINES    = 'completed-lines';
	const STATE_POST_PROCESS_TASKS = 'post-process-tasks';
	const POST_PROCESS_BATCH_SIZE  = 50;

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
	 * Post-process tasks.
	 *
	 * @var array
	 */
	private $post_process_tasks;

	/**
	 * Sensei_Import_File_Process_Task constructor.
	 *
	 * @param Sensei_Data_Port_Job $job
	 */
	public function __construct( Sensei_Data_Port_Job $job ) {
		parent::__construct( $job );

		$files = $this->get_job()->get_files();

		if ( ! isset( $files[ $this->get_task_key() ] ) ) {
			$this->is_completed       = true;
			$this->completed_lines    = 0;
			$this->total_lines        = 0;
			$this->post_process_tasks = [];
		} else {
			$attachment_id   = $files[ $this->get_task_key() ];
			$task_state      = $this->get_job()->get_state( $this->get_task_key() );
			$completed_lines = isset( $task_state[ self::STATE_COMPLETED_LINES ] ) ? $task_state[ self::STATE_COMPLETED_LINES ] : 0;

			try {
				$this->reader = new Sensei_Import_CSV_Reader( get_attached_file( $attachment_id ), $completed_lines );
			} catch ( Exception $e ) {
				$this->get_job()->add_log_entry(
					__( 'Uploaded file could not be opened.', 'sensei-lms' ),
					Sensei_Data_Port_Job::LOG_LEVEL_ERROR,
					[
						'type' => $this->get_model_key(),
						'code' => 'sensei_data_port_job_unreadable_file',
					]
				);

				$this->is_completed = true;

				return;
			}

			$this->post_process_tasks = isset( $task_state[ self::STATE_POST_PROCESS_TASKS ] ) ? $task_state[ self::STATE_POST_PROCESS_TASKS ] : [];

			$this->is_completed    = $this->reader->is_completed() && empty( $this->post_process_tasks );
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

		if ( ! $this->reader->is_completed() ) {
			$lines = $this->reader->read_lines();

			$current_line = $this->completed_lines;

			foreach ( $lines as $line_data ) {
				$current_line++;

				$this->process_line( $current_line + 1, $line_data );
			}

			$this->completed_lines = $this->reader->get_completed_lines();
			$this->total_lines     = $this->reader->get_total_lines();
		} elseif ( $this->reader->is_completed() ) {
			// Running this in an else so that post process tasks run in a fresh batch.
			$this->run_post_process_tasks();
		}

		$this->is_completed = $this->reader->is_completed() && empty( $this->post_process_tasks );
	}

	/**
	 * Save the current task's state.
	 */
	public function save_state() {
		$this->get_job()->set_state(
			$this->get_task_key(),
			[
				self::STATE_COMPLETED_LINES    => $this->completed_lines,
				self::STATE_POST_PROCESS_TASKS => $this->post_process_tasks,
			]
		);
	}

	/**
	 * Execute post process tasks.
	 */
	private function run_post_process_tasks() {
		$post_process_batch_left = self::POST_PROCESS_BATCH_SIZE;
		while ( $post_process_batch_left > 0 && ! empty( $this->post_process_tasks ) ) {
			$post_process_batch_left--;
			$tasks          = array_keys( $this->post_process_tasks );
			$next_task      = $tasks[0];
			$next_task_args = array_shift( $this->post_process_tasks[ $next_task ] );

			$task_method = 'handle_' . $next_task;
			$callback    = [ $this, $task_method ];

			call_user_func( $callback, $next_task_args );

			if ( empty( $this->post_process_tasks[ $next_task ] ) ) {
				unset( $this->post_process_tasks[ $next_task ] );
			}
		}
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
	 * Get the model which handles this task.
	 *
	 * @param int   $line_number Line number for model.
	 * @param array $data        An associated array with the CSV line.
	 *
	 * @return Sensei_Import_Model
	 */
	abstract public function get_model( $line_number, $data );

	/**
	 * Get the model key for this task.
	 *
	 * @return string
	 */
	abstract public function get_model_key();

	/**
	 * Process a single CSV line.
	 *
	 * @param int            $line_number  The line number in the file.
	 * @param WP_Error|array $data         The current line as returned from Sensei_Import_CSV_Reader::read_lines().
	 *
	 * @return bool
	 */
	protected function process_line( $line_number, $data ) {
		if ( empty( $data ) ) {
			return true;
		}

		if ( $data instanceof WP_Error ) {
			$this->get_job()->add_log_entry(
				$data->get_error_message(),
				Sensei_Data_Port_Job::LOG_LEVEL_ERROR,
				[
					'type' => $this->get_model_key(),
					'line' => $line_number,
					'code' => $data->get_error_code(),
				]
			);

			return false;
		}

		$model = $this->get_model( $line_number, $data );
		if ( ! is_a( $model, Sensei_Import_Model::class ) ) {
			return false;
		}

		if ( ! $model->is_valid() ) {
			$this->get_job()->add_log_entry(
				__( 'A required field is missing or one of the fields is malformed. Line skipped.', 'sensei-lms' ),
				Sensei_Data_Port_Job::LOG_LEVEL_ERROR,
				$model->get_error_data(
					[
						'line' => $line_number,
						'code' => 'sensei_data_port_required_field_missing',
					]
				)
			);

			$this->get_job()->set_line_result( $model->get_model_key(), $line_number, Sensei_Import_Job::RESULT_ERROR );

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
						'code' => $result->get_error_code(),
					]
				)
			);

			$this->get_job()->set_line_result( $model->get_model_key(), $line_number, Sensei_Import_Job::RESULT_ERROR );

			return false;
		}

		// Add warnings to the job when post sync is ready.
		$model->add_warnings_to_job();

		$this->get_job()->set_line_result( $model->get_model_key(), $line_number, Sensei_Import_Job::RESULT_SUCCESS );

		return true;
	}

	/**
	 * Add a post process task.
	 *
	 * @param string $task Task name. Handler should be a method with the name `handle_{$task}`.
	 * @param array  $args Arguments to pass to the task.
	 */
	public function add_post_process_task( $task, $args ) {
		if ( ! isset( $this->post_process_tasks[ $task ] ) ) {
			$this->post_process_tasks[ $task ] = [];
		}

		$this->post_process_tasks[ $task ][] = $args;
	}
}

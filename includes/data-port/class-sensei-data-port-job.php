<?php
/**
 * File containing the Sensei_Data_Port_Job class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Superclass of data import/export jobs. It provides basic functionality like logging, maintaining state, cleanup and
 * running data port tasks which are registered by subclasses.
 */
abstract class Sensei_Data_Port_Job implements Sensei_Background_Job_Interface, JsonSerializable {
	const OPTION_PREFIX = 'sensei-tools-job-';

	/**
	 * An array which holds the results of the data port job and populated in subclasses.
	 *
	 * @var array
	 */
	protected $results;

	/**
	 * An array which contains the state for each task.
	 *
	 * @var array
	 */
	protected $task_state;

	/**
	 * Unique id for the job.
	 *
	 * @var string
	 */
	private $job_id;

	/**
	 * An array containing log entries (e.g. errors). It has the following format:
	 * {
	 *
	 *     @type array $type {
	 *         @type string $title The post title of the entity this entry applies to.
	 *         @type string $id    The id of the entity this entry applies to.
	 *         @type string $msg   The log message.
	 *     }
	 * }
	 *
	 * @var array
	 */
	private $logs;

	/**
	 * True if the job is completed.
	 *
	 * @var bool
	 */
	private $is_completed;

	/**
	 * True if the internal state has changed and needs to be stored.
	 *
	 * @var bool
	 */
	private $has_changed;

	/**
	 * True if the job has been cleaned up.
	 *
	 * @var bool
	 */
	private $is_deleted;

	/**
	 * Estimate of completion percentage.
	 *
	 * @var float
	 */
	private $percentage;

	/**
	 * Sensei_Data_Port_Job constructor. A data port instance can be created either when a new data port job is
	 * registered or when an existing one is restored from a JSON string.
	 *
	 * @param string $job_id   Unique job id.
	 * @param array  $args     Arguments to be used by subclasses.
	 * @param string $json     A json string to restore internal state from.
	 */
	protected function __construct( $job_id, $args = [], $json = '' ) {
		$this->job_id      = $job_id;
		$this->has_changed = false;
		$this->is_deleted  = false;

		if ( '' !== $json ) {
			$this->restore_from_json( $json );
		} else {
			$this->logs         = [];
			$this->results      = [];
			$this->is_completed = false;
			$this->has_changed  = true;
			$this->task_state   = [];
			$this->percentage   = 0;
		}

		add_action( 'shutdown', [ $this, 'persist' ] );
	}

	/**
	 * Restore a stored job. Returns null if the job does not exist.
	 *
	 * @param string $job_id  The job id.
	 *
	 * @return Sensei_Data_Port_Job|null instance.
	 */
	public static function get( $job_id ) {
		$json = get_option( self::get_option_name( $job_id ), '' );

		if ( empty( $json ) ) {
			return null;
		}

		return new static( $job_id, [], $json );
	}

	/**
	 * Get the results of the job.
	 *
	 * @return array The results.
	 */
	public function get_results() {
		return $this->results;
	}

	/**
	 * Get the logs of the job. The logs are grouped by type. The pagination works on the total number of logs which
	 * means that depending on the arguments, multiple type of logs can be returned.
	 *
	 * @param int $offset  Offset for pagination.
	 * @param int $limit   Limit for pagination.
	 *
	 * @return array The logs.
	 */
	public function get_logs( $offset = 0, $limit = 20 ) {

		$result_logs = [];

		foreach ( $this->logs as $type => $messages ) {

			// Check if the logs should start from this group.
			if ( $offset < count( $messages ) ) {
				$added_logs           = array_slice( $messages, $offset, $limit );
				$offset               = 0;
				$result_logs[ $type ] = $added_logs;

				if ( count( $added_logs ) >= $limit ) {
					return $result_logs;
				} else {
					// Adjust the limit to take into account added logs.
					$limit -= count( $added_logs );
				}
			} else {
				// Adjust the offset for the skipped log entries.
				$offset -= count( $messages );
			}
		}

		return $result_logs;
	}

	/**
	 * Delete any stored state for this job.
	 */
	public function clean_up() {
		foreach ( $this->get_tasks() as $task ) {
			$task->clean_up();
		}

		$this->is_deleted = true;
		delete_option( self::get_option_name( $this->job_id ) );
	}

	/**
	 * Get the completion status of the job.
	 *
	 * @return array
	 */
	public function get_status() {
		return [
			'status'     => $this->is_completed ? 'completed' : 'pending',
			'percentage' => $this->percentage,
		];
	}

	/**
	 * Creates the tasks that consist the data port job.
	 *
	 * @return Sensei_Data_Port_Task_Interface[]
	 */
	abstract public function get_tasks();

	/**
	 * Serialize state to JSON.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			's' => $this->task_state,
			'l' => $this->logs,
			'r' => $this->results,
			'c' => $this->is_completed,
			'p' => $this->percentage,
		];
	}

	/**
	 * Restore state from JSON.
	 *
	 * @param string $json_string The JSON string.
	 */
	private function restore_from_json( $json_string ) {
		$json_arr = json_decode( $json_string, true );

		if ( ! $json_arr ) {
			return;
		}

		$this->task_state   = $json_arr['s'];
		$this->logs         = $json_arr['l'];
		$this->results      = $json_arr['r'];
		$this->is_completed = $json_arr['c'];
		$this->percentage   = $json_arr['p'];
	}

	/**
	 * Add an entry to the logs.
	 *
	 * @param string $post_tile Post title of the entity this log applies to.
	 * @param string $message   Log message.
	 * @param string $type      Post type this message.
	 * @param string $id        Id of the entity this log applies to.
	 */
	protected function add_log_entry( $post_tile, $message, $type, $id = '' ) {
		$this->has_changed = true;

		$entry = [
			'title' => sanitize_text_field( $post_tile ),
			'msg'   => sanitize_text_field( $message ),
		];

		if ( ! empty( $id ) ) {
			$entry['id'] = sanitize_text_field( $id );
		}

		$this->logs[ sanitize_text_field( $type ) ][] = $entry;
	}

	/**
	 * Persist state to the db.
	 *
	 * @access private
	 */
	public function persist() {
		if ( ! $this->is_deleted && $this->has_changed ) {
			update_option( self::get_option_name( $this->job_id ), wp_json_encode( $this ) );
		}

		$this->has_changed = false;
	}

	/**
	 * Run the job.
	 */
	public function run() {
		if ( $this->is_completed ) {
			return;
		}

		$completed_cycles   = 0;
		$total_cycles       = 0;
		$has_processed_task = false;

		foreach ( $this->get_tasks() as $task ) {

			if ( ! $has_processed_task && ! $task->is_completed() ) {
				$task->run();
				$has_processed_task = true;
			}

			$ratio = $task->get_completion_ratio();

			$completed_cycles += $ratio['completed'];
			$total_cycles     += $ratio['total'];
		}

		if ( ! $has_processed_task ) {
			$this->is_completed = true;
			$this->percentage   = 100;
		} else {
			$this->percentage = 100 * $completed_cycles / $total_cycles;
		}
	}

	/**
	 * Returns true if job is completed.
	 *
	 * @return bool True if job is completed.
	 */
	public function is_complete() {
		return $this->is_completed;
	}

	/**
	 * Returns the arguments for data port jobs.
	 *
	 * @return array
	 */
	public function get_args() {
		return [ 'job_id' => $this->job_id ];
	}

	/**
	 * Get the action name for the scheduled job.
	 *
	 * @return string
	 */
	public function get_name() {
		return self::get_option_name( $this->job_id );
	}

	/**
	 * Retrieve the option name for a job.
	 *
	 * @param string $job_id Unique job id.
	 *
	 * @return string The option name.
	 */
	public static function get_option_name( $job_id ) {
		return self::OPTION_PREFIX . $job_id;
	}
}

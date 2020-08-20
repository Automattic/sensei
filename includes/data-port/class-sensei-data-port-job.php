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
	const OPTION_PREFIX         = 'sensei-data-port-job-';
	const SCHEDULED_ACTION_NAME = 'sensei-data-port-job';
	const LOG_LEVEL_INFO        = 0;
	const LOG_LEVEL_NOTICE      = 1;
	const LOG_LEVEL_ERROR       = 2;

	/**
	 * An array which holds the results of the data port job and populated in subclasses.
	 *
	 * @var array
	 */
	protected $results;

	/**
	 * An array which contains job state.
	 *
	 * @var array
	 */
	private $state;

	/**
	 * Unique id for the job.
	 *
	 * @var string
	 */
	private $job_id;

	/**
	 * An array containing log entries (e.g. errors). It has the following format:
	 *
	 * @type array {
	 *     @type string The message of the log entry.
	 *     @type string The log level.
	 *     @type array {
	 *          Log entry's companion data.
	 *
	 *          @type string type         The log type (course, lesson, etc.)
	 *          @type int    line         The line number which this log message refers to.
	 *          @type string entry_id     The import id of the related entry.
	 *          @type string entry_title  The title of the related entry.
	 *          @type int    post_id      The post id of the related entry.
	 *     }
	 * }
	 *
	 * @var array
	 */
	private $logs;

	/**
	 * True if the job is started.
	 *
	 * @var bool
	 */
	private $is_started;

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
	protected $has_changed;

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
	 * User ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Files that have been saved and associated with this job.
	 *
	 * @var array {
	 *     File attachment IDs indexed with the file key.
	 *
	 *     @type int $$file_key Attachment post ID.
	 * }
	 */
	private $files;

	/**
	 * Sensei_Data_Port_Job constructor. A data port instance can be created either when a new data port job is
	 * registered or when an existing one is restored from a JSON string.
	 *
	 * @param string $job_id Unique job id.
	 * @param string $json   A json string to restore internal state from.
	 */
	protected function __construct( $job_id, $json = '' ) {
		$this->job_id      = $job_id;
		$this->has_changed = false;
		$this->is_deleted  = false;

		if ( '' !== $json ) {
			$this->restore_from_json( $json );
		} else {
			$this->logs         = [];
			$this->files        = [];
			$this->is_completed = false;
			$this->is_started   = false;
			$this->has_changed  = true;
			$this->state        = [];
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
		$option_name = self::get_option_name( $job_id );

		/**
		 * It solves an issue when we are working with the `process` endpoint,
		 * along the scheduled job. Sometimes in the requests racing, a request
		 * starts, when the other is setting some option, like the job state.
		 * So it would get the old (cached) option.
		 *
		 * It's noticed more frequently when WooCommerce is not installed, and
		 * the Sensei_Scheduler is using WP Cron.
		 */
		wp_cache_delete( $option_name, 'options' );

		$json = get_option( $option_name, '' );

		if ( empty( $json ) ) {
			return null;
		}

		return new static( $job_id, $json );
	}

	/**
	 * Create a new job.
	 *
	 * @param string $job_id  The job id.
	 * @param int    $user_id The user id.
	 *
	 * @return static
	 */
	public static function create( $job_id, $user_id ) {
		$job = new static( $job_id );
		$job->set_user_id( $user_id );

		return $job;
	}

	/**
	 * Set up a job to start.
	 */
	public function start() {
		$this->has_changed = true;
		$this->is_started  = true;
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
	 * Get the logs of the job.
	 *
	 * @return array The logs.
	 */
	public function get_logs() {
		usort(
			$this->logs,
			function( $first_log, $second_log ) {

				// Get the type ordering and compare the two logs based on that.
				$log_order                = $this->get_log_type_order();
				$first_type_order_exists  = isset( $first_log[2]['type'] ) && in_array( $first_log[2]['type'], $log_order, true );
				$second_type_order_exists = isset( $second_log[2]['type'] ) && in_array( $second_log[2]['type'], $log_order, true );

				$compare_type_result = $this->compare_null_integers(
					$first_type_order_exists ? array_search( $first_log[2]['type'], $log_order, true ) : null,
					$second_type_order_exists ? array_search( $second_log[2]['type'], $log_order, true ) : null
				);

				// If the logs have the same type, compare them based on line number.
				if ( 0 === $compare_type_result ) {
					return $this->compare_null_integers(
						isset( $first_log[2]['line'] ) ? $first_log[2]['line'] : null,
						isset( $second_log[2]['line'] ) ? $second_log[2]['line'] : null
					);
				}

				return $compare_type_result;
			}
		);

		return array_map(
			function ( $log ) {
				return [
					'message' => $log[0],
					'level'   => $log[1],
					'data'    => $log[2],
				];
			},
			$this->logs
		);
	}

	/**
	 * Helper method that compares two integers which are possibly null. If an integer is null it gains precedence.
	 *
	 * @param int $first   The first integer.
	 * @param int $second  The second integer.
	 *
	 * @return int
	 */
	private function compare_null_integers( $first, $second ) {
		if ( $first === $second ) {
			return 0;
		}

		if ( null === $first ) {
			return -1;
		}

		if ( null === $second ) {
			return 1;
		}

		return $first < $second ? -1 : 1;
	}

	/**
	 * Delete any stored state for this job.
	 */
	public function clean_up() {
		foreach ( array_keys( $this->files ) as $file_key ) {
			$this->delete_file( $file_key );
		}

		$this->is_deleted = true;
		delete_option( self::get_option_name( $this->job_id ) );
	}

	/**
	 * Get the job ID.
	 *
	 * @return string
	 */
	public function get_job_id() {
		return $this->job_id;
	}

	/**
	 * Get the completion status of the job.
	 *
	 * @return array
	 */
	public function get_status() {
		$status = 'setup';
		if ( $this->is_started ) {
			$status = $this->is_completed ? 'completed' : 'pending';
		}

		return [
			'status'     => $status,
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
	 * Get the configuration for expected files.
	 *
	 * @return array {
	 *    @type array $$component {
	 *        @type callable $validator  Callback to handle validating the file before save (optional).
	 *        @type array    $mime_types Expected mime-types for the file.
	 *    }
	 * }
	 */
	abstract public static function get_file_config();

	/**
	 * Check if a job is ready to be started.
	 *
	 * @return bool
	 */
	abstract public function is_ready();

	/**
	 * Logs are order by log type first and then by line number. The method defines the ordering by type.
	 *
	 * @return array An array of log types which defines the log order.
	 */
	abstract protected function get_log_type_order();

	/**
	 * Initialize and restore state of task.
	 *
	 * @param string $task_class Class name of task class.
	 *
	 * @return Sensei_Data_Port_Task_Interface
	 */
	protected function initialize_task( $task_class ) {
		return new $task_class( $this );
	}

	/**
	 * Serialize state to JSON.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			's' => $this->state,
			'l' => $this->logs,
			'r' => $this->results,
			'c' => $this->is_completed,
			'i' => $this->is_started,
			'p' => $this->percentage,
			'f' => $this->files,
			'u' => $this->user_id,
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

		$this->state        = $json_arr['s'];
		$this->logs         = $json_arr['l'];
		$this->results      = $json_arr['r'];
		$this->is_completed = $json_arr['c'];
		$this->is_started   = $json_arr['i'];
		$this->percentage   = $json_arr['p'];
		$this->files        = $json_arr['f'];
		$this->user_id      = $json_arr['u'];
	}

	/**
	 * Add an entry to the logs.
	 *
	 * @param string $message Log message.
	 * @param int    $level   Log level (see constants).
	 * @param array  $data    Data to include with the message.
	 */
	public function add_log_entry( $message, $level = self::LOG_LEVEL_INFO, $data = [] ) {
		$this->has_changed = true;

		$this->logs[] = [
			sanitize_text_field( $message ),
			(int) $level,
			$data,
		];
	}

	/**
	 * Persist state to the db.
	 *
	 * @access private
	 */
	public function persist() {
		if ( ! $this->is_deleted && $this->has_changed ) {
			update_option( self::get_option_name( $this->job_id ), wp_json_encode( $this ), false );
		}

		$this->has_changed = false;
	}

	/**
	 * Run the job.
	 */
	public function run() {
		if ( $this->is_complete() || ! $this->is_started() ) {
			return;
		}

		$completed_cycles    = 0;
		$total_cycles        = 0;
		$has_processed_task  = false;
		$has_incomplete_task = false;

		foreach ( $this->get_tasks() as $task ) {

			if ( ! $has_processed_task && ! $task->is_completed() ) {
				$task->run();
				$has_processed_task = true;
			}

			if ( ! $task->is_completed() ) {
				$has_incomplete_task = true;
			}

			$ratio = $task->get_completion_ratio();

			$completed_cycles += $ratio['completed'];
			$total_cycles     += $ratio['total'];

			$task->save_state();
		}

		if ( ! $has_incomplete_task || 0 === $total_cycles ) {
			$this->is_completed = true;
			$this->percentage   = 100;

			/**
			 * Trigger an action when a data port job is complete.
			 *
			 * @since 3.2.0
			 * @hook sensei_data_port_complete
			 *
			 * @param {Sensei_Data_Port_Job} $data_port_job The data port job object.
			 */
			do_action( 'sensei_data_port_complete', $this );
		} else {
			// The calc is based in 90% as maximum when it's not completed yet.
			$this->percentage = 90 * $completed_cycles / $total_cycles;
		}

		$this->has_changed = true;
	}

	/**
	 * Save a file associated with this job. If this is an uploaded file, `is_uploaded_file()` check should
	 * occur prior to this method.
	 *
	 * @param string $file_key  Key for the file being saved.
	 * @param string $tmp_file  Temporary path where the file is stored.
	 * @param string $file_name File name.
	 *
	 * @return true|WP_Error
	 */
	public function save_file( $file_key, $tmp_file, $file_name ) {
		// Make the file path less predictable.
		$stored_file_name = substr( md5( uniqid() ), 0, 8 ) . '_' . $file_name;

		$uploads = wp_upload_dir( gmdate( 'Y/m' ) );
		if ( ! ( $uploads && false === $uploads['error'] ) ) {
			return new WP_Error( 'sensei_data_port_upload_path_unavailable', $uploads['error'] );
		}

		$file_configs = static::get_file_config();

		if ( ! isset( $file_configs[ $file_key ] ) ) {
			return new WP_Error(
				'sensei_data_port_unknown_file_key',
				__( 'Unexpected file key used.', 'sensei-lms' )
			);
		}

		$filename       = wp_unique_filename( $uploads['path'], $stored_file_name );
		$file_save_path = $uploads['path'] . "/$filename";

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Check done with file_exists.
		$move_new_file = @copy( $tmp_file, $file_save_path );

		if ( 0 === strpos( $tmp_file, sys_get_temp_dir() ) ) {
			unlink( $tmp_file );
		}

		if ( ! $move_new_file || ! file_exists( $file_save_path ) ) {
			return new WP_Error( 'sensei_data_port_file_save_failed', __( 'Error saving file.', 'sensei-lms' ) );
		}

		$file_config = $file_configs[ $file_key ];
		$mime_types  = isset( $file_config['mime_types'] ) ? $file_config['mime_types'] : null;

		$file_save_url = $uploads['url'] . "/$filename";
		$wp_filetype   = wp_check_filetype_and_ext( $file_save_path, $file_name, $mime_types );

		// Construct the attachment arguments array.
		$attachment_args = array(
			'post_title'     => $file_name,
			'post_content'   => $file_save_url,
			'post_mime_type' => $wp_filetype['type'],
			'guid'           => $file_save_url,
			'context'        => 'sensei-import',
			'post_status'    => 'private',
		);

		// Save the attachment.
		$id = wp_insert_attachment( $attachment_args, $file_save_path );

		// Make sure to clean up any previous file if it wasn't already removed.
		if ( isset( $this->files[ $file_key ] ) ) {
			$this->delete_file( $file_key );
		}

		$this->files[ $file_key ] = $id;
		$this->has_changed        = true;

		return true;
	}

	/**
	 * Get the files associated with this job.
	 *
	 * @return array
	 */
	public function get_files() {
		return $this->files;
	}

	/**
	 * Get injectable files data.
	 *
	 * @return array
	 */
	public function get_files_data() {
		$data = [];
		foreach ( $this->files as $file_key => $file_post_id ) {
			$file = get_post( $file_post_id );
			if ( ! $file ) {
				continue;
			}

			$data[ $file_key ] = [
				'name' => $file->post_title,
				'url'  => wp_get_attachment_url( $file_post_id ),
			];
		}

		return $data;
	}

	/**
	 * Get the file path for the stored file.
	 *
	 * @param string $file_key Key for the file being saved.
	 *
	 * @return false|string
	 */
	public function get_file_path( $file_key ) {
		if ( ! isset( $this->files[ $file_key ] ) ) {
			return false;
		}

		return get_attached_file( $this->files[ $file_key ] );
	}

	/**
	 * Delete the attachment and file for an associated file.
	 *
	 * @param string $file_key Key for the file being saved.
	 *
	 * @return bool
	 */
	public function delete_file( $file_key ) {
		if ( ! isset( $this->files[ $file_key ] ) ) {
			return false;
		}

		$file_id = $this->files[ $file_key ];

		unset( $this->files[ $file_key ] );
		$this->has_changed = true;

		return wp_delete_attachment( $file_id, true );
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
	 * Returns true if job is started.
	 *
	 * @return bool True if job is started.
	 */
	public function is_started() {
		return $this->is_started;
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
		return self::SCHEDULED_ACTION_NAME;
	}

	/**
	 * Retrieve the option name for a job.
	 *
	 * @param string $job_id Unique job id.
	 *
	 * @return string The option name.
	 */
	private static function get_option_name( $job_id ) {
		return self::OPTION_PREFIX . $job_id;
	}

	/**
	 * Get the state for a specific key.
	 *
	 * @param string $state_key The key of the state.
	 *
	 * @return mixed The state.
	 */
	public function get_state( $state_key ) {
		return isset( $this->state[ $state_key ] ) ? $this->state[ $state_key ] : [];
	}

	/**
	 * Update the state of a specific key.
	 *
	 * @param string $state_key The key of the state.
	 * @param mixed  $state     The state.
	 */
	public function set_state( $state_key, $state ) {
		$this->has_changed         = true;
		$this->state[ $state_key ] = $state;
	}

	/**
	 * Get the user ID.
	 *
	 * @return int
	 */
	public function get_user_id() {
		return $this->user_id;
	}

	/**
	 * Set the user ID.
	 *
	 * @param int $user_id User ID.
	 */
	private function set_user_id( $user_id ) {
		$this->user_id = $user_id;
	}

	/**
	 * Get the descriptor for a log entry.
	 *
	 * @param array $entry Log entry.
	 *
	 * @return string|null
	 */
	public static function get_log_entry_descriptor( $entry ) {
		$data = ! empty( $entry['data'] ) ? $entry['data'] : [];

		$descriptor = [];
		if ( ! empty( $data['entry_title'] ) ) {
			$descriptor[] = $data['entry_title'];
		}

		if ( ! empty( $data['entry_id'] ) ) {
			// translators: Placeholder is ID of entry in source file.
			$descriptor[] = sprintf( __( 'ID: %s', 'sensei-lms' ), $data['entry_id'] );
		}

		if ( empty( $descriptor ) ) {
			return null;
		}

		return implode( '; ', $descriptor );
	}

	/**
	 * Translate error severity level from integer to string.
	 *
	 * @param int $level Integer level.
	 *
	 * @return string
	 */
	public static function translate_log_severity_level( $level ) {
		$map = [
			self::LOG_LEVEL_INFO   => 'info',
			self::LOG_LEVEL_NOTICE => 'notice',
			self::LOG_LEVEL_ERROR  => 'error',
		];

		return isset( $map[ $level ] ) ? $map[ $level ] : 'info';
	}
}

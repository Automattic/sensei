<?php
/**
 * File containing the Sensei_Data_Port_Manager class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class is responsible creating, deleting and keeping track of data port jobs.
 */
class Sensei_Data_Port_Manager implements JsonSerializable {
	const OPTION_NAME           = 'sensei-data-port-jobs';
	const JOB_STALE_AGE_SECONDS = DAY_IN_SECONDS;
	const OPTION_RUNNING_JOB    = 'sensei-data-port-jobs-running';

	/**
	 * An array of all in progress data port jobs. It has the following format:
	 * {
	 *
	 *     @type string $id        Unique id for this job.
	 *     @type int    $user_id   The user which initiatied this job.
	 *     @type int    $time      When the job started.
	 *     @type string $handler   The class which handles this job.
	 * }
	 *
	 * @var array
	 */
	private $data_port_jobs;

	/**
	 * Tracks if the data port jobs have been updated.
	 *
	 * @var boolean
	 */
	private $has_changed;

	/**
	 * Instance of singleton.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Fetches an instance of the class.
	 *
	 * @return self
	 */
	public static function instance() {

		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Sensei_Data_Port_Manager constructor.
	 */
	private function __construct() {
		$json_string       = get_option( self::OPTION_NAME );
		$this->has_changed = false;

		if ( $json_string ) {
			$this->data_port_jobs = json_decode( $json_string, true );
		} else {
			$this->data_port_jobs = [];
		}
	}

	/**
	 * Initializes the data port manager.
	 */
	public function init() {
		add_action( 'init', [ $this, 'maybe_schedule_cron_jobs' ] );
		add_action( 'sensei_data_port_complete', [ $this, 'log_complete_import_jobs' ] );
		add_action( 'sensei_data_port_garbage_collection', [ $this, 'clean_old_jobs' ] );
		add_action( Sensei_Data_Port_Job::SCHEDULED_ACTION_NAME, [ $this, 'run_scheduled_data_port_job' ] );
		add_action( 'shutdown', [ $this, 'persist' ] );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'sensei-import', new Sensei_Import_Job_CLI() );
		}
	}

	/**
	 * Schedule garbage collection event if needed.
	 */
	public function maybe_schedule_cron_jobs() {
		if ( ! wp_next_scheduled( 'sensei_data_port_garbage_collection' ) ) {
			wp_schedule_event( time(), 'daily', 'sensei_data_port_garbage_collection' );
		}
	}

	/**
	 * Clean old jobs.
	 */
	public function clean_old_jobs() {
		foreach ( $this->data_port_jobs as $job ) {
			$age = time() - $job['time'];
			if ( $age > self::JOB_STALE_AGE_SECONDS ) {
				$this->cancel_job( $job['id'] );
			}
		}
	}

	/**
	 * Runs a scheduled data port job.
	 *
	 * @param array $args The arguments of the background job. Only the job_id is included.
	 */
	public function run_scheduled_data_port_job( $args ) {
		if ( empty( $args['job_id'] ) ) {
			return;
		}

		$job = $this->get_job( $args['job_id'] );

		if ( null !== $job ) {
			wp_set_current_user( $job->get_user_id() );
			$this->run_data_port_job( $job );
			wp_set_current_user( 0 );
		}
	}

	/**
	 * Runs a data port job if it's not currently running.
	 *
	 * @param Sensei_Data_Port_Job $job
	 */
	public function run_data_port_job( $job ) {
		$job_running = get_transient( self::OPTION_RUNNING_JOB );
		if ( ! $job_running ) {
			set_transient( self::OPTION_RUNNING_JOB, true, 120 );
			Sensei_Scheduler::instance()->run( $job );
			delete_transient( self::OPTION_RUNNING_JOB );
		} else {
			Sensei_Scheduler::instance()->schedule_job( $job );
		}

	}

	/**
	 * Create a data import job.
	 *
	 * @param int $user_id  The user which started the job.
	 *
	 * @return Sensei_Import_Job
	 */
	public function create_import_job( $user_id ) {
		$job_id = md5( uniqid( '', true ) );

		$this->has_changed      = true;
		$this->data_port_jobs[] = [
			'user_id' => (int) $user_id,
			'time'    => time(),
			'handler' => Sensei_Import_Job::class,
			'id'      => $job_id,
		];

		return Sensei_Import_Job::create( $job_id, (int) $user_id );
	}

	/**
	 * Starts a data port job.
	 *
	 * @param Sensei_Data_Port_Job $job Job object.
	 *
	 * @return bool
	 */
	public function start_job( Sensei_Data_Port_Job $job ) {
		if ( ! $job->is_ready() || $job->is_started() ) {
			return false;
		}

		$this->has_changed = true;

		$job->start();
		Sensei_Scheduler::instance()->schedule_job( $job );

		return true;
	}

	/**
	 * Cancel a job.
	 *
	 * @param string $job_id  The job id.
	 */
	public function cancel_job( $job_id ) {
		$job = $this->get_job( $job_id );

		if ( null !== $job ) {
			$job->clean_up();
		}

		$this->has_changed    = true;
		$this->data_port_jobs = array_filter(
			$this->data_port_jobs,
			function ( $job ) use ( $job_id ) {
				return $job['id'] !== $job_id;
			}
		);
	}

	/**
	 * Cancel all pending jobs.
	 */
	public function cancel_all_jobs() {
		foreach ( $this->data_port_jobs as $job ) {
			if ( is_subclass_of( $job['handler'], 'Sensei_Data_Port_Job', true ) ) {
				$job_instance = $job['handler']::get( $job['id'] );

				if ( null !== $job_instance ) {
					$job_instance->clean_up();
				}
			}
		}

		$this->has_changed    = true;
		$this->data_port_jobs = [];
	}

	/**
	 * Log when an import job is complete.
	 *
	 * @access private
	 *
	 * @param Sensei_Data_Port_Job $job The job object.
	 */
	public function log_complete_import_jobs( Sensei_Data_Port_Job $job ) {
		if ( ! $job instanceof Sensei_Import_Job ) {
			return;
		}

		$results = $job->get_result_counts();

		sensei_log_event(
			'import_complete',
			[
				'imported_courses'   => $results[ Sensei_Import_Course_Model::MODEL_KEY ]['success'] + $results[ Sensei_Import_Course_Model::MODEL_KEY ]['warning'],
				'imported_lessons'   => $results[ Sensei_Import_Lesson_Model::MODEL_KEY ]['success'] + $results[ Sensei_Import_Lesson_Model::MODEL_KEY ]['warning'],
				'imported_questions' => $results[ Sensei_Import_Question_Model::MODEL_KEY ]['success'] + $results[ Sensei_Import_Question_Model::MODEL_KEY ]['warning'],
				'failed_courses'     => $results[ Sensei_Import_Course_Model::MODEL_KEY ]['error'],
				'failed_lessons'     => $results[ Sensei_Import_Lesson_Model::MODEL_KEY ]['error'],
				'failed_questions'   => $results[ Sensei_Import_Question_Model::MODEL_KEY ]['error'],
			]
		);
	}

	/**
	 * Serialize the port jobs to JSON.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return $this->data_port_jobs;
	}

	/**
	 * Persist state to the db.
	 *
	 * @access private
	 */
	public function persist() {
		if ( $this->has_changed ) {
			update_option( self::OPTION_NAME, wp_json_encode( $this ) );
		}

		$this->has_changed = false;
	}

	/**
	 * Get a data port job instance from its id.
	 *
	 * @param string $job_id The job id.
	 *
	 * @return Sensei_Data_Port_Job|null
	 */
	private function get_job( $job_id ) {

		foreach ( $this->data_port_jobs as $job ) {
			if ( $job_id === $job['id'] && is_subclass_of( $job['handler'], 'Sensei_Data_Port_Job', true ) ) {
				return $job['handler']::get( $job['id'] );
			}
		}

		return null;
	}

	/**
	 * Get the active job for a user.
	 *
	 * @param string $handler_class Class for the data port job.
	 * @param int    $user_id       User ID.
	 *
	 * @return Sensei_Data_Port_Job|null
	 */
	public function get_active_job( $handler_class, $user_id ) {

		foreach ( $this->data_port_jobs as $job ) {
			if (
				$handler_class === $job['handler']
				&& is_a( $job['handler'], 'Sensei_Data_Port_Job', true )
				&& (int) $user_id === $job['user_id']
			) {
				$current_job = $job['handler']::get( $job['id'] );

				if ( ! $current_job->is_complete() ) {
					return $current_job;
				}
			}
		}

		return null;
	}

	/**
	 * Get a specific job for a user.
	 *
	 * @param string $handler_class Class for the data port job.
	 * @param string $job_id        Job ID.
	 * @param int    $user_id       User ID.
	 *
	 * @return Sensei_Data_Port_Job|null
	 */
	public function get_job_for_user( $handler_class, $job_id, $user_id ) {

		foreach ( $this->data_port_jobs as $job ) {
			if (
				$handler_class === $job['handler']
				&& is_a( $job['handler'], 'Sensei_Data_Port_Job', true )
				&& (string) $job_id === $job['id']
				&& (int) $user_id === $job['user_id']
			) {
				return $job['handler']::get( $job['id'] );
			}
		}

		return null;
	}
}

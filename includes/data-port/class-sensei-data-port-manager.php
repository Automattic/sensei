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
	const OPTION_NAME = 'sensei-tools-jobs';

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
		foreach ( $this->data_port_jobs as $job ) {
			add_action( Sensei_Data_Port_Job::SCHEDULED_ACTION_NAME, [ $this, 'run_data_port_job' ] );
		}

		add_action( 'shutdown', [ $this, 'persist' ] );
	}

	/**
	 * Runs a data port job.
	 *
	 * @param array $args  The arguments of the background job. Only the job_id is included.
	 */
	public function run_data_port_job( $args ) {

		if ( empty( $args['job_id'] ) ) {
			return;
		}

		$job = $this->get_job( $args['job_id'] );

		if ( null !== $job ) {
			Sensei_Scheduler::instance()->run( $job );
		}
	}

	/**
	 * Starts a data import job.
	 *
	 * @param int $user_id  The user which started the job.
	 */
	public function start_import_job( $user_id ) {
		$job_id = md5( uniqid( '', true ) );

		$this->has_changed      = true;
		$this->data_port_jobs[] = [
			'user_id' => $user_id,
			'time'    => time(),
			'handler' => 'Sensei_Import_Job',
			'id'      => $job_id,
		];

		$job = new Sensei_Import_Job( $job_id );
		Sensei_Scheduler::instance()->schedule_job( $job );
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
			if ( $job['handler'] instanceof \Sensei_Data_Port_Job ) {
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
			if ( $job_id === $job['id'] && is_a( $job['handler'], 'Sensei_Data_Port_Job', true ) ) {
				return $job['handler']::get( $job['id'] );
			}
		}

		return null;
	}
}

<?php
/**
 * File with class for testing scheduled background jobs.
 *
 * @package sensei-tests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Used for testing scheduled jobs.
 *
 * @since 3.0.0
 */
class Sensei_Scheduler_Shim implements Sensei_Scheduler_Interface {
	private static $scheduled_jobs = [];
	private static $action_count   = [];

	/**
	 * Reset the shim.
	 */
	public static function reset() {
		self::$action_count   = [];
		self::$scheduled_jobs = [];
	}

	/**
	 * Get the next scheduled event.
	 *
	 * @param Sensei_Background_Job_Interface $job Job object.
	 *
	 * @return int|false
	 */
	public static function get_next_scheduled( Sensei_Background_Job_Interface $job ) {
		$job_hash = self::get_job_hash( $job );
		if ( empty( self::$scheduled_jobs[ $job_hash ] ) ) {
			return false;
		}

		return self::$scheduled_jobs[ $job_hash ];
	}

	/**
	 * Get the number of times the event has been scheduled.
	 *
	 * @param string $action Action name.
	 *
	 * @return int
	 */
	public static function get_scheduled_action_count( $action ) {
		if ( empty( self::$action_count[ $action ] ) ) {
			return 0;
		}

		return self::$action_count[ $action ];
	}

	/**
	 * Schedule a job to run.
	 *
	 * @param Sensei_Background_Job_Interface $job  Job object.
	 * @param int|null                        $time Time when the job should run. Defaults to now.
	 *
	 * @return mixed
	 */
	public function schedule_job( Sensei_Background_Job_Interface $job, $time = null ) {
		if ( null === $time ) {
			$time = time();
		}

		$job_hash = self::get_job_hash( $job );
		if ( ! isset( self::$scheduled_jobs[ $job_hash ] ) ) {
			self::$scheduled_jobs[ $job_hash ] = $time;

			if ( ! isset( self::$action_count[ $job->get_name() ] ) ) {
				self::$action_count[ $job->get_name() ] = 0;
			}
			self::$action_count[ $job->get_name() ]++;
		}
	}

	/**
	 * Handle running a job and handling its completion lifecycle event.
	 *
	 * @param Sensei_Background_Job_Interface $job                 Job object.
	 * @param callable|null                   $completion_callback Callback to call when job is complete.
	 */
	public function run( Sensei_Background_Job_Interface $job, $completion_callback = null ) {
		$job->run();

		if ( $job->is_complete() ) {
			$this->cancel_scheduled_job( $job );

			if ( is_callable( $completion_callback ) ) {
				call_user_func( $completion_callback );
			}
		}
	}

	/**
	 * Cancel a scheduled job.
	 *
	 * @param Sensei_Background_Job_Interface $job Job to schedule.
	 */
	public function cancel_scheduled_job( Sensei_Background_Job_Interface $job ) {
		$job_hash = self::get_job_hash( $job );
		unset( self::$scheduled_jobs[ $job_hash ] );
	}

	/**
	 * Cancel all jobs.
	 */
	public function cancel_all_jobs() {
		self::$scheduled_jobs = [];
	}

	/**
	 * Get a hash representation of the job.
	 *
	 * @param Sensei_Background_Job_Interface $job Job object
	 *
	 * @return string
	 */
	private static function get_job_hash( Sensei_Background_Job_Interface $job ) {
		return md5(
			wp_json_encode(
				[
					$job->get_name(),
					$job->get_args(),
				]
			)
		);
	}
}

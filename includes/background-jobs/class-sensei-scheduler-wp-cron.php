<?php
/**
 * This file contains Sensei_Scheduler_WP_Cron class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Implementation of Sensei's scheduler using WP Cron.
 *
 * @since 3.0.0
 */
class Sensei_Scheduler_WP_Cron implements Sensei_Scheduler_Interface {
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

		if ( ! wp_next_scheduled( $job->get_name(), [ $job->get_args() ] ) ) {
			wp_schedule_single_event( $time, $job->get_name(), [ $job->get_args() ] );
		}
	}

	/**
	 * Handle running a job and handling its completion lifecycle event.
	 *
	 * @param Sensei_Background_Job_Interface $job                 Job object.
	 * @param callable|null                   $completion_callback Callback to call when job is complete.
	 */
	public function run( Sensei_Background_Job_Interface $job, $completion_callback = null ) {
		$this->schedule_job( $job );
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
		wp_clear_scheduled_hook( $job->get_name(), [ $job->get_args() ] );
	}

	/**
	 * Cancel all jobs.
	 */
	public function cancel_all_jobs() {
		/**
		 * Get a list of background job actions that are handled by this class.
		 *
		 * @since 3.0.0
		 *
		 * @param array $actions Scheduled actions that are handled by this class.
		 */
		$actions = apply_filters( 'sensei_background_job_actions', [] );
		foreach ( $actions as $action_name ) {
			wp_unschedule_hook( $action_name );
		}
	}
}

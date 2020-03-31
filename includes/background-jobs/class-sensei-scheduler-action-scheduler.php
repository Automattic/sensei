<?php
/**
 * This file contains Sensei_Scheduler_Action_Scheduler class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Implementation of Sensei's scheduler using Action Scheduler.
 *
 * @since 3.0.0
 */
class Sensei_Scheduler_Action_Scheduler implements Sensei_Scheduler_Interface {
	const ACTION_SCHEDULER_GROUP = 'sensei-lms';

	/**
	 * Currently running job.
	 *
	 * @var Sensei_Background_Job_Interface
	 */
	private $current_job;

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

		$next_scheduled_action = as_next_scheduled_action( $job->get_name(), [ $job->get_args() ], self::ACTION_SCHEDULER_GROUP );

		if (
			! $next_scheduled_action // Not scheduled.
			|| ( // Currently running.
				$job === $this->current_job
				&& true === $next_scheduled_action
			)
		) {
			as_schedule_single_action( $time, $job->get_name(), [ $job->get_args() ], self::ACTION_SCHEDULER_GROUP );
		}
	}

	/**
	 * Handle running a job and handling its completion lifecycle event.
	 *
	 * @param Sensei_Background_Job_Interface $job                 Job object.
	 * @param callable|null                   $completion_callback Callback to call when job is complete.
	 */
	public function run( Sensei_Background_Job_Interface $job, $completion_callback = null ) {
		$this->current_job = $job;

		$this->schedule_job( $job );

		$job->run();

		if ( $job->is_complete() ) {
			$this->cancel_scheduled_job( $job );

			if ( is_callable( $completion_callback ) ) {
				call_user_func( $completion_callback );
			}
		}

		$this->current_job = null;
	}

	/**
	 * Cancel a scheduled job.
	 *
	 * @param Sensei_Background_Job_Interface $job Job to schedule.
	 */
	public function cancel_scheduled_job( Sensei_Background_Job_Interface $job ) {
		as_unschedule_all_actions( $job->get_name(), [ $job->get_args() ], self::ACTION_SCHEDULER_GROUP );
	}

	/**
	 * Cancel all jobs.
	 */
	public function cancel_all_jobs() {
		as_unschedule_all_actions( null, null, self::ACTION_SCHEDULER_GROUP );
	}
}

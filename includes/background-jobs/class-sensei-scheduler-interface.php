<?php
/**
 * This file contains Sensei_Scheduler_Interface interface.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Defines what a scheduler class should handle.
 *
 * @since 3.0.0
 */
interface Sensei_Scheduler_Interface {
	/**
	 * Schedule a job to run.
	 *
	 * @param Sensei_Background_Job_Interface $job  Job object.
	 * @param int|null                        $time Time when the job should run. Defaults to now.
	 *
	 * @return mixed
	 */
	public function schedule_job( Sensei_Background_Job_Interface $job, $time = null );

	/**
	 * Handle running a job and handling its completion lifecycle event.
	 *
	 * @param Sensei_Background_Job_Interface $job                 Job object.
	 * @param callable|null                   $completion_callback Callback to call when job is complete.
	 */
	public function run( Sensei_Background_Job_Interface $job, $completion_callback = null );

	/**
	 * Cancel a scheduled job.
	 *
	 * @param Sensei_Background_Job_Interface $job Job to schedule.
	 */
	public function cancel_scheduled_job( Sensei_Background_Job_Interface $job );

	/**
	 * Cancel all jobs.
	 */
	public function cancel_all_jobs();
}

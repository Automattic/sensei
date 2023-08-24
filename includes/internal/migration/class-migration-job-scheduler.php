<?php
/**
 * File containing the Migration_Job_Scheduler class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Migration;

use Sensei\Internal\Action_Scheduler\Action_Scheduler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Migration_Job_Scheduler
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Migration_Job_Scheduler {
	/**
	 * Sensei jobs namespace.
	 *
	 * @var string
	 */
	private const HOOK_NAMESPACE = 'sensei_lms_jobs_';

	/**
	 * Migration errors option name.
	 *
	 * @var string
	 */
	public const ERRORS_OPTION_NAME = 'sensei_lms_progress_migration_job_errors';

	/**
	 * Migration job started option name.
	 *
	 * @var string
	 */
	public const STARTED_OPTION_NAME = 'sensei_lms_progress_migration_job_started';

	/**
	 * Migration job completed option name.
	 *
	 * @var string
	 */
	public const COMPLETED_OPTION_NAME = 'sensei_lms_progress_migration_job_completed';

	/**
	 * Action_Scheduler instance.
	 *
	 * @var Action_Scheduler
	 */
	private $action_scheduler;

	/**
	 * Job to schedule.
	 *
	 * @var Migration_Job
	 */
	private $job;

	/**
	 * Migration_Job_Scheduler constructor.
	 *
	 * @param Action_Scheduler $action_scheduler Action_Scheduler instance.
	 * @param Migration_Job    $job              Job to schedule.
	 */
	public function __construct( Action_Scheduler $action_scheduler, Migration_Job $job ) {
		$this->action_scheduler = $action_scheduler;
		$this->job              = $job;
	}

	/**
	 * Initialize the job.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 */
	public function init(): void {
		add_action( $this->get_hook_name(), [ $this, 'run_job' ] );
	}

	/**
	 * Schedule the job.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 */
	public function schedule(): void {
		$action_id = $this->action_scheduler->schedule_single_action(
			$this->get_hook_name(),
			[],
			false
		);
	}

	/**
	 * Run the job.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 */
	public function run_job(): void {
		if ( $this->is_first_run() ) {
			$this->start();
		}

		$this->job->run();

		if ( $this->job->get_errors() ) {
			$migration_errors = get_option( self::ERRORS_OPTION_NAME, [] );
			$migration_errors = array_merge( $migration_errors, $this->job->get_errors() );
			update_option( self::ERRORS_OPTION_NAME, $migration_errors );
		}

		if ( $this->job->is_complete() ) {
			$this->complete();
		} else {
			$this->schedule();
		}
	}

	/**
	 * Get the hook name for the job.
	 *
	 * @return string
	 */
	private function get_hook_name(): string {
		return self::HOOK_NAMESPACE . $this->job->get_job_name();
	}

	/**
	 * Check if this is the first run of the job.
	 *
	 * @return bool
	 */
	private function is_first_run(): bool {
		$started   = get_option( self::STARTED_OPTION_NAME, 0 );
		$completed = get_option( self::COMPLETED_OPTION_NAME, 0 );

		return $started < $completed || 0 === $started;
	}

	/**
	 * Set start time.
	 */
	private function start(): void {
		update_option( self::STARTED_OPTION_NAME, microtime( true ) );
		delete_option( self::COMPLETED_OPTION_NAME );
	}

	/**
	 * Set completion time.
	 */
	private function complete(): void {
		update_option( self::COMPLETED_OPTION_NAME, microtime( true ) );
	}
}

<?php
/**
 * File containing the Migration_Job_Scheduler class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Migration;

use ActionScheduler_Store;
use Sensei\Internal\Action_Scheduler\Action_Scheduler;
use Sensei\Internal\Migration\Migrations\Quiz_Migration;
use Sensei\Internal\Migration\Migrations\Student_Progress_Migration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Migration_Job_Scheduler
 *
 * @internal
 *
 * @since 4.17.0
 */
class Migration_Job_Scheduler {
	/**
	 * Sensei jobs namespace.
	 *
	 * @var string
	 */
	private const HOOK_NAMESPACE = 'sensei_lms_migration_job_';

	/**
	 * Migration errors option name.
	 *
	 * @var string
	 */
	public const ERRORS_OPTION_NAME = 'sensei_lms_migration_job_errors';

	/**
	 * Migration job started option name.
	 *
	 * @var string
	 */
	public const STARTED_OPTION_NAME = 'sensei_lms_migration_job_started';

	/**
	 * Migration job completed option name.
	 *
	 * @var string
	 */
	public const COMPLETED_OPTION_NAME = 'sensei_lms_migration_job_completed';

	/**
	 * Action_Scheduler instance.
	 *
	 * @var Action_Scheduler
	 */
	private $action_scheduler;

	/**
	 * Jobs to schedule.
	 *
	 * @var Migration_Job[]
	 */
	private $jobs = [];

	/**
	 * Migration_Job_Scheduler constructor.
	 *
	 * @param Action_Scheduler $action_scheduler Action_Scheduler instance.
	 */
	public function __construct( Action_Scheduler $action_scheduler ) {
		$this->action_scheduler = $action_scheduler;
	}

	/**
	 * Initialize the migration job scheduler.
	 */
	public function init(): void {
		add_action( 'action_scheduler_unexpected_shutdown', [ $this, 'collect_failed_job_errors' ], 10, 2 );
	}

	/**
	 * Register a job to be scheduled.
	 *
	 * @param Migration_Job $job The migration job.
	 */
	public function register_job( Migration_Job $job ): void {
		$this->jobs[ $job->get_name() ] = $job;

		add_action( $this->get_job_hook_name( $job ), [ $this, 'run_job' ] );
	}

	/**
	 * Schedule all jobs.
	 *
	 * @internal
	 *
	 * @since  4.17.0
	 * @throws \RuntimeException If no jobs to schedule.
	 */
	public function schedule(): void {
		if ( ! $this->jobs ) {
			throw new \RuntimeException( 'No jobs to schedule.' );
		}

		$first_job = reset( $this->jobs );

		$this->schedule_job( $first_job );
	}

	/**
	 * Check if the migration is complete.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @return bool
	 */
	public function is_complete(): bool {
		return (bool) get_option( self::COMPLETED_OPTION_NAME, false );
	}

	/**
	 * Check if the migration is in progress.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @return bool
	 */
	public function is_in_progress(): bool {
		$stared    = (bool) get_option( self::STARTED_OPTION_NAME, false );
		$completed = (bool) get_option( self::COMPLETED_OPTION_NAME, false );
		return $stared && ! $completed;
	}

	/**
	 * Get the migration errors.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @return array
	 */
	public function get_errors(): array {
		return (array) get_option( self::ERRORS_OPTION_NAME, [] );
	}

	/**
	 * Schedule a job.
	 *
	 * @param Migration_Job $job The migration job.
	 */
	private function schedule_job( Migration_Job $job ): void {
		$this->action_scheduler->schedule_single_action(
			$this->get_job_hook_name( $job ),
			[ 'job_name' => $job->get_name() ],
			false
		);
	}

	/**
	 * Handle unexpected job error and add it to migration errors.
	 *
	 * @internal
	 *
	 * @access private
	 *
	 * @since $$next-version$$
	 * @param string $action_id The action ID.
	 * @param array  $error     The error.
	 */
	public function collect_failed_job_errors( $action_id, $error ) {
		if ( ! $this->is_migrtion_action( $action_id ) ) {
			return;
		}

		$this->add_error( array( $error['message'] ) );
	}

	/**
	 * Check if the action is a migration action.
	 *
	 * @param string $action_id The action ID.
	 * @return bool
	 */
	private function is_migrtion_action( $action_id ): bool {
		// Make sure the action ID is a string.
		$action_id = (string) $action_id;

		$hooks = array();
		foreach ( $this->jobs as $job ) {
			$hooks[] = $this->get_job_hook_name( $job );
		}

		$args = array(
			'status' => 'failed',
		);

		foreach ( $hooks as $hook ) {
			$args['hook'] = $hook;

			$action_ids = $this->action_scheduler->get_scheduled_actions( $args, 'ids' );
			// Make sure the action IDs are strings.
			$action_ids = array_map( 'strval', $action_ids );
			if ( in_array( $action_id, $action_ids, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Run the job.
	 *
	 * @internal
	 *
	 * @since 4.17.0
	 *
	 * @param string $job_name The job name.
	 */
	public function run_job( string $job_name ): void {
		if ( $this->is_first_run() ) {
			$this->start();
		}

		$job = $this->jobs[ $job_name ];

		$job->run();

		if ( $job->get_errors() ) {
			$this->add_error( $job->get_errors() );
		}

		if ( $job->is_complete() ) {
			$next_job = $this->get_next_job( $job );
			if ( $next_job ) {
				$this->schedule_job( $next_job );
			} else {
				$this->complete();
			}
		} else {
			$this->schedule_job( $job );
		}
	}

	/**
	 * Clear migration state.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 */
	public function clear_state(): void {
		delete_option( self::STARTED_OPTION_NAME );
		delete_option( self::COMPLETED_OPTION_NAME );
		delete_option( self::ERRORS_OPTION_NAME );
		delete_option( Quiz_Migration::LAST_COMMENT_ID_OPTION_NAME );
		delete_option( Student_Progress_Migration::LARST_COMMENT_ID_OPTION_NAME );
	}

	/**
	 * Get the next job.
	 *
	 * @param Migration_Job $job The migration job.
	 *
	 * @return Migration_Job|null
	 */
	private function get_next_job( Migration_Job $job ): ?Migration_Job {
		$job_names    = array_keys( $this->jobs );
		$position     = array_search( $job->get_name(), $job_names, true );
		$has_next_job = false !== $position && isset( $job_names[ $position + 1 ] );

		if ( ! $has_next_job ) {
			return null;
		}

		return $this->jobs[ $job_names[ $position + 1 ] ];
	}

	/**
	 * Get the hook name for the job.
	 *
	 * @param Migration_Job $job The migration job.
	 *
	 * @return string
	 */
	private function get_job_hook_name( Migration_Job $job ): string {
		return self::HOOK_NAMESPACE . $job->get_name();
	}

	/**
	 * Add errors to the migration errors.
	 *
	 * @param array $errors The errors to add.
	 */
	private function add_error( array $errors ) {
		$migration_errors = (array) get_option( self::ERRORS_OPTION_NAME, [] );
		$migration_errors = array_merge( $migration_errors, $errors );
		update_option( self::ERRORS_OPTION_NAME, $migration_errors );
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

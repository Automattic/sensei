<?php
/**
 * File containing Migration_Tool class.
 *
 * @package sensei
 * @since 4.16.1
 */

namespace Sensei\Internal\Migration;

use Sensei_Tools;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Migration_Tool class.
 *
 * @since 4.16.1
 */
class Migration_Tool implements \Sensei_Tool_Interface {

	/**
	 * Sensei_Tools instance.
	 *
	 * @var Sensei_Tools
	 */
	private $tools;

	/**
	 * Migration job scheduler.
	 *
	 * @var Migration_Job_Scheduler
	 */
	private $migration_job_scheduler;

	/**
	 * Migration_Tool constructor.
	 *
	 * @param Sensei_Tools            $tools Sensei_Tools instance.
	 * @param Migration_Job_Scheduler $migration_job_scheduler Migration_Job_Scheduler instance.
	 */
	public function __construct( Sensei_Tools $tools, Migration_Job_Scheduler $migration_job_scheduler ) {
		$this->tools                   = $tools;
		$this->migration_job_scheduler = $migration_job_scheduler;
	}

	/**
	 * Initialize the tool.
	 */
	public function init(): void {
		add_filter( 'sensei_tools', [ $this, 'register_tool' ] );
	}

	/**
	 * Register the tool.
	 *
	 * @param array $tools List of tools.
	 *
	 * @return array
	 */
	public function register_tool( $tools ) {
		$tools[] = $this;
		return $tools;
	}

	/**
	 * Get the ID of the tool.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'student-progress-migration';
	}

	/**
	 * Get the name of the tool.
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Migrate comment-based student progress', 'sensei-lms' );
	}

	/**
	 * Get the description of the tool.
	 *
	 * @return string
	 */
	public function get_description() {
		$started   = (float) get_option( Migration_Job_Scheduler::STARTED_OPTION_NAME, 0 );
		$completed = (float) get_option( Migration_Job_Scheduler::COMPLETED_OPTION_NAME, 0 );

		$status = 'None';
		if ( $completed < $started ) {
			$status = 'In progress';
		} elseif ( $completed > $started ) {
			$status = 'Completed';
		}

		$errors = get_option( Migration_Job_Scheduler::ERRORS_OPTION_NAME, [] );

		return sprintf(
			// translators: %1$s: migration status. %2$s: errors.
			__(
				'Migrate comment-based progress to the new table-based progress system. Status: %1$s. Errors: %2$s.',
				'sensei-lms'
			),
			$status,
			count( $errors ) ? implode( ', ', $errors ) : 'No'
		);
	}

	/**
	 * Run the tool.
	 */
	public function process(): void {
		$this->migration_job_scheduler->schedule();

		$message = __( 'Migration scheduled.', 'sensei-lms' );

		$this->tools->add_user_message( $message );
	}

	/**
	 * Is the tool currently available?
	 *
	 * @return bool True if tool is available.
	 */
	public function is_available(): bool {
		return true;
	}
}

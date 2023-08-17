<?php
/**
 * File containing the Migration_Job class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Jobs;

use Sensei\Internal\Installer\Migrations\Student_Progress_Migration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Migration_Job
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Migration_Job {

	/**
	 * Progress migration.
	 *
	 * @var Student_Progress_Migration
	 */
	private $migration;

	/**
	 * Is migration complete.
	 *
	 * @var bool
	 */
	private $is_complete;

	/**
	 * Job name.
	 *
	 * @var string
	 */
	private $job_name;

	/**
	 * Migration_Job constructor.
	 *
	 * @param Student_Progress_Migration $migration Progress migration.
	 */
	public function __construct( Student_Progress_Migration $migration ) {
		$this->migration = $migration;
		$this->job_name  = 'progress_migration';
	}

	/**
	 * Run the job.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 */
	public function run() {
		$rows_inserted     = $this->migration->run( false );
		$this->is_complete = 0 === $rows_inserted;
	}

	/**
	 * Get job errors.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @return array
	 */
	public function get_errors(): array {
		return $this->migration->get_errors();
	}

	/**
	 * Is job complete.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @return bool
	 */
	public function is_complete(): bool {
		return $this->is_complete;
	}

	/**
	 * Get job name.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @return string
	 */
	public function get_job_name(): string {
		return $this->job_name;
	}
}


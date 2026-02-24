<?php
/**
 * File containing the abstract class for migrations.
 *
 * @package sensei
 * @since 4.17.0
 */

namespace Sensei\Internal\Migration;

/**
 * Migration abstract class.
 *
 * @since 4.17.0
 */
abstract class Migration_Abstract {
	/**
	 * The errors that occurred during the migration.
	 *
	 * @var array
	 */
	private $errors = array();

	/**
	 * Time budget in seconds for this migration run.
	 *
	 * @since 4.26.0
	 * @var float|null
	 */
	private $time_budget = null;

	/**
	 * Timestamp when the time budget started.
	 *
	 * @since 4.26.0
	 * @var float|null
	 */
	private $time_budget_start = null;

	/**
	 * Run the migration.
	 *
	 * @since 4.17.0
	 *
	 * @param bool $dry_run Whether to run the migration in dry-run mode.
	 *
	 * @return int The number of rows migrated.
	 */
	abstract public function run( bool $dry_run = true );

	/**
	 * Set the time budget for this migration run.
	 *
	 * @since 4.26.0
	 *
	 * @param float $seconds Maximum seconds this run should take.
	 * @return void
	 */
	public function set_time_budget( float $seconds ): void {
		$this->time_budget       = $seconds;
		$this->time_budget_start = microtime( true );
	}

	/**
	 * Check if the time budget has been exceeded.
	 *
	 * Returns true when 80% of the budget has been consumed.
	 * Returns false if no budget has been set.
	 *
	 * @since 4.26.0
	 *
	 * @return bool
	 */
	public function is_time_exceeded(): bool {
		if ( null === $this->time_budget || null === $this->time_budget_start ) {
			return false;
		}

		$elapsed = microtime( true ) - $this->time_budget_start;
		return $elapsed >= ( $this->time_budget * 0.8 );
	}

	/**
	 * Return the errors that occurred during the migration.
	 *
	 * @since 4.17.0
	 *
	 * @return array
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Add an error message to the errors list unless it's there already.
	 *
	 * @param string $error The error message to add.
	 */
	protected function add_error( string $error ): void {
		if ( ! in_array( $error, $this->errors, true ) ) {
			$this->errors[] = $error;
		}
	}
}

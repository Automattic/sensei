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


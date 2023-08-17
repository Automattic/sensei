<?php
/**
 * File containing the interface \Sensei\Internal\Installer\Migration.
 *
 * @package sensei
 * @since 4.16.1
 */

namespace Sensei\Internal\Installer;

/**
 * Migration interface.
 *
 * @since 4.16.1
 */
interface Migration {
	/**
	 * The targeted plugin version.
	 *
	 * @since 4.16.1
	 *
	 * @return string
	 */
	public function target_version(): string;

	/**
	 * Run the migration.
	 *
	 * @since 4.16.1
	 *
	 * @param bool $dry_run Whether to run the migration in dry-run mode.
	 */
	public function run( bool $dry_run = true );

	/**
	 * Get the migration errors.
	 *
	 * @return array
	 */
	public function get_errors(): array;
}


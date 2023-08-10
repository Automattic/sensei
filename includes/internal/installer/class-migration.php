<?php
/**
 * File containing the interface \Sensei\Internal\Installer\Migration.
 *
 * @package sensei
 * @since $$next-version$$
 */

namespace Sensei\Internal\Installer;

/**
 * Migration interface.
 *
 * @since $$next-version$$
 */
interface Migration {
	/**
	 * The targeted plugin version.
	 *
	 * @since $$next-version$$
	 *
	 * @return string
	 */
	public function target_version(): string;

	/**
	 * Run the migration.
	 *
	 * @since $$next-version$$
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


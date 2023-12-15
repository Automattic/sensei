<?php
/**
 * File containing the Progress_Storage_Settings class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Progress_Storage_Settings.
 *
 * @internal
 *
 * @since 4.20.0
 */
class Progress_Storage_Settings {
	/**
	 * Comment-based storage.
	 *
	 * @var string
	 */
	public const COMMENTS_STORAGE = 'comments';

	/**
	 * Table-based storage.
	 *
	 * @var string
	 */
	public const TABLES_STORAGE = 'custom_tables';

	/**
	 * Get the storage repositories.
	 *
	 * @return array Returns an array of repositories where the key is the repository slug and the value is the description.
	 */
	public static function get_storage_repositories(): array {
		return array(
			self::COMMENTS_STORAGE => __( 'WordPress comments based storage', 'sensei-lms' ),
			self::TABLES_STORAGE   => __( 'High-Performance progress storage (experimental)', 'sensei-lms' ),
		);
	}

	/**
	 * Returns true if the HPPS feature is enabled.
	 *
	 * @return bool
	 */
	public static function is_hpps_enabled(): bool {
		return Sensei()->settings->settings['experimental_progress_storage'] ?? false;
	}

	/**
	 * Returns current storage repository.
	 *
	 * @return string
	 */
	public static function get_current_repository(): string {
		return Sensei()->settings->settings['experimental_progress_storage_repository'] ?? self::COMMENTS_STORAGE;
	}

	/**
	 * Returns true if the comments repository is enabled.
	 *
	 * @return bool
	 */
	public static function is_comments_repository(): bool {
		return self::COMMENTS_STORAGE === self::get_current_repository();
	}

	/**
	 * Returns true if the tables repository is enabled.
	 *
	 * @return bool
	 */
	public static function is_tables_repository(): bool {
		return self::TABLES_STORAGE === self::get_current_repository();
	}

	/**
	 * Returns true if the HPPS synchronization is enabled.
	 *
	 * @return bool
	 */
	public static function is_sync_enabled(): bool {
		return Sensei()->settings->settings['experimental_progress_storage_synchronization'] ?? false;
	}
}

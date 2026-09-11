<?php
/**
 * File containing the Progress_Storage_Configuration class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Immutable resolved progress storage configuration.
 *
 * @internal
 *
 * @since $$next-version$$
 */
final class Progress_Storage_Configuration {
	/**
	 * Comments read backend.
	 *
	 * @var string
	 */
	public const COMMENTS_BACKEND = 'comments';

	/**
	 * Tables read backend.
	 *
	 * @var string
	 */
	public const TABLES_BACKEND = 'tables';

	/**
	 * Whether the HPPS feature is enabled.
	 *
	 * @var bool
	 */
	private $hpps_enabled;

	/**
	 * The authoritative read backend.
	 *
	 * @var string
	 */
	private $read_backend;

	/**
	 * Whether writes are synchronized to both backends.
	 *
	 * @var bool
	 */
	private $dual_write_enabled;

	/**
	 * Constructor.
	 *
	 * @param bool   $hpps_enabled      Whether the HPPS feature is enabled.
	 * @param string $read_backend      The authoritative read backend.
	 * @param bool   $dual_write_enabled Whether writes are synchronized to both backends.
	 */
	private function __construct( bool $hpps_enabled, string $read_backend, bool $dual_write_enabled ) {
		$this->hpps_enabled       = $hpps_enabled;
		$this->read_backend       = $read_backend;
		$this->dual_write_enabled = $dual_write_enabled;
	}

	/**
	 * Resolve a valid configuration from raw settings and compatibility filters.
	 *
	 * Tables reads require both HPPS and dual writes. Invalid configurations fall
	 * back to comments reads and comments-only writes.
	 *
	 * @param array $settings Raw Sensei settings.
	 * @return self
	 */
	public static function resolve( array $settings ): self {
		$hpps_enabled       = true === ( $settings['experimental_progress_storage'] ?? false );
		$dual_write_enabled = $hpps_enabled && true === ( $settings['experimental_progress_storage_synchronization'] ?? false );
		$read_from_tables   = Progress_Storage_Settings::TABLES_STORAGE === ( $settings['experimental_progress_storage_repository'] ?? Progress_Storage_Settings::COMMENTS_STORAGE );

		/**
		 * Filter whether to read student progress from tables.
		 *
		 * @since 4.17.0
		 *
		 * @hook sensei_student_progress_read_from_tables
		 *
		 * @param {bool} $read_from_tables Whether to read student progress from tables.
		 * @return {bool} Whether to read student progress from tables.
		 */
		$read_from_tables = (bool) apply_filters( 'sensei_student_progress_read_from_tables', $read_from_tables );
		$read_backend     = $hpps_enabled && $dual_write_enabled && $read_from_tables
			? self::TABLES_BACKEND
			: self::COMMENTS_BACKEND;

		return new self( $hpps_enabled, $read_backend, $dual_write_enabled );
	}

	/**
	 * Returns whether the HPPS feature is enabled.
	 *
	 * @return bool
	 */
	public function is_hpps_enabled(): bool {
		return $this->hpps_enabled;
	}

	/**
	 * Returns the authoritative read backend.
	 *
	 * @return string
	 */
	public function get_read_backend(): string {
		return $this->read_backend;
	}

	/**
	 * Returns whether tables are authoritative for reads.
	 *
	 * @return bool
	 */
	public function is_reading_from_tables(): bool {
		return self::TABLES_BACKEND === $this->read_backend;
	}

	/**
	 * Returns whether writes are synchronized to both backends.
	 *
	 * @return bool
	 */
	public function is_dual_write_enabled(): bool {
		return $this->dual_write_enabled;
	}
}

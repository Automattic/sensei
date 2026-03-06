<?php
/**
 * File containing the Sensei_HPPS_Query_Interceptor_Registrar class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Sensei_HPPS_Query_Interceptor_Registrar
 *
 * Checks whether HPPS tables mode is active and, if so, instantiates
 * and registers the query interceptor.
 *
 * @since $$next-version$$
 */
class Sensei_HPPS_Query_Interceptor_Registrar {

	/**
	 * Initialize the interceptor if HPPS tables mode is active.
	 *
	 * Should be called during plugin initialization (e.g., from class-sensei.php).
	 *
	 * @since $$next-version$$
	 */
	public static function init(): void {
		if ( ! self::should_intercept() ) {
			return;
		}

		global $wpdb;

		$interceptor = new Sensei_HPPS_Query_Interceptor( $wpdb );
		$interceptor->register();
	}

	/**
	 * Check whether the interceptor should be activated.
	 *
	 * Returns true only when HPPS is enabled and using tables-based storage.
	 *
	 * @since $$next-version$$
	 *
	 * @return bool
	 */
	private static function should_intercept(): bool {
		if ( ! class_exists( 'Sensei\Internal\Services\Progress_Storage_Settings' ) ) {
			return false;
		}

		return \Sensei\Internal\Services\Progress_Storage_Settings::is_hpps_enabled()
			&& \Sensei\Internal\Services\Progress_Storage_Settings::is_tables_repository();
	}
}

<?php
/**
 * File with trait Sensei_HPPS_Helpers.
 *
 * @package sensei-tests
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Using PHPUnit conventions.

use Sensei\Internal\Services\Progress_Storage_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helpers related to the High-Performance Progress Storage feature.
 *
 * @since $$next-version$$
 */
trait Sensei_HPPS_Helpers {
	private function enable_hpps_tables_repository() {
		Sensei()->settings->settings['experimental_progress_storage_repository'] = Progress_Storage_Settings::TABLES_STORAGE;
	}

	private function reset_hpps_repository() {
		Sensei()->settings->settings['experimental_progress_storage_repository'] = Progress_Storage_Settings::COMMENTS_STORAGE;
	}
}

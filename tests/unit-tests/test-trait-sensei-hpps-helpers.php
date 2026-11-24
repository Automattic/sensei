<?php

use Sensei\Internal\Services\Progress_Storage_Configuration;
use Sensei\Internal\Services\Progress_Storage_Settings;

/**
 * Tests for Sensei_HPPS_Helpers.
 */
class Sensei_HPPS_Helpers_Test extends WP_UnitTestCase {
	use Sensei_HPPS_Helpers;

	public function testEnableHppsTablesRepository_Called_SetsResolvedConfigurationToTables() {
		/* Arrange. */
		$original_repository            = Sensei()->settings->settings['experimental_progress_storage_repository'] ?? Progress_Storage_Settings::COMMENTS_STORAGE;
		$original_storage_configuration = Sensei()->progress_storage_configuration;

		/* Act. */
		$this->enable_hpps_tables_repository();
		$actual = Sensei()->progress_storage_configuration->is_table_reading_enabled();
		$this->reset_hpps_repository();
		Sensei()->settings->settings['experimental_progress_storage_repository'] = $original_repository;
		Sensei()->progress_storage_configuration                                 = $original_storage_configuration;

		/* Assert. */
		self::assertTrue( $actual );
	}

	public function testResetHppsRepository_TablesConfigurationGiven_SetsResolvedConfigurationToComments() {
		/* Arrange. */
		$original_repository                     = Sensei()->settings->settings['experimental_progress_storage_repository'] ?? Progress_Storage_Settings::COMMENTS_STORAGE;
		$original_storage_configuration          = Sensei()->progress_storage_configuration;
		Sensei()->progress_storage_configuration = Progress_Storage_Configuration::resolve(
			array(
				'experimental_progress_storage'            => true,
				'experimental_progress_storage_repository' => Progress_Storage_Settings::TABLES_STORAGE,
				'experimental_progress_storage_synchronization' => true,
			)
		);
		$this->enable_hpps_tables_repository();

		/* Act. */
		$this->reset_hpps_repository();
		$actual = Sensei()->progress_storage_configuration->is_table_reading_enabled();

		Sensei()->settings->settings['experimental_progress_storage_repository'] = $original_repository;
		Sensei()->progress_storage_configuration                                 = $original_storage_configuration;

		/* Assert. */
		self::assertFalse( $actual );
	}

	/**
	 * Storage switches update caching and restore its previous setting.
	 *
	 * @covers Sensei_HPPS_Helpers::set_progress_storage_mode
	 * @covers Sensei_HPPS_Helpers::restore_progress_storage_mode
	 * @dataProvider storage_cache_modes
	 */
	public function testSetProgressStorageMode_CacheFlagPrimed_UpdatesAndRestoresCaching( string $mode, string $original_repository, bool $expected ): void {
		/* Arrange. */
		$original_settings = Sensei()->settings->settings;
		Sensei()->settings->settings['experimental_progress_storage_repository'] = $original_repository;
		Progress_Storage_Settings::reset_cache_enabled();
		$original_cache_enabled = Progress_Storage_Settings::is_cache_enabled();

		/* Act. */
		try {
			$this->set_progress_storage_mode( $mode );
			$selected = Progress_Storage_Settings::is_cache_enabled();
			$this->restore_progress_storage_mode();
			$restored = Progress_Storage_Settings::is_cache_enabled();
		} finally {
			$this->restore_progress_storage_mode();
			Sensei()->settings->settings = $original_settings;
			Progress_Storage_Settings::reset_cache_enabled();
		}

		/* Assert. */
		self::assertSame( $expected, $selected, 'Caching should follow the selected storage mode.' );
		self::assertSame( $original_cache_enabled, $restored, 'Restoring storage should restore its caching behavior.' );
	}

	public static function storage_cache_modes(): array {
		return array(
			'tables from comments' => array( 'tables', Progress_Storage_Settings::COMMENTS_STORAGE, true ),
			'comments from tables' => array( 'comments', Progress_Storage_Settings::TABLES_STORAGE, false ),
		);
	}
}

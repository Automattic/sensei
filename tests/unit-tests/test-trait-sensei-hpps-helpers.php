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
		$actual = Sensei()->progress_storage_configuration->is_reading_from_tables();
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
		$actual = Sensei()->progress_storage_configuration->is_reading_from_tables();

		Sensei()->settings->settings['experimental_progress_storage_repository'] = $original_repository;
		Sensei()->progress_storage_configuration                                 = $original_storage_configuration;

		/* Assert. */
		self::assertFalse( $actual );
	}
}

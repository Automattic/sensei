<?php

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Progress_Storage_Configuration;
use Sensei\Internal\Services\Progress_Storage_Settings;

/**
 * Tests for the Progress_Storage_Configuration class.
 *
 * @covers \Sensei\Internal\Services\Progress_Storage_Configuration
 */
class Progress_Storage_Configuration_Test extends \WP_UnitTestCase {

	/**
	 * Tests that all storage setting combinations resolve safely.
	 *
	 * @dataProvider providerResolve_SettingsGiven_ReturnsExpectedConfiguration
	 */
	public function testResolve_SettingsGiven_ReturnsExpectedConfiguration(
		bool $hpps_enabled,
		string $selected_repository,
		bool $synchronization_enabled,
		string $expected_read_backend,
		bool $expected_dual_write_enabled
	): void {
		/* Arrange. */
		$settings = array(
			'experimental_progress_storage'            => $hpps_enabled,
			'experimental_progress_storage_repository' => $selected_repository,
			'experimental_progress_storage_synchronization' => $synchronization_enabled,
		);

		/* Act. */
		$configuration = Progress_Storage_Configuration::resolve( $settings );

		/* Assert. */
		self::assertSame(
			array( $hpps_enabled, $expected_read_backend, $expected_dual_write_enabled ),
			array( $configuration->is_hpps_enabled(), $configuration->get_read_backend(), $configuration->is_dual_write_enabled() )
		);
	}

	public function providerResolve_SettingsGiven_ReturnsExpectedConfiguration(): array {
		return array(
			'hpps disabled, comments selected, synchronization disabled' => array( false, Progress_Storage_Settings::COMMENTS_STORAGE, false, Progress_Storage_Configuration::COMMENTS_BACKEND, false ),
			'hpps disabled, comments selected, synchronization enabled'  => array( false, Progress_Storage_Settings::COMMENTS_STORAGE, true, Progress_Storage_Configuration::COMMENTS_BACKEND, false ),
			'hpps disabled, tables selected, synchronization disabled'   => array( false, Progress_Storage_Settings::TABLES_STORAGE, false, Progress_Storage_Configuration::COMMENTS_BACKEND, false ),
			'hpps disabled, tables selected, synchronization enabled'    => array( false, Progress_Storage_Settings::TABLES_STORAGE, true, Progress_Storage_Configuration::COMMENTS_BACKEND, false ),
			'hpps enabled, comments selected, synchronization disabled'  => array( true, Progress_Storage_Settings::COMMENTS_STORAGE, false, Progress_Storage_Configuration::COMMENTS_BACKEND, false ),
			'hpps enabled, comments selected, synchronization enabled'   => array( true, Progress_Storage_Settings::COMMENTS_STORAGE, true, Progress_Storage_Configuration::COMMENTS_BACKEND, true ),
			'hpps enabled, tables selected, synchronization disabled'    => array( true, Progress_Storage_Settings::TABLES_STORAGE, false, Progress_Storage_Configuration::COMMENTS_BACKEND, false ),
			'hpps enabled, tables selected, synchronization enabled'     => array( true, Progress_Storage_Settings::TABLES_STORAGE, true, Progress_Storage_Configuration::TABLES_BACKEND, true ),
		);
	}

	public function testResolve_SettingsMissing_ReturnsSafeDefaults(): void {
		/* Act. */
		$configuration = Progress_Storage_Configuration::resolve( array() );

		/* Assert. */
		self::assertSame(
			array( false, Progress_Storage_Configuration::COMMENTS_BACKEND, false ),
			array( $configuration->is_hpps_enabled(), $configuration->get_read_backend(), $configuration->is_dual_write_enabled() )
		);
	}

	public function testResolve_UnknownRepositoryGiven_ReturnsCommentsBackend(): void {
		/* Arrange. */
		$settings = array(
			'experimental_progress_storage'            => true,
			'experimental_progress_storage_repository' => 'unknown',
			'experimental_progress_storage_synchronization' => true,
		);

		/* Act. */
		$configuration = Progress_Storage_Configuration::resolve( $settings );

		/* Assert. */
		self::assertSame( Progress_Storage_Configuration::COMMENTS_BACKEND, $configuration->get_read_backend() );
	}

	public function testResolve_FilterDisablesTablesReads_ReturnsCommentsBackend(): void {
		/* Arrange. */
		$settings = array(
			'experimental_progress_storage'            => true,
			'experimental_progress_storage_repository' => Progress_Storage_Settings::TABLES_STORAGE,
			'experimental_progress_storage_synchronization' => true,
		);
		add_filter( 'sensei_student_progress_read_from_tables', '__return_false' );

		/* Act. */
		$configuration = Progress_Storage_Configuration::resolve( $settings );

		/* Assert. */
		self::assertSame( Progress_Storage_Configuration::COMMENTS_BACKEND, $configuration->get_read_backend() );

		/* Cleanup. */
		remove_filter( 'sensei_student_progress_read_from_tables', '__return_false' );
	}

	public function testResolve_FilterEnablesTablesReadsWithSynchronization_ReturnsTablesBackend(): void {
		/* Arrange. */
		$settings = array(
			'experimental_progress_storage'            => true,
			'experimental_progress_storage_repository' => Progress_Storage_Settings::COMMENTS_STORAGE,
			'experimental_progress_storage_synchronization' => true,
		);
		add_filter( 'sensei_student_progress_read_from_tables', '__return_true' );

		/* Act. */
		$configuration = Progress_Storage_Configuration::resolve( $settings );

		/* Assert. */
		self::assertSame( Progress_Storage_Configuration::TABLES_BACKEND, $configuration->get_read_backend() );

		/* Cleanup. */
		remove_filter( 'sensei_student_progress_read_from_tables', '__return_true' );
	}

	public function testResolve_FilterEnablesTablesReadsWithoutSynchronization_ReturnsCommentsBackend(): void {
		/* Arrange. */
		$settings = array(
			'experimental_progress_storage'            => true,
			'experimental_progress_storage_repository' => Progress_Storage_Settings::COMMENTS_STORAGE,
			'experimental_progress_storage_synchronization' => false,
		);
		add_filter( 'sensei_student_progress_read_from_tables', '__return_true' );

		/* Act. */
		$configuration = Progress_Storage_Configuration::resolve( $settings );

		/* Assert. */
		self::assertSame( Progress_Storage_Configuration::COMMENTS_BACKEND, $configuration->get_read_backend() );

		/* Cleanup. */
		remove_filter( 'sensei_student_progress_read_from_tables', '__return_true' );
	}

	public function testResolve_FilterEnablesTablesReadsWithHppsDisabled_ReturnsCommentsBackend(): void {
		/* Arrange. */
		$settings = array(
			'experimental_progress_storage'            => false,
			'experimental_progress_storage_repository' => Progress_Storage_Settings::COMMENTS_STORAGE,
			'experimental_progress_storage_synchronization' => true,
		);
		add_filter( 'sensei_student_progress_read_from_tables', '__return_true' );

		/* Act. */
		$configuration = Progress_Storage_Configuration::resolve( $settings );

		/* Assert. */
		self::assertSame( Progress_Storage_Configuration::COMMENTS_BACKEND, $configuration->get_read_backend() );

		/* Cleanup. */
		remove_filter( 'sensei_student_progress_read_from_tables', '__return_true' );
	}
}

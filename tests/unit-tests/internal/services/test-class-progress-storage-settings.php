<?php

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Progress_Storage_Settings;

/**
 * Class Progress_Storage_Settings_Test.
 *
 * @covers \Sensei\Internal\Services\Progress_Storage_Settings
 */
class Progress_Storage_Settings_Test extends \WP_UnitTestCase {

	public function testGetStorageRepositories_Always_ReturnsArrayWithMatchingKeys(): void {
		/* Act.*/
		$repositories = Progress_Storage_Settings::get_storage_repositories();

		/* Assert. */
		$expected = array(
			Progress_Storage_Settings::COMMENTS_STORAGE,
			Progress_Storage_Settings::TABLES_STORAGE,
		);
		$this->assertSame( $expected, array_keys( $repositories ) );
	}

	public function testIsHppsEnabled_WhenHppsNotSet_ReturnsFalse(): void {
		/* Arrange. */
		unset( Sensei()->settings->settings['experimental_progress_storage'] );

		/* Act. */
		$hpps_enabled = Progress_Storage_Settings::is_hpps_enabled();

		/* Assert. */
		$this->assertFalse( $hpps_enabled );
	}

	/**
	 * Test that the method return the expected value.
	 *
	 * @dataProvider providerIsHppsEnabled_WhenHppsSet_ReturnsSameValue
	 */
	public function testIsHppsEnabled_WhenHppsSet_ReturnsSameValue( $value ): void {
		/* Arrange. */
		Sensei()->settings->settings['experimental_progress_storage'] = $value;

		/* Act. */
		$hpps_enabled = Progress_Storage_Settings::is_hpps_enabled();

		/* Assert. */
		$this->assertSame( $value, $hpps_enabled );
	}

	public function providerIsHppsEnabled_WhenHppsSet_ReturnsSameValue(): array {
		return array(
			'hpps enabled'  => array( true ),
			'hpps disabled' => array( false ),
		);
	}

	public function testGetCurrentRepository_WhenRepositoryNotSet_ReturnsDefault(): void {
		/* Arrange. */
		unset( Sensei()->settings->settings['experimental_progress_storage_repository'] );

		/* Act. */
		$repository = Progress_Storage_Settings::get_current_repository();

		/* Assert. */
		$this->assertSame( Progress_Storage_Settings::COMMENTS_STORAGE, $repository );
	}

	/**
	 * Test that the method return the expected value.
	 *
	 * @dataProvider providerGetCurrentRepository_WhenRepositoryNotSet_ReturnsDefault
	 */
	public function testGetCurrentRepository_WhenRepositorySet_ReturnsSameRepository( $repository ): void {
		/* Arrange. */
		Sensei()->settings->settings['experimental_progress_storage_repository'] = $repository;

		/* Act. */
		$actual = Progress_Storage_Settings::get_current_repository();

		/* Assert. */
		$this->assertSame( $repository, $actual );
	}

	public function providerGetCurrentRepository_WhenRepositoryNotSet_ReturnsDefault(): array {
		return array(
			'comment-based' => array( 'comments' ),
			'table-based'   => array( 'custom_tables' ),
		);
	}

	public function testIsCommentsRepository_WhenRepositoryNotSet_ReturnsTrue(): void {
		/* Arrange. */
		unset( Sensei()->settings->settings['experimental_progress_storage_repository'] );

		/* Act. */
		$actual = Progress_Storage_Settings::is_comments_repository();

		/* Assert. */
		$this->assertTrue( $actual );
	}

	/**
	 * Test that the method return the expected value.
	 *
	 * @dataProvider providerIsCommentsRepository_WhenRepositorySet_ReturnsExpectedValue
	 */
	public function testIsCommentsRepository_WhenRepositorySet_ReturnsExpectedValue( $repository, $expected ): void {
		/* Arrange. */
		Sensei()->settings->settings['experimental_progress_storage_repository'] = $repository;

		/* Act. */
		$actual = Progress_Storage_Settings::is_comments_repository();

		/* Assert. */
		$this->assertSame( $expected, $actual );
	}

	public function providerIsCommentsRepository_WhenRepositorySet_ReturnsExpectedValue(): array {
		return array(
			'comment-based' => array( 'comments', true ),
			'table-based'   => array( 'custom_tables', false ),
		);
	}

	public function testIsTablesRepository_WhenRepositoryNotSet_ReturnsFalse(): void {
		/* Arrange. */
		unset( Sensei()->settings->settings['experimental_progress_storage_repository'] );

		/* Act. */
		$actual = Progress_Storage_Settings::is_tables_repository();

		/* Assert. */
		$this->assertFalse( $actual );
	}

	/**
	 * Test that the method return the expected value.
	 *
	 * @dataProvider providerIsTablesRepository_WhenRepositorySet_ReturnsExpectedValue
	 */
	public function testIsTablesRepository_WhenRepositorySet_ReturnsExpectedValue( $repository, $expected ): void {
		/* Arrange. */
		Sensei()->settings->settings['experimental_progress_storage_repository'] = $repository;

		/* Act. */
		$actual = Progress_Storage_Settings::is_tables_repository();

		/* Assert. */
		$this->assertSame( $expected, $actual );
	}

	public function providerIsTablesRepository_WhenRepositorySet_ReturnsExpectedValue(): array {
		return array(
			'comment-based' => array( 'comments', false ),
			'table-based'   => array( 'custom_tables', true ),
		);
	}

	public function testIsSyncEnabled_WhenSyncNotSet_ReturnsFalse(): void {
		/* Arrange. */
		unset( Sensei()->settings->settings['experimental_progress_storage_synchronization'] );

		/* Act. */
		$sync_enabled = Progress_Storage_Settings::is_sync_enabled();

		/* Assert. */
		$this->assertFalse( $sync_enabled );
	}

	/**
	 * Test that the method return the expected value.
	 *
	 * @dataProvider providerIsSyncEnabled_WhenSyncSet_ReturnsSameValue
	 */
	public function testIsSyncEnabled_WhenSyncSet_ReturnsSameValue( $value ): void {
		/* Arrange. */
		Sensei()->settings->settings['experimental_progress_storage_synchronization'] = $value;

		/* Act. */
		$sync_enabled = Progress_Storage_Settings::is_sync_enabled();

		/* Assert. */
		$this->assertSame( $value, $sync_enabled );
	}

	public function providerIsSyncEnabled_WhenSyncSet_ReturnsSameValue(): array {
		return array(
			'sync enabled'  => array( true ),
			'sync disabled' => array( false ),
		);
	}

	public function testIsCacheEnabled_WhenTablesRepository_ReturnsTrue(): void {
		/* Arrange. */
		Progress_Storage_Settings::reset_cache_enabled();
		Sensei()->settings->settings['experimental_progress_storage_repository'] = 'custom_tables';

		/* Act. */
		$result = Progress_Storage_Settings::is_cache_enabled();

		/* Assert. */
		self::assertTrue( $result );

		/* Cleanup. */
		Progress_Storage_Settings::reset_cache_enabled();
	}

	public function testIsCacheEnabled_WhenCommentsRepository_ReturnsFalse(): void {
		/* Arrange. */
		Progress_Storage_Settings::reset_cache_enabled();
		Sensei()->settings->settings['experimental_progress_storage_repository'] = 'comments';

		/* Act. */
		$result = Progress_Storage_Settings::is_cache_enabled();

		/* Assert. */
		self::assertFalse( $result );

		/* Cleanup. */
		Progress_Storage_Settings::reset_cache_enabled();
	}

	public function testIsCacheEnabled_Memoized_ReturnsSameValueOnSecondCall(): void {
		/* Arrange. */
		Progress_Storage_Settings::reset_cache_enabled();
		add_filter( 'sensei_hpps_cache_enabled', '__return_true' );

		/* Act. */
		$first  = Progress_Storage_Settings::is_cache_enabled();
		$second = Progress_Storage_Settings::is_cache_enabled();

		/* Assert. */
		self::assertTrue( $first );
		self::assertSame( $first, $second );

		/* Cleanup. */
		remove_filter( 'sensei_hpps_cache_enabled', '__return_true' );
		Progress_Storage_Settings::reset_cache_enabled();
	}

	public function testIsCacheEnabled_FilterOverride_RespectsFilter(): void {
		/* Arrange. */
		Progress_Storage_Settings::reset_cache_enabled();
		Sensei()->settings->settings['experimental_progress_storage_repository'] = 'comments';
		add_filter( 'sensei_hpps_cache_enabled', '__return_true' );

		/* Act. */
		$result = Progress_Storage_Settings::is_cache_enabled();

		/* Assert — filter overrides the default. */
		self::assertTrue( $result );

		/* Cleanup. */
		remove_filter( 'sensei_hpps_cache_enabled', '__return_true' );
		Progress_Storage_Settings::reset_cache_enabled();
	}

	public function testResetCacheEnabled_AfterMemoization_AllowsRecomputation(): void {
		/* Arrange. */
		Progress_Storage_Settings::reset_cache_enabled();
		add_filter( 'sensei_hpps_cache_enabled', '__return_true' );
		Progress_Storage_Settings::is_cache_enabled();
		remove_filter( 'sensei_hpps_cache_enabled', '__return_true' );

		/* Act. */
		Progress_Storage_Settings::reset_cache_enabled();
		add_filter( 'sensei_hpps_cache_enabled', '__return_false' );
		$result = Progress_Storage_Settings::is_cache_enabled();

		/* Assert. */
		self::assertFalse( $result );

		/* Cleanup. */
		remove_filter( 'sensei_hpps_cache_enabled', '__return_false' );
		Progress_Storage_Settings::reset_cache_enabled();
	}
}

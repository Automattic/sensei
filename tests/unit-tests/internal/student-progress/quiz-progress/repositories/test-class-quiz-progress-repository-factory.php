<?php

namespace SenseiTest\Internal\Student_Progress\Repositories;

use Sensei\Internal\Services\Progress_Storage_Configuration;
use Sensei\Internal\Services\Progress_Storage_Settings;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Comment_Reading_Aggregate_Quiz_Progress_Repository;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Comments_Based_Quiz_Progress_Repository;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Factory;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Table_Reading_Aggregate_Quiz_Progress_Repository;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Tables_Based_Quiz_Progress_Repository;

/**
 * Tests for the Quiz_Progress_Repository_Factory class.
 *
 * @covers \Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Factory
 */
class Quiz_Progress_Repository_Factory_Test extends \WP_UnitTestCase {

	/**
	 * Tests that the factory creates the correct repository.
	 *
	 * @dataProvider providerCreate_WhenCalled_ReturnsQuizProgressRepository
	 */
	public function testCreate_WhenCalled_ReturnsQuizProgressRepository( bool $hpps_enabled, string $read_backend, bool $dual_write_enabled, string $expected ): void {
		/* Arrange. */
		$configuration = Progress_Storage_Configuration::resolve(
			array(
				'experimental_progress_storage'            => $hpps_enabled,
				'experimental_progress_storage_repository' => $read_backend,
				'experimental_progress_storage_synchronization' => $dual_write_enabled,
			)
		);
		$factory       = new Quiz_Progress_Repository_Factory( $configuration );

		/* Act. */
		$actual_repository = $factory->create();

		/* Assert. */
		$this->assertInstanceOf( $expected, $actual_repository );
	}

	public function providerCreate_WhenCalled_ReturnsQuizProgressRepository(): array {
		return array(
			'hpps enabled, comments reads, dual writes enabled' => array(
				true,
				Progress_Storage_Settings::COMMENTS_STORAGE,
				true,
				Comment_Reading_Aggregate_Quiz_Progress_Repository::class,
			),
			'hpps enabled, tables reads, dual writes enabled' => array(
				true,
				Progress_Storage_Settings::TABLES_STORAGE,
				true,
				Table_Reading_Aggregate_Quiz_Progress_Repository::class,
			),
			'hpps disabled, tables selected, synchronization enabled' => array(
				false,
				Progress_Storage_Settings::TABLES_STORAGE,
				true,
				Comments_Based_Quiz_Progress_Repository::class,
			),
			'hpps enabled, tables selected, synchronization disabled' => array(
				true,
				Progress_Storage_Settings::TABLES_STORAGE,
				false,
				Comments_Based_Quiz_Progress_Repository::class,
			),
		);
	}

	public function testCreateTablesBasedRepository_Always_ReturnsTablesBasedRepository(): void {
		/* Arrange. */
		$configuration = Progress_Storage_Configuration::resolve(
			array(
				'experimental_progress_storage'            => true,
				'experimental_progress_storage_repository' => Progress_Storage_Settings::TABLES_STORAGE,
				'experimental_progress_storage_synchronization' => true,
			)
		);
		$factory       = new Quiz_Progress_Repository_Factory( $configuration );

		/* Act. */
		$actual_repository = $factory->create_tables_based_repository();

		/* Assert. */
		$this->assertInstanceOf( Tables_Based_Quiz_Progress_Repository::class, $actual_repository );
	}
}

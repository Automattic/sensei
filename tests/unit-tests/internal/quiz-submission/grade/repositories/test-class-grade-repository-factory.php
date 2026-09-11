<?php

namespace SenseiTest\Internal\Quiz_Submission\Grade\Repositories;

use Sensei\Internal\Services\Progress_Storage_Configuration;
use Sensei\Internal\Services\Progress_Storage_Settings;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Comment_Reading_Aggregate_Grade_Repository;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Comments_Based_Grade_Repository;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Grade_Repository_Factory;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Table_Reading_Aggregate_Grade_Repository;

/**
 * Class Grade_Repository_Factory_Test
 *
 * @covers \Sensei\Internal\Quiz_Submission\Grade\Repositories\Grade_Repository_Factory
 */
class Grade_Repository_Factory_Test extends \WP_UnitTestCase {

	/**
	 * Tests that the factory creates the correct repository.
	 *
	 * @dataProvider providerCreate_WhenCalled_ReturnsGradeRepository
	 */
	public function testCreate_WhenCalled_ReturnsGradeRepository( bool $hpps_enabled, string $read_backend, bool $dual_write_enabled, string $expected ): void {
		/* Arrange. */
		$configuration = Progress_Storage_Configuration::resolve(
			array(
				'experimental_progress_storage'            => $hpps_enabled,
				'experimental_progress_storage_repository' => $read_backend,
				'experimental_progress_storage_synchronization' => $dual_write_enabled,
			)
		);
		$factory       = new Grade_Repository_Factory( $configuration );

		/* Act. */
		$actual = $factory->create();

		/* Assert. */
		self::assertInstanceOf( $expected, $actual );
	}

	public function providerCreate_WhenCalled_ReturnsGradeRepository(): array {
		return array(
			'hpps disabled, tables selected, synchronization enabled' => array( false, Progress_Storage_Settings::TABLES_STORAGE, true, Comments_Based_Grade_Repository::class ),
			'hpps enabled, tables selected, synchronization disabled' => array( true, Progress_Storage_Settings::TABLES_STORAGE, false, Comments_Based_Grade_Repository::class ),
			'hpps enabled, comments reads, dual writes enabled'       => array( true, Progress_Storage_Settings::COMMENTS_STORAGE, true, Comment_Reading_Aggregate_Grade_Repository::class ),
			'hpps enabled, tables reads, dual writes enabled'         => array( true, Progress_Storage_Settings::TABLES_STORAGE, true, Table_Reading_Aggregate_Grade_Repository::class ),
		);
	}
}

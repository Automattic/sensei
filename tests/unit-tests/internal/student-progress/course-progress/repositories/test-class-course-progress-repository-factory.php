<?php

namespace SenseiTest\Internal\Student_Progress\Course_Progress\Repositories;

use Sensei\Internal\Services\Progress_Storage_Configuration;
use Sensei\Internal\Services\Progress_Storage_Settings;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Aggregate_Course_Progress_Repository;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Comment_Reading_Aggregate_Course_Progress_Repository;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Comments_Based_Course_Progress_Repository;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Factory;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Table_Reading_Aggregate_Course_Progress_Repository;

/**
 * Class Course_Progress_Repository_Factory_Test
 *
 * @covers \Sensei\Internal\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Factory
 */
class Course_Progress_Repository_Factory_Test extends \WP_UnitTestCase {
	/**
	 * Tests that the factory creates the correct repository.
	 *
	 * @dataProvider providerCreate_WhenCalled_ReturnsCourseProgressRepository
	 */
	public function testCreate_WhenCalled_ReturnsCourseProgressRepository( bool $hpps_enabled, string $read_backend, bool $dual_write_enabled, string $expected ): void {
		/* Arrange. */
		$configuration = Progress_Storage_Configuration::resolve(
			array(
				'experimental_progress_storage'            => $hpps_enabled,
				'experimental_progress_storage_repository' => $read_backend,
				'experimental_progress_storage_synchronization' => $dual_write_enabled,
			)
		);
		$factory       = new Course_Progress_Repository_Factory( $configuration );

		/* Act. */
		$actual = $factory->create();

		/* Assert. */
		self::assertInstanceOf( $expected, $actual );
	}

	public function providerCreate_WhenCalled_ReturnsCourseProgressRepository(): array {
		return array(
			'hpps enabled, comments reads, dual writes enabled' => array(
				true,
				Progress_Storage_Settings::COMMENTS_STORAGE,
				true,
				Comment_Reading_Aggregate_Course_Progress_Repository::class,
			),
			'hpps enabled, tables reads, dual writes enabled'   => array(
				true,
				Progress_Storage_Settings::TABLES_STORAGE,
				true,
				Table_Reading_Aggregate_Course_Progress_Repository::class,
			),
			'hpps disabled, tables selected, synchronization enabled' => array(
				false,
				Progress_Storage_Settings::TABLES_STORAGE,
				true,
				Comments_Based_Course_Progress_Repository::class,
			),
			'hpps enabled, tables selected, synchronization disabled' => array(
				true,
				Progress_Storage_Settings::TABLES_STORAGE,
				false,
				Comments_Based_Course_Progress_Repository::class,
			),
		);
	}
}

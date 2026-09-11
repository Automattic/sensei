<?php

namespace SenseiTest\Internal\Student_Progress\Lesson_Progress\Repositories;

use Sensei\Internal\Services\Progress_Storage_Configuration;
use Sensei\Internal\Services\Progress_Storage_Settings;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comment_Reading_Aggregate_Lesson_Progress_Repository;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comments_Based_Lesson_Progress_Repository;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Factory;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Table_Reading_Aggregate_Lesson_Progress_Repository;

/**
 * Tests for the Lesson_Progress_Repository_Factory class.
 *
 * @covers \Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Factory
 */
class Lesson_Progress_Repository_Factory_Test extends \WP_UnitTestCase {
	/**
	 * Tests that the factory creates the correct repository.
	 *
	 * @dataProvider providerCreate_WhenCalled_ReturnsLessonProgressRepository
	 */
	public function testCreate_WhenCalled_ReturnsLessonProgressRepository( bool $hpps_enabled, string $read_backend, bool $dual_write_enabled, string $expected ): void {
		/* Arrange. */
		$configuration = Progress_Storage_Configuration::resolve(
			array(
				'experimental_progress_storage'            => $hpps_enabled,
				'experimental_progress_storage_repository' => $read_backend,
				'experimental_progress_storage_synchronization' => $dual_write_enabled,
			)
		);
		$factory       = new Lesson_Progress_Repository_Factory( $configuration );

		/* Act. */
		$actual_repository = $factory->create();

		/* Assert. */
		$this->assertInstanceOf( $expected, $actual_repository );
	}

	public function providerCreate_WhenCalled_ReturnsLessonProgressRepository(): array {
		return array(
			'hpps enabled, tables reads, dual writes enabled' => array(
				true,
				Progress_Storage_Settings::TABLES_STORAGE,
				true,
				Table_Reading_Aggregate_Lesson_Progress_Repository::class,
			),
			'hpps enabled, comments reads, dual writes enabled' => array(
				true,
				Progress_Storage_Settings::COMMENTS_STORAGE,
				true,
				Comment_Reading_Aggregate_Lesson_Progress_Repository::class,
			),
			'hpps disabled, tables selected, synchronization enabled' => array(
				false,
				Progress_Storage_Settings::TABLES_STORAGE,
				true,
				Comments_Based_Lesson_Progress_Repository::class,
			),
			'hpps enabled, tables selected, synchronization disabled' => array(
				true,
				Progress_Storage_Settings::TABLES_STORAGE,
				false,
				Comments_Based_Lesson_Progress_Repository::class,
			),
		);
	}
}

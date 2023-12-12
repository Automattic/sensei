<?php

namespace SenseiTest\Internal\Student_Progress\Lesson_Progress\Repositories;

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
	public function testCreate_WhenCalled_ReturnsLessonProgressRepository( bool $tables_enabled, bool $read_tables, string $expected ): void {
		/* Arrange. */
		$factory = new Lesson_Progress_Repository_Factory( $tables_enabled, $read_tables );

		/* Act. */
		$actual_repository = $factory->create();

		/* Assert. */
		$this->assertInstanceOf( $expected, $actual_repository );
	}

	public function providerCreate_WhenCalled_ReturnsLessonProgressRepository(): array {
		return [
			'tables feature enabled, read enabled'   => array(
				true,
				true,
				Table_Reading_Aggregate_Lesson_Progress_Repository::class,
			),
			'tables feature enabled, read disabled'  => array(
				true,
				false,
				Comment_Reading_Aggregate_Lesson_Progress_Repository::class,
			),
			'tables feature disabled, read enabled'  => array(
				false,
				true,
				Comments_Based_Lesson_Progress_Repository::class,
			),
			'tables feature disabled, read disabled' => array(
				false,
				false,
				Comments_Based_Lesson_Progress_Repository::class,
			),
		];
	}
}

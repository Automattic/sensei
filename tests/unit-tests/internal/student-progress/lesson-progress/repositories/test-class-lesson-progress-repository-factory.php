<?php

namespace SenseiTest\Internal\Student_Progress\Lesson_Progress\Repositories;

use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Aggregate_Lesson_Progress_Repository;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Factory;

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
	public function testCreate_WhenCalled_ReturnsLessonProgressRepository( bool $use_tables ): void {
		/* Arrange. */
		$factory = new Lesson_Progress_Repository_Factory( $use_tables );

		/* Act. */
		$actual_repository = $factory->create();

		/* Assert. */
		$this->assertInstanceOf( Aggregate_Lesson_Progress_Repository::class, $actual_repository );
	}

	public function providerCreate_WhenCalled_ReturnsLessonProgressRepository(): array {
		return [
			'use tables'        => [ true ],
			'do not use tables' => [ false ],
		];
	}
}

<?php

namespace SenseiTest\Student_Progress\Repositories;

use Sensei\Student_Progress\Repositories\Lesson_Progress_Comments_Repository;
use Sensei\Student_Progress\Repositories\Lesson_Progress_Repository_Factory;

/**
 * Tests for the Lesson_Progress_Repository_Factory class.
 *
 * @covers \Sensei\Student_Progress\Repositories\Lesson_Progress_Repository_Factory
 */
class Lesson_Progress_Repository_Factory_Test extends \WP_UnitTestCase {
	public function testCreate_WhenCalled_ReturnsLessonProgressRepository(): void {
		/* Arrange. */
		$factory = new Lesson_Progress_Repository_Factory();

		/* Act. */
		$actual_repository = $factory->create();

		/* Assert. */
		$this->assertInstanceOf( Lesson_Progress_Comments_Repository::class, $actual_repository );
	}
}

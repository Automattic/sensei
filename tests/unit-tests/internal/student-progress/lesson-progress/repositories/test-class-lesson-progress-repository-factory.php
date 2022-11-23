<?php

namespace SenseiTest\Internal\Student_Progress\Lesson_Progress\Repositories;

use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comments_Based_Lesson_Progress_Repository;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Factory;

/**
 * Tests for the Lesson_Progress_Repository_Factory class.
 *
 * @covers \Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Factory
 */
class Lesson_Progress_Repository_Factory_Test extends \WP_UnitTestCase {
	public function testCreate_WhenCalled_ReturnsLessonProgressRepository(): void {
		/* Arrange. */
		$factory = new Lesson_Progress_Repository_Factory();

		/* Act. */
		$actual_repository = $factory->create();

		/* Assert. */
		$this->assertInstanceOf( Comments_Based_Lesson_Progress_Repository::class, $actual_repository );
	}
}

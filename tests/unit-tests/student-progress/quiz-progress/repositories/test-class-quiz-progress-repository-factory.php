<?php

namespace SenseiTest\Student_Progress\Repositories;

use Sensei\Student_Progress\Quiz_Progress\Repositories\Comments_Based_Quiz_Progress_Repository;
use Sensei\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Factory;

/**
 * Tests for the Quiz_Progress_Repository_Factory class.
 * @covers \Sensei\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Factory
 */
class Quiz_Progress_Repository_Factory_Test extends \WP_UnitTestCase {

	public function testCreate_WhenCalled_ReturnsQuizProgressRepository(): void {
		/* Arrange. */
		$factory = new Quiz_Progress_Repository_Factory();

		/* Act. */
		$actual_repository = $factory->create();

		/* Assert. */
		$this->assertInstanceOf( Comments_Based_Quiz_Progress_Repository::class, $actual_repository );
	}
}

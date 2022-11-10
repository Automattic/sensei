<?php

namespace SenseiTest\Internal\Student_Progress\Repositories;

use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Aggregate_Quiz_Progress_Repository;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Factory;

/**
 * Tests for the Quiz_Progress_Repository_Factory class.
 *
 * @covers \Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Factory
 */
class Quiz_Progress_Repository_Factory_Test extends \WP_UnitTestCase {
	public function testCreate_WhenCalled_ReturnsQuizProgressRepository(): void {
		/* Arrange. */
		$factory = new Quiz_Progress_Repository_Factory();

		/* Act. */
		$actual_repository = $factory->create();

		/* Assert. */
		$this->assertInstanceOf( Aggregate_Quiz_Progress_Repository::class, $actual_repository );
	}
}

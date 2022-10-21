<?php

namespace SenseiTest\Internal\Student_Progress\Course_Progress\Repositories;

use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Comments_Based_Course_Progress_Repository;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Factory;

/**
 * Class Course_Progress_Repository_Factory_Test
 *
 * @covers \Sensei\Internal\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Factory
 */
class Course_Progress_Repository_Factory_Test extends \WP_UnitTestCase {

	public function testCreate_WhenCalled_ReturnsCourseProgressRepository(): void {
		/* Arrange. */
		$factory = new Course_Progress_Repository_Factory();

		/* Act. */
		$actual = $factory->create();

		/* Assert. */
		self::assertInstanceOf( Comments_Based_Course_Progress_Repository::class, $actual );
	}

}

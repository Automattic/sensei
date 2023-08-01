<?php

namespace SenseiTest\Internal\Student_Progress\Course_Progress\Repositories;

use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Aggregate_Course_Progress_Repository;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Factory;

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
	public function testCreate_WhenCalled_ReturnsCourseProgressRepository( bool $use_tables ): void {
		/* Arrange. */
		$factory = new Course_Progress_Repository_Factory( $use_tables );

		/* Act. */
		$actual = $factory->create();

		/* Assert. */
		self::assertInstanceOf( Aggregate_Course_Progress_Repository::class, $actual );
	}

	public function providerCreate_WhenCalled_ReturnsCourseProgressRepository(): array {
		return [
			'use tables'        => [ true ],
			'do not use tables' => [ false ],
		];
	}
}

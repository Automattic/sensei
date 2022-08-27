<?php

namespace SenseiTest\Student_Progress\Repositories;

use Sensei\Student_Progress\Models\Course_Progress_Comments;
use Sensei\Student_Progress\Models\Course_Progress_Tables;
use Sensei\Student_Progress\Repositories\Course_Progress_Comments_Repository;
use Sensei\Student_Progress\Repositories\Course_Progress_Repository_Aggregate;
use Sensei\Student_Progress\Repositories\Course_Progress_Repository_Factory;
use Sensei\Student_Progress\Repositories\Course_Progress_Tables_Repository;


/**
 * Class Course_Progress_Repository_Factory_Test
 *
 * @covers \Sensei\Student_Progress\Repositories\Course_Progress_Repository_Factory
 */
class Course_Progress_Repository_Factory_Test extends \WP_UnitTestCase {

	public function testCreate_WhenCalled_ReturnsCourseProgressRepository(): void {
		/* Arrange. */
		$factory = new Course_Progress_Repository_Factory();

		/* Act. */
		$actual = $factory->create();

		/* Assert. */
		self::assertInstanceOf( Course_Progress_Repository_Aggregate::class, $actual );
	}

	/**
	 * Test that the factory creates correct repositories.
	 *
	 * @dataProvider providerCreateFor_WhenCourseProgressGiven_ReturnsMatchingCourseProgressRepository
	 * @param string $progress_type
	 * @param string $expected
	 */
	public function testCreateFor_WhenCourseProgressGiven_ReturnsMatchingCourseProgressRepository( string $progress_type, string $expected ): void {
		/* Arrange. */
		$progress = $this->createMock( $progress_type );
		$factory  = new Course_Progress_Repository_Factory();

		/* Act. */
		$actual = $factory->create_for( $progress );

		/* Assert. */
		self::assertInstanceOf( $expected, $actual );
	}

	public function providerCreateFor_WhenCourseProgressGiven_ReturnsMatchingCourseProgressRepository(): array {
		return [
			[ Course_Progress_Tables::class, Course_Progress_Tables_Repository::class ],
			[ Course_Progress_Comments::class, Course_Progress_Comments_Repository::class ],
		];
	}

}

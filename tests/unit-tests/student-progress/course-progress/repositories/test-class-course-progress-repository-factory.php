<?php

namespace SenseiTest\Student_Progress\Course_Progress\Repositories;

use Sensei\Student_Progress\Course_Progress\Models\Comments_Based_Course_Progress;
use Sensei\Student_Progress\Course_Progress\Models\Tables_Based_Course_Progress;
use Sensei\Student_Progress\Course_Progress\Repositories\Comments_Based_Course_Progress_Repository;
use Sensei\Student_Progress\Course_Progress\Repositories\Aggregate_Course_Progress_Repository;
use Sensei\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Factory;
use Sensei\Student_Progress\Course_Progress\Repositories\Tables_Based_Course_Progress_Repository;


/**
 * Class Course_Progress_Repository_Factory_Test
 *
 * @covers \Sensei\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Factory
 */
class Course_Progress_Repository_Factory_Test extends \WP_UnitTestCase {

	public function testCreate_WhenCalled_ReturnsCourseProgressRepository(): void {
		/* Arrange. */
		$factory = new Course_Progress_Repository_Factory();

		/* Act. */
		$actual = $factory->create();

		/* Assert. */
		self::assertInstanceOf( Aggregate_Course_Progress_Repository::class, $actual );
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
			[ Tables_Based_Course_Progress::class, Tables_Based_Course_Progress_Repository::class ],
			[ Comments_Based_Course_Progress::class, Comments_Based_Course_Progress_Repository::class ],
		];
	}

}

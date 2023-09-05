<?php

namespace SenseiTest\Internal\Student_Progress\Course_Progress\Repositories;

use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Aggregate_Course_Progress_Repository;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Comment_Reading_Aggregate_Course_Progress_Repository;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Comments_Based_Course_Progress_Repository;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Factory;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Table_Reading_Aggregate_Course_Progress_Repository;

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
	public function testCreate_WhenCalled_ReturnsCourseProgressRepository( bool $tables_enabled, bool $read_tables, string $expected ): void {
		/* Arrange. */
		$factory = new Course_Progress_Repository_Factory( $tables_enabled, $read_tables );

		/* Act. */
		$actual = $factory->create();

		/* Assert. */
		self::assertInstanceOf( $expected, $actual );
	}

	public function providerCreate_WhenCalled_ReturnsCourseProgressRepository(): array {
		return array(
			'tables enabled, reading disabled'  => array(
				true,
				false,
				Comment_Reading_Aggregate_Course_Progress_Repository::class,
			),
			'tables enabled, reading enabled'   => array(
				true,
				true,
				Table_Reading_Aggregate_Course_Progress_Repository::class,
			),
			'tables disabled, reading disabled' => array(
				false,
				false,
				Comments_Based_Course_Progress_Repository::class,
			),
			'tables disabled, reading enabled'  => array(
				false,
				true,
				Comments_Based_Course_Progress_Repository::class,
			),
		);
	}
}

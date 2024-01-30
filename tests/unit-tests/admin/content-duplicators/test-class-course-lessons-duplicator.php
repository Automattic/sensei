<?php

namespace SenseiTest\Admin\Content_Duplicators;

use Sensei\Admin\Content_Duplicators\Course_Lessons_Duplicator;
use Sensei_Factory;

/**
 * Class Course_Lessons_Duplicator_Test
 *
 * @covers Sensei\Admin\Content_Duplicators\Course_Lessons_Duplicator
 */
class Course_Lessons_Duplicator_Test extends \WP_UnitTestCase {
	/**
	 * Sensei factory.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public function set_up(): void {
		parent::set_up();
		$this->factory = new Sensei_Factory();
	}

	public function tear_down(): void {
		parent::tear_down();
		$this->factory->tearDown();
	}

	public function testDuplicate_CourseIdsGiven_ReturnsMatchingNumberOfDuplicatedLessons(): void {
		/* Arrange */
		$course_info   = $this->factory->get_course_with_lessons( array( 'lesson_count' => 2 ) );
		$new_course_id = $this->factory->course->create();

		$duplicator = new Course_Lessons_Duplicator();

		/* Act */
		$actual = $duplicator->duplicate( $course_info['course_id'], $new_course_id );

		/* Assert */
		$this->assertSame( 2, $actual );
	}
}

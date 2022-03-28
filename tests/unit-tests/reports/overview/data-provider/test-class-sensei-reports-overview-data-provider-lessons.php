<?php

/**
 * Sensei Reports Overview Data Provider Lessons Test Class
 *
 * @covers Sensei_Reports_Overview_Data_Provider_Lessons
 */
class Sensei_Reports_Overview_Data_Provider_Lessons_Test extends WP_UnitTestCase {
	/**
	 * Factory for setting up testing data.
	 *
	 * @var Sensei_Factory
	 */
	private $factory;

	/**
	 * Set up before each test.
	 */
	public function setup() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown() {
		parent::tearDown();

		$this->factory->tearDown();
	}

	/**
	 * Tests that when getting the lessons they are filtered by course.
	 *
	 * @covers Sensei_Reports_Overview_Data_Provider_Lessons::get_items
	 */
	public function testGetLessonsByCourse() {
		/* Arrange. */
		$course_id         = $this->factory->course->create();
		$course_lesson_ids = $this->factory->lesson->create_many( 2, [ 'meta_input' => [ '_lesson_course' => $course_id ] ] );

		// Fill the database with other lessons from other courses.
		$this->factory->lesson->create_many( 2, [ 'meta_input' => [ '_lesson_course' => $this->factory->course->create() ] ] );

		$instance = new Sensei_Reports_Overview_Data_Provider_Lessons( Sensei()->course );

		/* Act. */
		$query_args = [
			'number'    => -1,
			'offset'    => 0,
			'orderby'   => '',
			'order'     => 'ASC',
			'course_id' => $course_id,
		];

		$course_lesson_posts = $instance->get_items( $query_args );

		/* Assert. */
		$this->assertEquals(
			$course_lesson_ids,
			wp_list_pluck( $course_lesson_posts, 'ID' ),
			'The lessons should be filtered by course.'
		);
	}
}

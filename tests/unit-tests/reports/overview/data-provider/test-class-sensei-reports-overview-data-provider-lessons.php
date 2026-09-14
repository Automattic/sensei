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
	protected $factory;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		$this->factory->tearDown();
	}

	/**
	 * Tests that when getting the lessons they are filtered by course.
	 *
	 * @covers Sensei_Reports_Overview_Data_Provider_Lessons::get_items
	 */
	public function testGetItems_FilteredByCourse_ReturnsFilteredLessons() {
		/* Arrange. */
		$course_id         = $this->factory->course->create();
		$course_lesson_ids = $this->factory->lesson->create_many( 2, [ 'meta_input' => [ '_lesson_course' => $course_id ] ] );

		// Fill the database with other lessons from other courses.
		$this->factory->lesson->create_many( 2, [ 'meta_input' => [ '_lesson_course' => $this->factory->course->create() ] ] );

		$instance = $this->create_data_provider();

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

	/**
	 * Tests that get items returns no lessons when course doesn't have lessons.
	 *
	 * @covers Sensei_Reports_Overview_Data_Provider_Lessons::get_items
	 */
	public function testGetItems_returns_no_lessons_if_course_does_not_have_lessons() {
		// Create 2 courses.
		$course_id_1 = $this->factory->course->create();
		$course_id_2 = $this->factory->course->create();

		// Add lessons to second course.
		$course_lesson_ids = $this->factory->lesson->create_many( 2, [ 'meta_input' => [ '_lesson_course' => $course_id_2 ] ] );

		// Fill the database with other lessons from other courses.
		$this->factory->lesson->create_many( 2, [ 'meta_input' => [ '_lesson_course' => $this->factory->course->create() ] ] );

		$instance = $this->create_data_provider();

		// Get items for first course.
		$query_args = [
			'number'    => -1,
			'offset'    => 0,
			'orderby'   => '',
			'order'     => 'ASC',
			'course_id' => $course_id_1,
		];

		$course_lesson_posts = $instance->get_items( $query_args );

		/* Assert. */
		$this->assertEquals(
			[],
			$course_lesson_posts,
			'No lesson was returned from get items.'
		);
	}

	/**
	 * Create the data provider with the active storage implementation.
	 *
	 * @return Sensei_Reports_Overview_Data_Provider_Lessons
	 */
	private function create_data_provider(): Sensei_Reports_Overview_Data_Provider_Lessons {
		$query_service_factory = new \Sensei\Internal\Services\Progress_Query_Service_Factory( Sensei()->progress_storage_configuration );

		return new Sensei_Reports_Overview_Data_Provider_Lessons( Sensei()->course, $query_service_factory->create_clauses_service() );
	}
}

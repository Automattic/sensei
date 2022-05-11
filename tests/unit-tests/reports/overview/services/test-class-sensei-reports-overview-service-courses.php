<?php

/**
 * Sensei Reports Overview Service Courses Test Class
 *
 * @covers Sensei_Reports_Overview_Service_Courses
 */
class Sensei_Reports_Overview_Service_Courses_Test extends WP_UnitTestCase {

	private static $initial_hook_suffix;

	/**
	 * Factory for setting up testing data.
	 *
	 * @var Sensei_Factory
	 */
	private $factory;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		self::$initial_hook_suffix = $GLOBALS['hook_suffix'] ?? null;
		$GLOBALS['hook_suffix']    = null;
	}

	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		$GLOBALS['hook_suffix'] = self::$initial_hook_suffix;
	}

	/**
	 * Set up before each test.
	 */
	public function setUp() {
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
	 * Tests getting total average progress value for the course based on the lessons completion for single course.
	 *
	 * @covers Sensei_Analysis_Overview_List_Table::get_average_progress_for_courses_table
	 */
	public function testTotalAverageProgressForCoursesSingleCourse() {
		// Create a course
		$course_id = $this->factory->course->create();

		// Create 2 users
		$user_id_1 = $this->factory->user->create();
		$user_id_2 = $this->factory->user->create();

		//Add 2 lessons to the course
		$lesson_1 = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$lesson_2 = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		$service = new Sensei_Reports_Overview_Service_Courses();

		// Complete lesson 1 and lesson 2 with user_1.
		Sensei_Utils::sensei_start_lesson( $lesson_1, $user_id_1, true );
		Sensei_Utils::sensei_start_lesson( $lesson_2, $user_id_1, true );

		// Enroll student 2 to the course and lessons, but don't complete the lessons.
		Sensei_Utils::sensei_start_lesson( $lesson_1, $user_id_2 );
		Sensei_Utils::sensei_start_lesson( $lesson_2, $user_id_2 );

		/* Assert. */
		$this->assertEquals(
			50,
			$service->get_total_average_progress( [ $course_id ] ),
			'Find totals of lessons completed single course.'
		);
	}

	/**
	 * Tests getting total average progress value for the course based on the lessons completion for multiple courses.
	 *
	 * @covers Sensei_Analysis_Overview_List_Table::get_average_progress_for_courses_table
	 */
	public function testTotalAverageProgressForCoursesMultipleCourses() {
		// Create a course 1
		$course_id_1 = $this->factory->course->create();

		// Create a course 2
		$course_id_2 = $this->factory->course->create();

		// Create 2 users
		$user_id_1 = $this->factory->user->create();
		$user_id_2 = $this->factory->user->create();

		//Add 2 lessons to the course 1
		$lesson_1 = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id_1 ] ]
		);
		$lesson_2 = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id_1 ] ]
		);
		//Add 2 lessons to the course 2
		$lesson_3 = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id_2 ] ]
		);
		$lesson_4 = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id_2 ] ]
		);
		$service  = new Sensei_Reports_Overview_Service_Courses();
		// Complete lesson 1 and lesson 2 with user_1.
		Sensei_Utils::sensei_start_lesson( $lesson_1, $user_id_1, true );
		Sensei_Utils::sensei_start_lesson( $lesson_2, $user_id_1, true );

		// Enroll student 2 to the course and lessons, but don't complete the lessons.
		Sensei_Utils::sensei_start_lesson( $lesson_1, $user_id_2 );
		Sensei_Utils::sensei_start_lesson( $lesson_2, $user_id_2 );

		// Complete lesson 1 and lesson 2 with user_1.
		Sensei_Utils::sensei_start_lesson( $lesson_3, $user_id_1, true );
		Sensei_Utils::sensei_start_lesson( $lesson_4, $user_id_1 );

		// Enroll student 2 to the course and lessons, but don't complete the lessons.
		Sensei_Utils::sensei_start_lesson( $lesson_3, $user_id_2 );
		Sensei_Utils::sensei_start_lesson( $lesson_4, $user_id_2 );
		/* Assert. */
		$this->assertEquals(
			38,
			$service->get_total_average_progress( [ $course_id_1, $course_id_2 ] ),
			'Find totals of lessons completed multiple courses.'
		);
	}



	/**
	 * Tests getting total average progress value for the course based on the lessons completion to be zero.
	 *
	 * @covers Sensei_Analysis_Overview_List_Table::get_average_progress_for_courses_table
	 */
	public function testTotalAverageProgressForCoursesProgressZero() {
		// Create a course 1
		$course_id_1 = $this->factory->course->create();

		// Create single
		$user_id_2 = $this->factory->user->create();

		//Add 2 lessons to the course 1
		$lesson_1 = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id_1 ] ]
		);
		$lesson_2 = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id_1 ] ]
		);
		$service  = new Sensei_Reports_Overview_Service_Courses();

		// Enroll student 2 to the course and lessons, but don't complete the lessons.
		Sensei_Utils::sensei_start_lesson( $lesson_1, $user_id_2 );
		Sensei_Utils::sensei_start_lesson( $lesson_2, $user_id_2 );

		/* Assert. */
		$this->assertEquals(
			0,
			$service->get_total_average_progress( [ $course_id_1 ] ),
			'Find average progress total is 0 when no lesson is completed'
		);
	}


	/**
	 * Tests getting total average progress value create courses but don't add students returns 0.
	 *
	 * @covers Sensei_Analysis_Overview_List_Table::get_average_progress_for_courses_table
	 */
	public function testTotalAverageProgressForCoursesProgressZeroNoStudents() {
		// Create a course 1
		$this->factory->course->create();

		$service = new Sensei_Reports_Overview_Service_Courses();

		/* Assert. */
		$this->assertEquals(
			0,
			$service->get_total_average_progress( [] ),
			'Average of progress total is zero when no lessons or students.'
		);
	}

	/**
	 * Tests getting total average progress value for the course based on the lessons completion for multiple students.
	 *
	 * @covers Sensei_Analysis_Overview_List_Table::get_average_progress_for_courses_table
	 */
	public function testTotalAverageProgressCompletedForMultipleStudents() {
		// Create first course
		$course_id_1 = $this->factory->course->create();

		// Create second course
		$course_id_2 = $this->factory->course->create();

		// Create 3 users
		$user_id_1 = $this->factory->user->create();
		$user_id_2 = $this->factory->user->create();
		$user_id_3 = $this->factory->user->create();

		//Add 2 lessons to the first course
		$lesson_1 = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id_1 ] ]
		);
		$lesson_2 = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id_1 ] ]
		);

		// Add 1 lesson to the second course
		$lesson_3 = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id_2 ] ]
		);
		$service  = new Sensei_Reports_Overview_Service_Courses();

		// Complete lesson 1 and lesson 2 with user_1.
		Sensei_Utils::sensei_start_lesson( $lesson_1, $user_id_1, true );
		Sensei_Utils::sensei_start_lesson( $lesson_2, $user_id_1, true );

		// Enroll student 2 to the course and lessons, but don't complete the lessons.
		Sensei_Utils::sensei_start_lesson( $lesson_1, $user_id_2, true );
		Sensei_Utils::sensei_start_lesson( $lesson_2, $user_id_2, true );

		// Enroll 1 student to the second course and complete lesson
		Sensei_Utils::sensei_start_lesson( $lesson_3, $user_id_3, true );

		/* Assert. */
		$this->assertEquals(
			100,
			$service->get_total_average_progress( [ $course_id_1, $course_id_2 ] ),
			'Find totals of lessons completed single course.'
		);
	}
}

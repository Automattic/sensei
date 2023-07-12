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
	protected $factory;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$initial_hook_suffix = $GLOBALS['hook_suffix'] ?? null;
		$GLOBALS['hook_suffix']    = null;
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		$GLOBALS['hook_suffix'] = self::$initial_hook_suffix;
	}

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


	public function testGetAverageDaysToCompletionWhenOneCourseExistsReturnsMatchingValue() {
		$user1_id  = $this->factory->user->create();
		$user2_id  = $this->factory->user->create();
		$user3_id  = $this->factory->user->create();
		$course_id = $this->factory->course->create();

		$comment1_id = Sensei_Utils::update_course_status( $user1_id, $course_id, 'complete' );
		wp_update_comment(
			[
				'comment_ID'   => $comment1_id,
				'comment_date' => '2022-01-07 00:00:00',
			]
		);
		update_comment_meta( $comment1_id, 'start', '2022-01-01 00:00:01' );

		$comment2_id = Sensei_Utils::update_course_status( $user2_id, $course_id, 'complete' );
		wp_update_comment(
			[
				'comment_ID'   => $comment2_id,
				'comment_date' => '2022-01-10 00:00:00',
			]
		);
		update_comment_meta( $comment2_id, 'start', '2022-01-01 00:00:01' );

		$comment3_id = Sensei_Utils::update_course_status( $user3_id, $course_id, 'complete' );
		wp_update_comment(
			[
				'comment_ID'   => $comment3_id,
				'comment_date' => '2022-01-30 00:00:00',
			]
		);
		update_comment_meta( $comment3_id, 'start', '2022-01-01 00:00:01' );

		$instance = new Sensei_Reports_Overview_Service_Courses();
		$actual   = $instance->get_average_days_to_completion( [ $course_id ] );

		// 2022-01-07 00:00:00 - 2022-01-01 00:00:01 + 1 = 7 days.
		// 2022-01-10 00:00:00 - 2022-01-01 00:00:01 + 1 = 10 days.
		// 2022-01-30 00:00:00 - 2022-01-01 00:00:01 + 1 = 30 days.
		// As these completions are for the single course:
		// ceil(7 + 10 + 30/ 3)  = 16 days.
		self::assertSame( 16.0, $actual );
	}

	public function testGetAverageDaysToCompletionWhenMoreThanOneCourseExistReturnsMatchingValue() {
		$user1_id   = $this->factory->user->create();
		$user2_id   = $this->factory->user->create();
		$course1_id = $this->factory->course->create();
		$course2_id = $this->factory->course->create();

		$comment1_id = Sensei_Utils::update_course_status( $user1_id, $course1_id, 'complete' );
		wp_update_comment(
			[
				'comment_ID'   => $comment1_id,
				'comment_date' => '2022-03-11 23:29:06',
			]
		);
		update_comment_meta( $comment1_id, 'start', '2022-03-11 23:27:51' );

		$comment2_id = Sensei_Utils::update_course_status( $user2_id, $course1_id, 'complete' );
		wp_update_comment(
			[
				'comment_ID'   => $comment2_id,
				'comment_date' => '2022-03-14 21:34:37',
			]
		);
		update_comment_meta( $comment2_id, 'start', '2022-03-14 21:34:27' );

		$comment3_id = Sensei_Utils::update_course_status( $user1_id, $course2_id, 'complete' );
		wp_update_comment(
			[
				'comment_ID'   => $comment3_id,
				'comment_date' => '2022-03-12 00:22:37',
			]
		);
		update_comment_meta( $comment3_id, 'start', '2022-03-09 00:22:34' );

		$instance = new Sensei_Reports_Overview_Service_Courses();
		$actual   = $instance->get_average_days_to_completion( [ $course1_id, $course2_id ] );

		// Average for the first course: (1 + 1) / 2 = 1.
		// Average for the second course: 4 / 1 = 4.
		// Total: (1 + 4) / 2 = 2.5.
		self::assertSame( 2.5, $actual );
	}

	public function testGetTotalTotalEnrollments_WhenThereWereNoEnrolledStudents_ReturnsZero() {

		/* Arrange. */
		$instance = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $instance->get_total_enrollments( [] );

		/* Assert. */
		self::assertSame( 0, $actual );
	}
	public function testGetTotalTotalEnrollments_WhenThereWereSameStudentsInDifferentCourses_ReturnsSumOfEnrollments() {

		/* Arrange */
		$user1_id   = $this->factory->user->create();
		$course1_id = $this->factory->course->create();
		$course2_id = $this->factory->course->create();

		// Add 2 lessons to the course.
		$lesson_course_1 = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course1_id ] ]
		);
		$lesson_course_2 = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course2_id ] ]
		);

		// Enroll student 2 to the course and lessons, but don't complete the lessons.
		Sensei_Utils::sensei_start_lesson( $lesson_course_1, $user1_id );
		Sensei_Utils::sensei_start_lesson( $lesson_course_2, $user1_id );

		$instance = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $instance->get_total_enrollments( [ $course1_id, $course2_id ] );

		/* Assert. */
		self::assertSame( 2, $actual );
	}


	public function testGetTotalTotalEnrollments_WhenThereWereStudentsInDifferentCourses_ReturnsSumOfEnrollments() {

		/* Arrange */
		$user1_id = $this->factory->user->create();
		$user2_id = $this->factory->user->create();

		$course1_id = $this->factory->course->create();
		$course2_id = $this->factory->course->create();

		// Add 2 lessons to the course.
		$lesson_course_1 = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course1_id ] ]
		);
		$lesson_course_2 = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course2_id ] ]
		);

		// Enroll student 2 to the course and lessons, but don't complete the lessons.
		Sensei_Utils::sensei_start_lesson( $lesson_course_1, $user1_id );
		Sensei_Utils::sensei_start_lesson( $lesson_course_2, $user2_id );

		$instance = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $instance->get_total_enrollments( [ $course1_id, $course2_id ] );

		/* Assert. */
		self::assertSame( 2, $actual );
	}

	public function testGetAverageDaysToCompletionTotalWithoutCompletionsReturnsZero() {
		$instance = new Sensei_Reports_Overview_Service_Courses();
		$actual   = $instance->get_average_days_to_completion( [] );

		self::assertSame( 0.0, $actual );
	}
}

<?php

/**
 * Tests for Sensei_Analysis_Overview_List_Table class.
 */
class Sensei_Analysis_Overview_List_Table_Test extends WP_UnitTestCase {

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
	 * Lesson statuses that mark the lesson as completed.
	 *
	 * @return string[][]
	 */
	public function lessonCompleteStatuses(): array {
		return [
			[ 'complete' ],
			[ 'passed' ],
			[ 'graded' ],
		];
	}

	/**
	 * Lesson statuses that mark the lesson as uncompleted.
	 *
	 * @return string[][]
	 */
	public function lessonIncompleteStatuses(): array {
		return [
			[ 'in-progress' ],
			[ 'ungraded' ],
			[ 'failed' ],
		];
	}

	/**
	 * Tests that the last activity is ignoring uncompleted lessons.
	 *
	 * @covers Sensei_Admin::get_last_activity_date
	 * @dataProvider lessonIncompleteStatuses
	 */
	public function testGetLastActivityDateShouldIgnoreIncompleteLessons( $lesson_incomplete_status ) {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$lesson_id = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $this->factory->course->create() ] ]
		);

		$instance = new Sensei_Analysis_Overview_List_Table();
		$method   = new ReflectionMethod( $instance, 'get_last_activity_date' );
		$method->setAccessible( true );

		/* Act. */
		// Start lesson 1 (status: in-progress).
		$lesson_activity_comment_id = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );
		wp_update_comment(
			[
				'comment_ID'       => $lesson_activity_comment_id,
				'comment_approved' => $lesson_incomplete_status,
			]
		);

		/* Assert. */
		$this->assertEquals(
			'N/A',
			$method->invoke( $instance, array( 'user_id' => $user_id ) ),
			'The last activity should not take into account lessons that are in progress.'
		);
	}

	/**
	 * Tests that the last activity is based on "completed" lessons.
	 *
	 * @covers Sensei_Admin::get_last_activity_date
	 * @dataProvider lessonCompleteStatuses
	 */
	public function testGetLastActivityDateShouldBeBasedOnCompletedLessons( $lesson_complete_status ) {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$lesson_id = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $this->factory->course->create() ] ]
		);

		$instance = new Sensei_Analysis_Overview_List_Table();
		$method   = new ReflectionMethod( $instance, 'get_last_activity_date' );
		$method->setAccessible( true );

		/* Act. */
		$lesson_activity_comment_id = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );
		wp_update_comment(
			[
				'comment_ID'       => $lesson_activity_comment_id,
				'comment_approved' => $lesson_complete_status,
			]
		);

		/* Assert. */
		$this->assertStringEndsWith(
			'ago',
			$method->invoke( $instance, array( 'user_id' => $user_id ) ),
			'The last activity should take into account lessons with status "' . $lesson_complete_status . '".'
		);
	}

	/**
	 * Tests that the last activity should be the more recent one.
	 *
	 * @covers Sensei_Admin::get_last_activity_date
	 */
	public function testGetLastActivityDateShouldReturnTheMoreRecentActivity() {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_1  = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$lesson_2  = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		$instance = new Sensei_Analysis_Overview_List_Table();
		$method   = new ReflectionMethod( $instance, 'get_last_activity_date' );
		$method->setAccessible( true );

		/* Act. */
		// Complete lesson 1 and update its activity date.
		$lesson_1_activity_comment_id = Sensei_Utils::sensei_start_lesson( $lesson_1, $user_id, true );
		$lesson_1_activity_timestamp  = strtotime( '-3 days' );
		wp_update_comment(
			[
				'comment_ID'   => $lesson_1_activity_comment_id,
				'comment_date' => gmdate( 'Y-m-d H:i:s', $lesson_1_activity_timestamp ),
			]
		);

		// Complete lesson 2 and update its activity date.
		$lesson_2_activity_comment_id = Sensei_Utils::sensei_start_lesson( $lesson_2, $user_id, true );
		$lesson_2_activity_timestamp  = strtotime( '-2 day' );
		wp_update_comment(
			[
				'comment_ID'   => $lesson_2_activity_comment_id,
				'comment_date' => gmdate( 'Y-m-d H:i:s', $lesson_2_activity_timestamp ),
			]
		);

		/* Assert. */
		$this->assertEquals(
			'2 days ago',
			$method->invoke( $instance, array( 'user_id' => $user_id ) ),
			'The last activity should be the more recent activity.'
		);

		/* Act. */
		// Update lesson 1 activity date.
		$lesson_1_activity_timestamp = strtotime( '-1 days' );
		wp_update_comment(
			[
				'comment_ID'   => $lesson_1_activity_comment_id,
				'comment_date' => gmdate( 'Y-m-d H:i:s', $lesson_1_activity_timestamp ),
			]
		);

		/* Assert. */
		$this->assertEquals(
			'1 day ago',
			$method->invoke( $instance, array( 'user_id' => $user_id ) ),
			'The last activity should be the more recent activity.'
		);
	}

	/**
	 * Tests that the last activity date format is human-readable when less than a week.
	 *
	 * @covers Sensei_Admin::get_last_activity_date
	 */
	public function testGetLastActivityDateShouldUseHumanReadableTimeFormatIfLessThanAWeek() {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$lesson_id = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $this->factory->course->create() ] ]
		);

		$instance = new Sensei_Analysis_Overview_List_Table();
		$method   = new ReflectionMethod( $instance, 'get_last_activity_date' );
		$method->setAccessible( true );

		/* Act. */
		// Complete lesson and update its activity date.
		$lesson_activity_comment_id = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id, true );
		$lesson_activity_timestamp  = strtotime( '-7 days' );
		wp_update_comment(
			[
				'comment_ID'   => $lesson_activity_comment_id,
				'comment_date' => gmdate( 'Y-m-d H:i:s', $lesson_activity_timestamp ),
			]
		);

		/* Assert. */
		$this->assertEquals(
			wp_date(
				get_option( 'date_format' ),
				$lesson_activity_timestamp,
				new DateTimeZone( 'GMT' )
			),
			$method->invoke( $instance, array( 'user_id' => $user_id ) ),
			'The last activity date format or timezone is invalid.'
		);

		/* Act. */
		// Update the lesson's activity date.
		$lesson_activity_timestamp = strtotime( '-1 day' );
		wp_update_comment(
			[
				'comment_ID'   => $lesson_activity_comment_id,
				'comment_date' => gmdate( 'Y-m-d H:i:s', $lesson_activity_timestamp ),
			]
		);

		/* Assert. */
		$this->assertEquals(
			'1 day ago',
			$method->invoke( $instance, array( 'user_id' => $user_id ) ),
			'The last activity date format should be in human-readable form.'
		);
	}

	/**
	 * Tests that the correct last activity date is returned when queried by course.
	 *
	 * @covers Sensei_Admin::get_last_activity_date
	 */
	public function testGetLastActivityDateByCourseLessons() {
		$user_ids   = $this->factory->user->create_many( 3 );
		$lesson_ids = $this->factory->lesson->create_many(
			2,
			[ 'meta_input' => [ '_lesson_course' => $this->factory->course->create() ] ]
		);
		$days_count = -7;

		$instance = new Sensei_Analysis_Overview_List_Table();
		$method   = new ReflectionMethod( $instance, 'get_last_activity_date' );
		$method->setAccessible( true );

		// Complete a lesson for each student on a different date.
		foreach ( $user_ids as $user_id ) {
			$lesson_activity_comment_id = Sensei_Utils::sensei_start_lesson( $lesson_ids[0], $user_id, true );
			wp_update_comment(
				[
					'comment_ID'   => $lesson_activity_comment_id,
					'comment_date' => gmdate( 'Y-m-d H:i:s', strtotime( $days_count . ' days' ) ),
				]
			);

			$days_count--;
		}

		$this->assertEquals(
			wp_date(
				get_option( 'date_format' ),
				strtotime( '-7 days' ),
				new DateTimeZone( 'GMT' )
			),
			$method->invoke( $instance, array( 'post__in' => $lesson_ids ) ),
			'The last activity date format or timezone is invalid.'
		);
	}

	/**
	 * Tests that when getting the lessons they are filtered by course.
	 *
	 * @covers Sensei_Analysis_Overview_List_Table::get_lessons
	 */
	public function testGetLessonsByCourse() {
		/* Arrange. */
		$course_id         = $this->factory->course->create();
		$course_lesson_ids = $this->factory->lesson->create_many( 2, [ 'meta_input' => [ '_lesson_course' => $course_id ] ] );

		// Fill the database with other lessons from other courses.
		$this->factory->lesson->create_many( 2, [ 'meta_input' => [ '_lesson_course' => $this->factory->course->create() ] ] );

		$instance = new Sensei_Analysis_Overview_List_Table();
		$method   = new ReflectionMethod( $instance, 'get_lessons' );
		$method->setAccessible( true );

		/* Act. */
		$query_args = [
			'number'  => -1,
			'offset'  => 0,
			'orderby' => '',
			'order'   => 'ASC',
		];

		$course_lesson_posts = $method->invoke( $instance, $query_args, $course_id );

		/* Assert. */
		$this->assertEquals(
			$course_lesson_ids,
			wp_list_pluck( $course_lesson_posts, 'ID' ),
			'The lessons should be filtered by course.'
		);
	}

	/**
	 * Tests that the learners last activity filter is applied correctly.
	 *
	 * @covers Sensei_Analysis_Overview_List_Table::get_learners
	 * @covers Sensei_Analysis_Overview_List_Table::filter_users_by_last_activity
	 */
	public function testGetLearnersByLastActivityDate() {
		/* Arrange. */
		$user_1 = $this->factory->user->create();
		$user_2 = $this->factory->user->create();

		$lesson_id = $this->factory->lesson->create( [ 'meta_input' => [ '_lesson_course' => $this->factory->course->create() ] ] );

		$instance = new Sensei_Analysis_Overview_List_Table();
		$method   = new ReflectionMethod( $instance, 'get_learners' );
		$method->setAccessible( true );

		$user_1_activity_comment_id = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_1, true );
		$user_2_activity_comment_id = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_2, true );

		wp_update_comment(
			[
				'comment_ID'   => $user_1_activity_comment_id,
				'comment_date' => '2022-03-01 00:00:00',
			]
		);

		wp_update_comment(
			[
				'comment_ID'   => $user_2_activity_comment_id,
				'comment_date' => '2022-03-02 00:00:00',
			]
		);

		/* Act. */
		$_GET = [
			'start_date' => '2022-03-01',
			'end_date'   => '2022-03-01',
		];

		$learners = $method->invoke( $instance, [] );

		/* Assert. */
		$this->assertEquals(
			[ $user_1 ],
			wp_list_pluck( $learners, 'ID' ),
			'The filter should work correctly when using the same start and end date.'
		);

		/* Act. */
		$_GET = [
			'start_date' => '2022-03-01',
			'end_date'   => '2022-03-02',
		];

		$learners = $method->invoke( $instance, [] );

		/* Assert. */
		$this->assertEquals(
			[ $user_1, $user_2 ],
			wp_list_pluck( $learners, 'ID' ),
			'The filter should work correctly when using different start and end date.'
		);

		/* Act. */
		$_GET = [
			'start_date' => '2022-03-02',
		];

		$learners = $method->invoke( $instance, [] );

		/* Assert. */
		$this->assertEquals(
			[ $user_2 ],
			wp_list_pluck( $learners, 'ID' ),
			'The filter should work correctly when using only the start date.'
		);

		/* Act. */
		$_GET = [
			'start_date' => '2022-03-02',
			'end_date'   => '2022-03-01',
		];

		$learners = $method->invoke( $instance, [] );

		/* Assert. */
		$this->assertEmpty(
			$learners,
			'The filter should return no results when the start date is bigger than the end date.'
		);
	}

	/**
	 * Tests that the courses last activity filter is applied correctly.
	 *
	 * @covers Sensei_Analysis_Overview_List_Table::get_courses
	 * @covers Sensei_Analysis_Overview_List_Table::filter_courses_by_last_activity
	 */
	public function testGetCoursesByLastActivityDate() {
		/* Arrange. */
		$user_id = $this->factory->user->create();

		$course_1 = $this->factory->course->create();
		$course_2 = $this->factory->course->create();

		$course_1_lesson_id = $this->factory->lesson->create( [ 'meta_input' => [ '_lesson_course' => $course_1 ] ] );
		$course_2_lesson_id = $this->factory->lesson->create( [ 'meta_input' => [ '_lesson_course' => $course_2 ] ] );

		$instance = new Sensei_Analysis_Overview_List_Table();
		$method   = new ReflectionMethod( $instance, 'get_courses' );
		$method->setAccessible( true );

		$course_1_activity_comment_id = Sensei_Utils::sensei_start_lesson( $course_1_lesson_id, $user_id, true );
		$course_2_activity_comment_id = Sensei_Utils::sensei_start_lesson( $course_2_lesson_id, $user_id, true );

		wp_update_comment(
			[
				'comment_ID'   => $course_1_activity_comment_id,
				'comment_date' => '2022-03-01 00:00:00',
			]
		);

		wp_update_comment(
			[
				'comment_ID'   => $course_2_activity_comment_id,
				'comment_date' => '2022-03-02 00:00:00',
			]
		);

		$query_args = [
			'number'  => -1,
			'offset'  => 0,
			'orderby' => '',
			'order'   => 'ASC',
		];

		/* Act. */
		$_GET = [
			'start_date' => '2022-03-01',
			'end_date'   => '2022-03-01',
		];

		$courses = $method->invoke( $instance, $query_args );

		/* Assert. */
		$this->assertEquals(
			[ $course_1 ],
			wp_list_pluck( $courses, 'ID' ),
			'The filter should work correctly when using the same start and end date.'
		);

		/* Act. */
		$_GET = [
			'start_date' => '2022-03-01',
			'end_date'   => '2022-03-02',
		];

		$courses = $method->invoke( $instance, $query_args );

		/* Assert. */
		$this->assertEquals(
			[ $course_1, $course_2 ],
			wp_list_pluck( $courses, 'ID' ),
			'The filter should work correctly when using different start and end date.'
		);

		/* Act. */
		$_GET = [
			'start_date' => '2022-03-02',
		];

		$courses = $method->invoke( $instance, $query_args );

		/* Assert. */
		$this->assertEquals(
			[ $course_2 ],
			wp_list_pluck( $courses, 'ID' ),
			'The filter should work correctly when using only the start date.'
		);

		/* Act. */
		$_GET = [
			'start_date' => '2022-03-02',
			'end_date'   => '2022-03-01',
		];

		$courses = $method->invoke( $instance, $query_args );

		/* Assert. */
		$this->assertEmpty(
			$courses,
			'The filter should return no results when the start date is bigger than the end date.'
		);
	}

	/**
	 * Test the start date getter.
	 *
	 * @covers Sensei_Analysis_Overview_List_Table::get_start_date_filter_value
	 */
	public function testGetStartDateFilterValue() {
		/* Arrange. */
		$instance = new Sensei_Analysis_Overview_List_Table();
		$method   = new ReflectionMethod( $instance, 'get_start_date_filter_value' );
		$method->setAccessible( true );

		/* Act. */
		$_GET = [
			'start_date' => '2022-03-01',
		];

		$start_date = $method->invoke( $instance );

		/* Assert. */
		$this->assertEquals(
			'2022-03-01',
			$start_date,
			'The start date should be equal to the "start_date" query param.'
		);

		/* Act. */
		$_GET = [
			'start_date' => '',
		];

		$start_date = $method->invoke( $instance );

		/* Assert. */
		$this->assertEquals(
			gmdate( 'Y-m-d', strtotime( '-30 days' ) ),
			$start_date,
			'The start date should default to 30 days ago.'
		);
	}

	/**
	 * Test the end date getter.
	 *
	 * @covers Sensei_Analysis_Overview_List_Table::get_end_date_filter_value
	 */
	public function testGetEndDateFilterValue() {
		/* Arrange. */
		$instance = new Sensei_Analysis_Overview_List_Table();
		$method   = new ReflectionMethod( $instance, 'get_end_date_filter_value' );
		$method->setAccessible( true );

		/* Act. */
		$_GET = [
			'end_date' => '2022-03-01',
		];

		$end_date = $method->invoke( $instance );

		/* Assert. */
		$this->assertEquals(
			'2022-03-01',
			$end_date,
			'The end date should be equal to the "end_date" query param.'
		);
	}

	/**
	 * Test the start date and time getter.
	 *
	 * @covers Sensei_Analysis_Overview_List_Table::get_start_date_and_time
	 */
	public function testGetStartDateAndTime() {
		/* Arrange. */
		$instance = new Sensei_Analysis_Overview_List_Table();
		$method   = new ReflectionMethod( $instance, 'get_start_date_and_time' );
		$method->setAccessible( true );

		/* Act. */
		$_GET = [
			'start_date' => '2022-03-01',
		];

		$end_date = $method->invoke( $instance );

		/* Assert. */
		$this->assertEquals(
			'2022-03-01 00:00:00',
			$end_date,
			'The end date should be equal to the "end_date" query param, plus the first minute time.'
		);
	}

	/**
	 * Test the end date and time getter.
	 *
	 * @covers Sensei_Analysis_Overview_List_Table::get_end_date_and_time
	 */
	public function testGetEndDateAndTime() {
		/* Arrange. */
		$instance = new Sensei_Analysis_Overview_List_Table();
		$method   = new ReflectionMethod( $instance, 'get_end_date_and_time' );
		$method->setAccessible( true );

		/* Act. */
		$_GET = [
			'end_date' => '2022-03-01',
		];

		$end_date = $method->invoke( $instance );

		/* Assert. */
		$this->assertEquals(
			'2022-03-01 23:59:59',
			$end_date,
			'The end date should be equal to the "end_date" query param, plus the last minute time.'
		);
	}
}

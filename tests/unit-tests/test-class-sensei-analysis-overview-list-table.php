<?php

/**
 * Tests for Sensei_Analysis_Overview_List_Table class.
 */
class Sensei_Analysis_Overview_List_Table_Test extends WP_UnitTestCase {

	/**
	 * Factory for setting up testing data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

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
	 * Tests that we are getting right data for Completion Rate column.
	 *
	 * @covers Sensei_Analysis_Overview_List_Table::get_row_data
	 * @dataProvider dataFortestCompletionRateForLesson
	 */
	public function testCompletionRateForLesson( $enrolled_student_count, $completed_student_count, $expected_output ) {
		$user_ids                    = $this->factory->user->create_many( $enrolled_student_count );
		$course_lessons              = $this->factory->get_course_with_lessons(
			array(
				'lesson_count' => 1,
			)
		);
		$instance                    = new Sensei_Analysis_Overview_List_Table();
		$instance->type              = 'lessons';
		$get_lessons_method          = new ReflectionMethod( $instance, 'get_lessons' );
		$get_row_data_lessons_method = new ReflectionMethod( $instance, 'get_row_data' );
		$get_lessons_method->setAccessible( true );
		$get_row_data_lessons_method->setAccessible( true );
		$lessons_args = array(
			'offset'  => 0,
			'number'  => -1,
			'orderby' => '',
			'order'   => 'ASC',
		);
		$lesson_id    = array_pop( $course_lessons['lesson_ids'] );
		foreach ( $user_ids as $key => $user_id ) {
			Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id, $key < $completed_student_count );
		}
		$lessons  = $get_lessons_method->invoke( $instance, $lessons_args );
		$row_data = $get_row_data_lessons_method->invoke( $instance, array_pop( $lessons ) );
		$this->assertEquals( $row_data['completion_rate'], $expected_output );
	}
	/**
	 * Returns an associative array with parameters needed to run lesson completion test.
	 *
	 * @return array
	 */
	public function dataFortestCompletionRateForLesson(): array {
		return [
			'100%' => [ 5, 5, '100%' ],
			'80%'  => [ 5, 4, '80%' ],
			'67%'  => [ 3, 2, '67%' ],
			'N/A'  => [ 0, 0, 'N/A' ],
			'0%'   => [ 1, 0, '0%' ],
		];
	}
}

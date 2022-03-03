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
	public function lessonUncompleteStatuses(): array {
		return [
			[ 'in-progress' ],
			[ 'ungraded' ],
			[ 'failed' ],
		];
	}

	/**
	 * Tests that the last activity is ignoring uncompleted lessons.
	 *
	 * @covers Sensei_Admin::get_last_activity
	 * @dataProvider lessonUncompleteStatuses
	 */
	public function testGetLastActivityShouldIgnoreUncompleteLessons( $lesson_uncomplete_status ) {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$lesson_id = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $this->factory->course->create() ] ]
		);

		$instance = new Sensei_Analysis_Overview_List_Table();
		$method   = new ReflectionMethod( $instance, 'get_last_activity' );
		$method->setAccessible( true );

		/* Act. */
		// Start lesson 1 (status: in-progress).
		$lesson_activity_comment_id = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );
		wp_update_comment(
			[
				'comment_ID'       => $lesson_activity_comment_id,
				'comment_approved' => $lesson_uncomplete_status,
			]
		);

		/* Assert. */
		$this->assertEquals(
			'N/A',
			$method->invoke( $instance, $user_id ),
			'The last activity should not take into account lessons that are in progress.'
		);
	}

	/**
	 * Tests that the last activity is based on "completed" lessons.
	 *
	 * @covers Sensei_Admin::get_last_activity
	 * @dataProvider lessonCompleteStatuses
	 */
	public function testGetLastActivityShouldBeBasedOnCompletedLessons( $lesson_complete_status ) {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$lesson_id = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $this->factory->course->create() ] ]
		);

		$instance = new Sensei_Analysis_Overview_List_Table();
		$method   = new ReflectionMethod( $instance, 'get_last_activity' );
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
			$method->invoke( $instance, $user_id ),
			'The last activity should take into account lessons with status "' . $lesson_complete_status . '".'
		);
	}

	/**
	 * Tests that the last activity should be the more recent one.
	 *
	 * @covers Sensei_Admin::get_last_activity
	 */
	public function testGetLastActivityShouldReturnTheMoreRecentActivity() {
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
		$method   = new ReflectionMethod( $instance, 'get_last_activity' );
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
			$method->invoke( $instance, $user_id ),
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
			$method->invoke( $instance, $user_id ),
			'The last activity should be the more recent activity.'
		);
	}

	/**
	 * Tests that the last activity date format is human-readable when less than a week.
	 *
	 * @covers Sensei_Admin::get_last_activity
	 */
	public function testGetLastActivityShouldUseHumanReadableTimeFormatIfLessThanAWeek() {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$lesson_id = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $this->factory->course->create() ] ]
		);

		$instance = new Sensei_Analysis_Overview_List_Table();
		$method   = new ReflectionMethod( $instance, 'get_last_activity' );
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
			$method->invoke( $instance, $user_id ),
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
			$method->invoke( $instance, $user_id ),
			'The last activity date format should be in human-readable form.'
		);
	}

	public function testGetCourseDaysToCompletionForIncompleteCourseReturnsNull() {

		$course = $this->factory->course->create_and_get();
		$user   = $this->factory->user->create_and_get();
		Sensei_Utils::update_course_status( $user->ID, $course->ID, 'in-progress' );

		$instance = new Sensei_Analysis_Overview_List_Table();
		$method   = new ReflectionMethod( $instance, 'get_course_days_to_completion' );
		$method->setAccessible( true );

		$actual = $method->invoke( $instance, $course );

		$this->assertNull( $actual, 'Days to completion should be null for incomplete course' );
	}

	/**
	 * Tests that get_course_days_to_completion returns the correct value.
	 *
	 * @param string $start_user1
	 * @param string $complete_user1
	 * @param string $start_user2
	 * @param string $complete_user2
	 * @param float $expected
	 *
	 * @return void
	 * @dataProvider providerGetCourseDaysToCompletionWhenCourseWasCompletedReturnsMatchingValue
	 */
	public function testGetCourseDaysToCompletionWhenCourseWasCompletedReturnsMatchingValue(
		$start_user1, $complete_user1, $start_user2, $complete_user2, $expected
	) {

		$course = $this->factory->course->create_and_get();
		$user1  = $this->factory->user->create_and_get();
		$user2  = $this->factory->user->create_and_get();

		Sensei_Utils::update_course_status( $user1->ID, $course->ID, 'in-progress' );
		$comment_id = Sensei_Utils::update_course_status( $user1->ID, $course->ID, 'complete' );
		update_comment_meta( $comment_id, 'start', $start_user1 );
		wp_update_comment(
			[
				'comment_ID'   => $comment_id,
				'comment_date' => $complete_user1,
			]
		);

		Sensei_Utils::update_course_status( $user2->ID, $course->ID, 'in-progress' );
		$comment_id = Sensei_Utils::update_course_status( $user2->ID, $course->ID, 'complete' );
		update_comment_meta( $comment_id, 'start', $start_user2 );
		wp_update_comment(
			[
				'comment_ID'   => $comment_id,
				'comment_date' => $complete_user2,
			]
		);

		$instance = new Sensei_Analysis_Overview_List_Table();
		$method   = new ReflectionMethod( $instance, 'get_course_days_to_completion' );
		$method->setAccessible( true );

		$actual = $method->invoke( $instance, $course );

		$this->assertSame( $expected, $actual, 'Days to completion should match expected value' );
	}

	public function providerGetCourseDaysToCompletionWhenCourseWasCompletedReturnsMatchingValue(): array {
		return [
			'Course was started and completed at the same moment' => [
				'2018-01-01 00:00:00',
				'2018-01-01 00:00:00',
				'2018-01-01 00:00:00',
				'2018-01-01 00:00:00',
				null,
			],
			'Course was completed in one day for the first user and in two days for the second user' => [
				'2018-01-01 00:00:00',
				'2018-01-02 00:00:00',
				'2018-01-02 00:00:00',
				'2018-01-04 00:00:00',
				1.5,
			],
			'Course was completed in a week for the first user and in a month for the second user' => [
				'2018-01-01 00:00:00',
				'2018-01-08 00:00:00',
				'2018-01-02 00:00:00',
				'2018-02-02 00:00:00',
				19,
			],
		];
	}
}

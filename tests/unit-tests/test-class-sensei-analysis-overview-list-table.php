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
	 * Tests the last activity logic.
	 *
	 * @covers Sensei_Admin::get_last_activity
	 */
	public function testGetLastActivity() {
		/* Arrange. */
		$user_id  = $this->factory->user->create();
		$lesson_1 = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course' => $this->factory->course->create(),
				],
			]
		);
		$lesson_2 = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course' => $this->factory->course->create(),
				],
			]
		);


		$instance = new Sensei_Analysis_Overview_List_Table();
		$method   = new ReflectionMethod( $instance, 'get_last_activity' );
		$method->setAccessible( true );

		/* Act. */
		// Start lesson 1 (status: in-progress).
		$lesson_1_activity_comment_id = Sensei_Utils::sensei_start_lesson( $lesson_1, $user_id );

		/* Assert. */
		$this->assertEmpty(
			$method->invoke( $instance, $user_id ),
			'The last activity should not take into account activities that are in progress.'
		);

		/* Act. */
		// Simulate quiz submission.
		wp_update_comment(
			[
				'comment_ID'       => $lesson_1_activity_comment_id,
				'comment_approved' => 'ungraded',
			]
		);

		/* Assert. */
		$this->assertNotEmpty(
			$method->invoke( $instance, $user_id ),
			'The last activity should take into account activities with status "ungraded".'
		);

		/* Act. */
		// Simulate passing a quiz.
		wp_update_comment(
			[
				'comment_ID'       => $lesson_1_activity_comment_id,
				'comment_approved' => 'passed',
			]
		);

		/* Assert. */
		$this->assertNotEmpty(
			$method->invoke( $instance, $user_id ),
			'The last activity should take into account activities with status "passed".'
		);

		/* Act. */
		// Simulate failing a quiz.
		wp_update_comment(
			[
				'comment_ID'       => $lesson_1_activity_comment_id,
				'comment_approved' => 'failed',
			]
		);

		/* Assert. */
		$this->assertNotEmpty(
			$method->invoke( $instance, $user_id ),
			'The last activity should take into account activities with status "failed".'
		);

		/* Act. */
		// Complete lesson 1 and update its date.
		$lesson_1_activity_comment_id = Sensei_Utils::sensei_start_lesson( $lesson_1, $user_id, true );
		$lesson_1_activity_timestamp  = strtotime( '-7 days' );
		wp_update_comment(
			[
				'comment_ID'   => $lesson_1_activity_comment_id,
				'comment_date' => gmdate( 'Y-m-d H:i:s', $lesson_1_activity_timestamp ),
			]
		);

		/* Assert. */
		$this->assertEquals(
			wp_date(
				get_option( 'date_format' ),
				$lesson_1_activity_timestamp,
				new DateTimeZone( 'GMT' )
			),
			$method->invoke( $instance, $user_id ),
			'The last activity date format or timezone is invalid.'
		);

		/* Act. */
		// Complete lesson 2 and update its date.
		$lesson_2_activity_comment_id = Sensei_Utils::sensei_start_lesson( $lesson_2, $user_id, true );
		$lesson_2_activity_timestamp  = strtotime( '-1 day' );
		wp_update_comment(
			[
				'comment_ID'   => $lesson_2_activity_comment_id,
				'comment_date' => gmdate( 'Y-m-d H:i:s', $lesson_2_activity_timestamp ),
			]
		);

		/* Assert. */
		$this->assertEquals(
			'1 day ago',
			$method->invoke( $instance, $user_id ),
			'The last activity should be the more recent activity or the date format is invalid and should be in human-readable form.'
		);

	}
}

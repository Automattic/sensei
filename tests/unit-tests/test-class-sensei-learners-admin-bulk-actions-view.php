<?php

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';

/**
 * Tests for Sensei_Learners_Admin_Bulk_Actions_View class.
 *
 * @group bulk-actions
 */
class Sensei_Learners_Admin_Bulk_Actions_View_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Test_Helpers;

	/**
	 * Factory for setting up testing data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();

		self::resetEnrolmentProviders();
	}

	/**
	 * Clean up after all tests.
	 */
	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		self::resetEnrolmentProviders();
	}

	/**
	 * Tests that the user gets redirected when the action is invalid.
	 */
	public function testPrepareItems_WhenCalled_ReturnsStudentsWithLastActivityDate() {
		// Arrange
		$bulk_action_view_instance = new Sensei_Learners_Admin_Bulk_Actions_View(
			Sensei()->learners->bulk_actions_controller,
			Sensei()->learners
		);

		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		$comment_date               = gmdate( 'Y-m-d H:i:s', strtotime( '48 hours' ) );
		$lesson_activity_comment_id = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id, true );
		wp_update_comment(
			[
				'comment_ID'   => $lesson_activity_comment_id,
				'comment_date' => $comment_date,
			]
		);

		// Act
		$bulk_action_view_instance->prepare_items();

		// Assert
		$expected = get_gmt_from_date( $comment_date );
		$actual   = $bulk_action_view_instance->items[1]->last_activity_date;
		$this->assertEquals( $expected, $actual );
	}
}

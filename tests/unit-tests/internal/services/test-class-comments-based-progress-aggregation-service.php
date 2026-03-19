<?php

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Comments_Based_Progress_Aggregation_Service;

/**
 * Class Comments_Based_Progress_Aggregation_Service_Test.
 *
 * @covers \Sensei\Internal\Services\Comments_Based_Progress_Aggregation_Service
 */
class Comments_Based_Progress_Aggregation_Service_Test extends \WP_UnitTestCase {

	/**
	 * Sensei factory.
	 *
	 * @var \Sensei_Factory
	 */
	private $sensei_factory;

	public function setUp(): void {
		parent::setUp();
		$this->sensei_factory = new \Sensei_Factory();
	}

	public function testCountStatuses_LessonType_ReturnsStatusCounts(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'in-progress' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type' => 'lesson',
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['in-progress'] );
	}

	public function testCountStatuses_WithPostIdFilter_RestrictsToPost(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson1   = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$lesson2   = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		\Sensei_Utils::update_lesson_status( $user_id, $lesson1, 'in-progress' );
		\Sensei_Utils::update_lesson_status( $user_id, $lesson2, 'complete' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'    => 'lesson',
				'post_id' => $lesson1,
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['in-progress'], 'Expected filtered post to have in-progress status.' );
		$this->assertArrayNotHasKey( 'complete', $result, 'Excluded post status should not appear.' );
	}

	public function testCountStatuses_WithExcludeUserLoginPrefixes_ExcludesMatchingUsers(): void {
		/* Arrange. */
		global $wpdb;

		$regular_user = $this->sensei_factory->user->create();
		$guest_user   = $this->sensei_factory->user->create(
			[ 'user_login' => 'sensei_guest_12345' ]
		);
		$course_id    = $this->sensei_factory->course->create();
		$lesson_id    = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		\Sensei_Utils::update_lesson_status( $regular_user, $lesson_id, 'in-progress' );
		\Sensei_Utils::update_lesson_status( $guest_user, $lesson_id, 'complete' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'                        => 'lesson',
				'exclude_user_login_prefixes' => [ 'sensei_guest_' ],
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['in-progress'], 'Expected regular user to have in-progress status.' );
		$this->assertArrayNotHasKey( 'complete', $result, 'Excluded guest user status should not appear.' );
	}

	public function testCountStatuses_LessonWithQuizStatus_CountsDirectly(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'passed' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type' => 'lesson',
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['passed'] );
	}

	public function testCountStatuses_WithPostInArray_FiltersToSpecifiedPosts(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson1   = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$lesson2   = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		\Sensei_Utils::update_lesson_status( $user_id, $lesson1, 'complete' );
		\Sensei_Utils::update_lesson_status( $user_id, $lesson2, 'in-progress' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'     => 'lesson',
				'post__in' => [ $lesson1 ],
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['complete'], 'Expected filtered post to have complete status.' );
		$this->assertArrayNotHasKey( 'in-progress', $result, 'Excluded post status should not appear.' );
	}

	public function testCountStatuses_WithUserIdArray_FiltersToSpecifiedUsers(): void {
		/* Arrange. */
		global $wpdb;

		$user1     = $this->sensei_factory->user->create();
		$user2     = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		\Sensei_Utils::update_lesson_status( $user1, $lesson_id, 'complete' );
		\Sensei_Utils::update_lesson_status( $user2, $lesson_id, 'in-progress' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'    => 'lesson',
				'user_id' => [ $user1 ],
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['complete'], 'Expected filtered user to have complete status.' );
		$this->assertArrayNotHasKey( 'in-progress', $result, 'Excluded user status should not appear.' );
	}

	public function testGetLessonTotals_WithCompletedLessons_ReturnsCorrectAggregates(): void {
		/* Arrange. */
		global $wpdb;

		$user1     = $this->sensei_factory->user->create();
		$user2     = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		$start_date = current_time( 'mysql' );
		\Sensei_Utils::update_lesson_status( $user1, $lesson_id, 'complete', [ 'start' => $start_date ] );
		\Sensei_Utils::update_lesson_status( $user2, $lesson_id, 'in-progress', [ 'start' => $start_date ] );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_totals( [ $lesson_id ] );

		/* Assert. */
		$this->assertSame( 2, $result['unique_student_count'], 'Expected two distinct students.' );
		$this->assertSame( 2, $result['lesson_start_count'], 'Expected two lesson starts.' );
		$this->assertSame( 1, $result['lesson_completed_count'], 'Expected one completed lesson.' );
		$this->assertGreaterThanOrEqual( 1, $result['days_to_complete_sum'], 'Expected at least one day to complete.' );
	}

	public function testGetLessonTotals_WithUngradedStatus_DoesNotCountAsCompleted(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		$start_date = current_time( 'mysql' );
		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'ungraded', [ 'start' => $start_date ] );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_totals( [ $lesson_id ] );

		/* Assert. */
		$this->assertSame( 0, $result['lesson_completed_count'], 'Ungraded status should NOT count as completed.' );
		$this->assertSame( 0, $result['days_to_complete_sum'], 'Ungraded status should not contribute to days to complete.' );
	}

	public function testGetLessonTotals_WithFailedStatus_DoesNotCountAsCompleted(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		$start_date = current_time( 'mysql' );
		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'failed', [ 'start' => $start_date ] );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_totals( [ $lesson_id ] );

		/* Assert. */
		$this->assertSame( 0, $result['lesson_completed_count'], 'Failed status should NOT count as completed.' );
		$this->assertSame( 0, $result['days_to_complete_sum'], 'Failed status should not contribute to days to complete.' );
	}

	public function testGetLessonTotals_WithPassedStatus_IncludesDaysToComplete(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		$start_date = gmdate( 'Y-m-d H:i:s', strtotime( '-3 days' ) );
		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'passed', [ 'start' => $start_date ] );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_totals( [ $lesson_id ] );

		/* Assert. */
		$this->assertSame( 1, $result['lesson_completed_count'], 'Passed status should count as completed.' );
		$this->assertSame( 4, $result['days_to_complete_sum'], 'Expected 4 days (3 day difference + 1).' );
	}

	public function testGetLessonTotals_WithEmptyLessonIds_ReturnsZeros(): void {
		/* Arrange. */
		global $wpdb;

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_totals( [] );

		/* Assert. */
		$this->assertSame( 0, $result['unique_student_count'] );
		$this->assertSame( 0, $result['lesson_start_count'] );
		$this->assertSame( 0, $result['lesson_completed_count'] );
		$this->assertSame( 0, $result['days_to_complete_sum'] );
	}

	public function testCountStatuses_WithIncludeStatusesOverride_KeepsExcludedUsersForOverrideStatuses(): void {
		/* Arrange. */
		global $wpdb;

		$regular_user = $this->sensei_factory->user->create();
		$guest_user   = $this->sensei_factory->user->create(
			[ 'user_login' => 'sensei_guest_12345' ]
		);
		$course_id    = $this->sensei_factory->course->create();
		$lesson_id    = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		\Sensei_Utils::update_lesson_status( $regular_user, $lesson_id, 'in-progress' );
		\Sensei_Utils::update_lesson_status( $guest_user, $lesson_id, 'ungraded' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'                        => 'lesson',
				'exclude_user_login_prefixes' => [ 'sensei_guest_' ],
				'include_statuses_override'   => [ 'ungraded' ],
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['in-progress'], 'Regular user status should be counted.' );
		$this->assertSame( 1, $result['ungraded'], 'Excluded user with override status should still be counted.' );
	}
}

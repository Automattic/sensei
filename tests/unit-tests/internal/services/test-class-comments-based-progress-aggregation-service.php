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

		$start_date = wp_date( 'Y-m-d H:i:s', strtotime( '-2 days' ) );
		\Sensei_Utils::update_lesson_status( $user1, $lesson_id, 'complete', [ 'start' => $start_date ] );
		\Sensei_Utils::update_lesson_status( $user2, $lesson_id, 'in-progress', [ 'start' => $start_date ] );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_totals( [ $lesson_id ] );

		/* Assert. */
		$this->assertSame( 2, $result['unique_student_count'], 'Expected two distinct students.' );
		$this->assertSame( 2, $result['lesson_start_count'], 'Expected two lesson starts.' );
		$this->assertSame( 1, $result['lesson_completed_count'], 'Expected one completed lesson.' );
		$this->assertSame( 1, $result['days_to_complete_count'], 'Expected one lesson with completion date.' );
		$this->assertSame( 3, $result['days_to_complete_sum'], 'Expected 3 days (2 day difference + 1).' );
	}

	public function testGetLessonTotals_WithUngradedStatus_CountsAsCompletedButNotDaysToComplete(): void {
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
		$this->assertSame( 1, $result['lesson_completed_count'], 'Ungraded status should count as completed.' );
		$this->assertSame( 0, $result['days_to_complete_count'], 'Ungraded status should not have a completion date.' );
		$this->assertSame( 0, $result['days_to_complete_sum'], 'Ungraded status should not contribute to days to complete.' );
	}

	public function testGetLessonTotals_WithFailedStatus_CountsAsCompletedButNotDaysToComplete(): void {
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
		$this->assertSame( 1, $result['lesson_completed_count'], 'Failed status should count as completed.' );
		$this->assertSame( 0, $result['days_to_complete_count'], 'Failed status should not have a completion date.' );
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

		$start_date = wp_date( 'Y-m-d H:i:s', strtotime( '-3 days' ) );
		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'passed', [ 'start' => $start_date ] );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_totals( [ $lesson_id ] );

		/* Assert. */
		$this->assertSame( 1, $result['lesson_completed_count'], 'Passed status should count as completed.' );
		$this->assertSame( 1, $result['days_to_complete_count'], 'Passed status should have a completion date.' );
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
		$this->assertSame( 0, $result['days_to_complete_count'] );
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

	public function testCountStatuses_WithTrashedLesson_ExcludesFromCounts(): void {
		/* Arrange. */
		global $wpdb;

		$user_id      = $this->sensei_factory->user->create();
		$course_id    = $this->sensei_factory->course->create();
		$lesson_id    = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$published_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'in-progress' );
		\Sensei_Utils::update_lesson_status( $user_id, $published_id, 'in-progress' );

		wp_trash_post( $lesson_id );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses( [ 'type' => 'lesson' ] );

		/* Assert. */
		$this->assertSame( 1, $result['in-progress'] ?? 0, 'Trashed lesson progress should be excluded from counts.' );
	}

	public function testGetLessonTotals_WithTrashedLesson_ExcludesFromTotals(): void {
		/* Arrange. */
		global $wpdb;

		$user_id      = $this->sensei_factory->user->create();
		$course_id    = $this->sensei_factory->course->create();
		$lesson_id    = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$published_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		$start_date = current_time( 'mysql' );
		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'in-progress', [ 'start' => $start_date ] );
		\Sensei_Utils::update_lesson_status( $user_id, $published_id, 'in-progress', [ 'start' => $start_date ] );

		wp_trash_post( $lesson_id );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_totals( [ $lesson_id, $published_id ] );

		/* Assert. */
		$this->assertSame( 1, $result['unique_student_count'], 'Trashed lesson progress should not count towards unique students.' );
		$this->assertSame( 1, $result['lesson_start_count'], 'Trashed lesson progress should not count towards lesson starts.' );
	}

	public function testCountStatuses_LessonWithQuizButNoAnswers_IncludedByDefault(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$quiz_id   = $this->sensei_factory->quiz->create(
			[
				'post_parent' => $lesson_id,
				'meta_input'  => [ '_quiz_lesson' => $lesson_id ],
			]
		);
		update_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );

		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'complete' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type' => 'lesson',
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['complete'], 'Completed lesson with quiz but no answers should be included by default.' );
	}

	public function testCountUngradedQuizzes_NoUngradedComments_ReturnsZero(): void {
		/* Arrange. */
		global $wpdb;

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_ungraded_quizzes();

		/* Assert. */
		$this->assertSame( 0, $result );
	}

	public function testCountUngradedQuizzes_OneUngradedComment_ReturnsOne(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$lesson_id = $this->sensei_factory->lesson->create();

		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'ungraded' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_ungraded_quizzes();

		/* Assert. */
		$this->assertSame( 1, $result );
	}

	public function testCountUngradedQuizzes_NonUngradedStatuses_NotCounted(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$lesson_id = $this->sensei_factory->lesson->create();

		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'graded' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_ungraded_quizzes();

		/* Assert. */
		$this->assertSame( 0, $result, 'Graded lesson should not be counted as ungraded.' );
	}

	public function testCountUngradedQuizzes_TrashedLesson_NotCounted(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$lesson_id = $this->sensei_factory->lesson->create( array( 'post_status' => 'trash' ) );

		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'ungraded' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_ungraded_quizzes();

		/* Assert. */
		$this->assertSame( 0, $result, 'Ungraded lesson on trashed post should not be counted.' );
	}

	public function testCountUngradedQuizzes_PrivateLesson_Counted(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$lesson_id = $this->sensei_factory->lesson->create( array( 'post_status' => 'private' ) );

		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'ungraded' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_ungraded_quizzes();

		/* Assert. */
		$this->assertSame( 1, $result, 'Ungraded lesson on private post should be counted.' );
	}

	public function testCountUngradedQuizzes_DraftLesson_NotCounted(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$lesson_id = $this->sensei_factory->lesson->create( array( 'post_status' => 'draft' ) );

		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'ungraded' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_ungraded_quizzes();

		/* Assert. */
		$this->assertSame( 0, $result, 'Ungraded lesson on draft post should not be counted.' );
	}

	public function testCountUngradedQuizzes_WithPostInFilter_RestrictsToLessons(): void {
		/* Arrange. */
		global $wpdb;

		$user1    = $this->sensei_factory->user->create();
		$user2    = $this->sensei_factory->user->create();
		$lesson_a = $this->sensei_factory->lesson->create();
		$lesson_b = $this->sensei_factory->lesson->create();

		// Lesson A: 1 ungraded. Lesson B: 2 ungraded.
		\Sensei_Utils::update_lesson_status( $user1, $lesson_a, 'ungraded' );
		\Sensei_Utils::update_lesson_status( $user1, $lesson_b, 'ungraded' );
		\Sensei_Utils::update_lesson_status( $user2, $lesson_b, 'ungraded' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act & Assert. */
		$this->assertSame( 1, $service->count_ungraded_quizzes( array( 'post__in' => array( $lesson_a ) ) ), 'post__in=[A] should count only lesson A ungraded.' );
		$this->assertSame( 2, $service->count_ungraded_quizzes( array( 'post__in' => array( $lesson_b ) ) ), 'post__in=[B] should count only lesson B ungraded.' );
	}

	public function testCountUngradedQuizzes_WithExcludeUserLoginPrefixes_ExcludesMatchingUsers(): void {
		/* Arrange. */
		global $wpdb;

		$regular_user = $this->sensei_factory->user->create();
		$guest_user   = $this->sensei_factory->user->create(
			array( 'user_login' => 'sensei_guest_42' )
		);
		$lesson_id    = $this->sensei_factory->lesson->create();

		\Sensei_Utils::update_lesson_status( $regular_user, $lesson_id, 'ungraded' );
		\Sensei_Utils::update_lesson_status( $guest_user, $lesson_id, 'ungraded' );

		$service = new Comments_Based_Progress_Aggregation_Service( $wpdb );

		/* Act & Assert. */
		$this->assertSame( 1, $service->count_ungraded_quizzes( array( 'exclude_user_login_prefixes' => array( 'sensei_guest_' ) ) ), 'Matching prefix should exclude the guest user.' );
		$this->assertSame( 2, $service->count_ungraded_quizzes( array( 'exclude_user_login_prefixes' => array( 'no_match_' ) ) ), 'Non-matching prefix should leave both users counted.' );
	}
}

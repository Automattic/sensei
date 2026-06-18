<?php

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Tables_Based_Progress_Aggregation_Service;

/**
 * Class Tables_Based_Progress_Aggregation_Service_Test.
 *
 * @covers \Sensei\Internal\Services\Tables_Based_Progress_Aggregation_Service
 */
class Tables_Based_Progress_Aggregation_Service_Test extends \WP_UnitTestCase {

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

	/**
	 * Insert a progress row directly into the HPPS progress table.
	 *
	 * @param int         $post_id        The post ID.
	 * @param int         $user_id        The user ID.
	 * @param string      $type           The progress type ('course' or 'lesson').
	 * @param string      $status         The progress status.
	 */
	private function insert_progress_with_dates( int $post_id, int $user_id, string $type, string $status, string $started_at, string $completed_at ): void {
		$wpdb  = $GLOBALS['wpdb'];
		$table = $wpdb->prefix . 'sensei_lms_progress';
		$now   = current_time( 'mysql' );
		$data  = [
			'post_id'      => $post_id,
			'user_id'      => $user_id,
			'type'         => $type,
			'status'       => $status,
			'started_at'   => $started_at,
			'completed_at' => $completed_at,
			'created_at'   => $now,
			'updated_at'   => $now,
		];

		$format = [ '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test helper inserting directly into custom table.
		$wpdb->insert( $table, $data, $format );
	}

	private function insert_progress( int $post_id, int $user_id, string $type, string $status, ?string $completed_at = null ): void {
		$wpdb   = $GLOBALS['wpdb'];
		$table  = $wpdb->prefix . 'sensei_lms_progress';
		$now    = current_time( 'mysql' );
		$data   = [
			'post_id'    => $post_id,
			'user_id'    => $user_id,
			'type'       => $type,
			'status'     => $status,
			'started_at' => $now,
			'created_at' => $now,
			'updated_at' => $now,
		];
		$format = [ '%d', '%d', '%s', '%s', '%s', '%s', '%s' ];

		if ( null !== $completed_at ) {
			$data['completed_at'] = $completed_at;
			$format[]             = '%s';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test helper inserting directly into custom table.
		$wpdb->insert( $table, $data, $format );
	}

	/**
	 * Insert a quiz submission row directly into the HPPS quiz submissions table.
	 *
	 * @param int $quiz_id The quiz post ID.
	 * @param int $user_id The user ID.
	 */
	private function insert_quiz_submission( int $quiz_id, int $user_id ): void {
		$wpdb  = $GLOBALS['wpdb'];
		$table = $wpdb->prefix . 'sensei_lms_quiz_submissions';
		$now   = current_time( 'mysql' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test helper inserting directly into custom table.
		$wpdb->insert(
			$table,
			[
				'quiz_id'    => $quiz_id,
				'user_id'    => $user_id,
				'created_at' => $now,
				'updated_at' => $now,
			],
			[ '%d', '%d', '%s', '%s' ]
		);
	}

	public function testCountStatuses_LessonType_ReturnsStatusCounts(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress' );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

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

		$this->insert_progress( $lesson1, $user_id, 'lesson', 'in-progress' );
		$this->insert_progress( $lesson2, $user_id, 'lesson', 'complete' );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

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

		$this->insert_progress( $lesson1, $user_id, 'lesson', 'complete' );
		$this->insert_progress( $lesson2, $user_id, 'lesson', 'in-progress' );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

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

		$this->insert_progress( $lesson_id, $user1, 'lesson', 'complete' );
		$this->insert_progress( $lesson_id, $user2, 'lesson', 'in-progress' );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'    => 'lesson',
				'post_id' => $lesson_id,
				'user_id' => [ $user1 ],
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['complete'], 'Expected filtered user to have complete status.' );
		$this->assertArrayNotHasKey( 'in-progress', $result, 'Excluded user status should not appear.' );
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

		$this->insert_progress( $lesson_id, $regular_user, 'lesson', 'in-progress' );
		$this->insert_progress( $lesson_id, $guest_user, 'lesson', 'complete' );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

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

	public function testCountStatuses_LessonWithQuiz_UsesQuizStatusInsteadOfLessonStatus(): void {
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
		update_post_meta( $lesson_id, '_quiz_has_questions', 1 );

		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'complete' );
		$this->insert_progress( $quiz_id, $user_id, 'quiz', 'graded' );
		$this->insert_quiz_submission( $quiz_id, $user_id );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type' => 'lesson',
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['graded'] );
		$this->assertArrayNotHasKey( 'complete', $result, 'Quiz status should replace lesson status, not add to it.' );
	}

	public function testCountStatuses_LessonWithoutQuiz_ReturnsOnlyLessonStatuses(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress' );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type' => 'lesson',
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['in-progress'], 'Expected lesson without quiz to have in-progress status.' );
		$this->assertArrayNotHasKey( 'graded', $result, 'Quiz status should not appear for lesson without quiz.' );
		$this->assertArrayNotHasKey( 'passed', $result, 'Quiz status should not appear for lesson without quiz.' );
	}

	public function testCountStatuses_LessonWithMultipleQuizStatuses_CountsAll(): void {
		/* Arrange. */
		global $wpdb;

		$user1     = $this->sensei_factory->user->create();
		$user2     = $this->sensei_factory->user->create();
		$user3     = $this->sensei_factory->user->create();
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
		update_post_meta( $lesson_id, '_quiz_has_questions', 1 );

		$this->insert_progress( $lesson_id, $user1, 'lesson', 'complete' );
		$this->insert_progress( $quiz_id, $user1, 'quiz', 'graded' );
		$this->insert_quiz_submission( $quiz_id, $user1 );
		$this->insert_progress( $lesson_id, $user2, 'lesson', 'complete' );
		$this->insert_progress( $quiz_id, $user2, 'quiz', 'passed' );
		$this->insert_quiz_submission( $quiz_id, $user2 );
		$this->insert_progress( $lesson_id, $user3, 'lesson', 'in-progress' );
		$this->insert_progress( $quiz_id, $user3, 'quiz', 'ungraded' );
		$this->insert_quiz_submission( $quiz_id, $user3 );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type' => 'lesson',
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['graded'], 'Expected one graded quiz status.' );
		$this->assertSame( 1, $result['passed'], 'Expected one passed quiz status.' );
		$this->assertSame( 1, $result['ungraded'], 'Expected one ungraded quiz status.' );
		$this->assertArrayNotHasKey( 'complete', $result, 'Raw lesson status should not appear when quiz status exists.' );
		$this->assertArrayNotHasKey( 'in-progress', $result, 'Raw lesson status should not appear when quiz status exists.' );
	}

	/**
	 * Verifies lessons with an "included" post_status are surfaced.
	 *
	 * @dataProvider includedLessonStatusesProvider
	 */
	public function testCountStatuses_WithIncludedLessonStatus_IncludesInCounts( string $included_status ): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);

		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Bypass WP filters to test SQL post_status filter directly.
		$wpdb->update( $wpdb->posts, array( 'post_status' => $included_status ), array( 'ID' => $lesson_id ) );
		clean_post_cache( $lesson_id );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses( array( 'type' => 'lesson' ) );

		/* Assert. */
		$this->assertSame( 1, $result['in-progress'] ?? 0, "Lesson with post_status '{$included_status}' should be included in counts." );
	}

	/**
	 * Verifies lessons with an "excluded" post_status are skipped.
	 *
	 * @dataProvider excludedLessonStatusesProvider
	 */
	public function testCountStatuses_WithExcludedLessonStatus_ExcludesFromCounts( string $excluded_status ): void {
		/* Arrange. */
		global $wpdb;

		$user_id     = $this->sensei_factory->user->create();
		$course_id   = $this->sensei_factory->course->create();
		$excluded_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);

		$this->insert_progress( $excluded_id, $user_id, 'lesson', 'in-progress' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Bypass WP filters to test SQL post_status filter directly.
		$wpdb->update( $wpdb->posts, array( 'post_status' => $excluded_status ), array( 'ID' => $excluded_id ) );
		clean_post_cache( $excluded_id );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses( array( 'type' => 'lesson' ) );

		/* Assert. */
		$this->assertSame( 0, $result['in-progress'] ?? 0, "Lesson with post_status '{$excluded_status}' should be excluded from counts." );
	}

	/**
	 * Verifies totals across all aggregate fields for lessons with an "included" post_status.
	 *
	 * @dataProvider includedLessonStatusesProvider
	 */
	public function testGetLessonTotals_WithCompletedLessons_ReturnsCorrectAggregates( string $included_status ): void {
		/* Arrange. */
		global $wpdb;

		$user1     = $this->sensei_factory->user->create();
		$user2     = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		$started_at   = '2024-01-01 10:00:00';
		$completed_at = '2024-01-03 10:00:00';
		$this->insert_progress_with_dates( $lesson_id, $user1, 'lesson', 'complete', $started_at, $completed_at );
		$this->insert_progress( $lesson_id, $user2, 'lesson', 'in-progress' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Bypass WP filters to test SQL post_status filter directly.
		$wpdb->update( $wpdb->posts, array( 'post_status' => $included_status ), array( 'ID' => $lesson_id ) );
		clean_post_cache( $lesson_id );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_totals( [ $lesson_id ] );

		/* Assert. */
		$this->assertSame( 2, $result['unique_student_count'], 'Expected two distinct students.' );
		$this->assertSame( 2, $result['lesson_start_count'], 'Expected two lesson starts.' );
		$this->assertSame( 1, $result['lesson_completed_count'], 'Expected one completed lesson.' );
		$this->assertSame( 1, $result['days_to_complete_count'], 'Expected one lesson with completion date.' );
		$this->assertSame( 3, $result['days_to_complete_sum'], 'Expected 3 days (2 day difference + 1).' );
	}

	public function testGetLessonTotals_WithInProgressQuizStatus_DoesNotCountAsCompleted(): void {
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
		update_post_meta( $lesson_id, '_quiz_has_questions', 1 );

		// Lesson is complete but quiz is still in-progress — COALESCE should
		// produce 'in-progress' which is NOT in the completed statuses list.
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'complete', current_time( 'mysql' ) );
		$this->insert_progress( $quiz_id, $user_id, 'quiz', 'in-progress' );
		$this->insert_quiz_submission( $quiz_id, $user_id );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_totals( [ $lesson_id ] );

		/* Assert. */
		$this->assertSame( 1, $result['unique_student_count'], 'Expected one student.' );
		$this->assertSame( 1, $result['lesson_start_count'], 'Expected one lesson start.' );
		$this->assertSame( 0, $result['lesson_completed_count'], 'In-progress quiz status should NOT count as completed.' );
	}

	public function testGetLessonTotals_WithUngradedQuizStatus_CountsAsCompletedButNotDaysToComplete(): void {
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

		// Lesson is in-progress, quiz is ungraded (submitted but not yet graded).
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress' );
		$this->insert_progress( $quiz_id, $user_id, 'quiz', 'ungraded' );
		$this->insert_quiz_submission( $quiz_id, $user_id );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_totals( [ $lesson_id ] );

		/* Assert. */
		$this->assertSame( 1, $result['lesson_completed_count'], 'Ungraded quiz status should count as completed.' );
		$this->assertSame( 0, $result['days_to_complete_count'], 'Ungraded quiz should not have a completion date.' );
		$this->assertSame( 0, $result['days_to_complete_sum'], 'Ungraded quiz should not contribute to days to complete.' );
	}

	public function includedLessonStatusesProvider(): array {
		return array(
			'publish' => array( 'publish' ),
			'private' => array( 'private' ),
		);
	}

	public function excludedLessonStatusesProvider(): array {
		return array(
			'draft'      => array( 'draft' ),
			'pending'    => array( 'pending' ),
			'future'     => array( 'future' ),
			'auto-draft' => array( 'auto-draft' ),
			'trash'      => array( 'trash' ),
		);
	}

	public function testGetLessonTotals_WithFailedQuizStatus_CountsAsCompletedButNotDaysToComplete(): void {
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

		// Lesson is in-progress, quiz is failed (pass required, student didn't pass).
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress' );
		$this->insert_progress( $quiz_id, $user_id, 'quiz', 'failed' );
		$this->insert_quiz_submission( $quiz_id, $user_id );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_totals( [ $lesson_id ] );

		/* Assert. */
		$this->assertSame( 1, $result['lesson_completed_count'], 'Failed quiz status should count as completed.' );
		$this->assertSame( 0, $result['days_to_complete_count'], 'Failed quiz should not have a completion date.' );
		$this->assertSame( 0, $result['days_to_complete_sum'], 'Failed quiz should not contribute to days to complete.' );
	}

	public function testGetLessonTotals_WithPassedQuiz_IncludesDaysToComplete(): void {
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

		$started_at   = '2024-01-01 10:00:00';
		$completed_at = '2024-01-04 10:00:00';
		$this->insert_progress_with_dates( $lesson_id, $user_id, 'lesson', 'complete', $started_at, $completed_at );
		$this->insert_progress( $quiz_id, $user_id, 'quiz', 'passed' );
		$this->insert_quiz_submission( $quiz_id, $user_id );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_totals( [ $lesson_id ] );

		/* Assert. */
		$this->assertSame( 1, $result['lesson_completed_count'], 'Passed quiz should count as completed.' );
		$this->assertSame( 1, $result['days_to_complete_count'], 'Passed quiz should have a completion date.' );
		$this->assertSame( 4, $result['days_to_complete_sum'], 'Expected 4 days (3 day difference + 1).' );
	}

	public function testGetLessonTotals_WithEmptyLessonIds_ReturnsZeros(): void {
		/* Arrange. */
		global $wpdb;

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_totals( [] );

		/* Assert. */
		$this->assertSame( 0, $result['unique_student_count'] );
		$this->assertSame( 0, $result['lesson_start_count'] );
		$this->assertSame( 0, $result['lesson_completed_count'] );
		$this->assertSame( 0, $result['days_to_complete_count'] );
		$this->assertSame( 0, $result['days_to_complete_sum'] );
	}

	/**
	 * Verifies lessons with an "excluded" post_status are skipped from totals.
	 *
	 * @dataProvider excludedLessonStatusesProvider
	 */
	public function testGetLessonTotals_WithExcludedLessonStatus_ReturnsZeros( string $excluded_status ): void {
		/* Arrange. */
		global $wpdb;

		$user1     = $this->sensei_factory->user->create();
		$user2     = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);

		$started_at   = '2024-01-01 10:00:00';
		$completed_at = '2024-01-03 10:00:00';
		$this->insert_progress_with_dates( $lesson_id, $user1, 'lesson', 'complete', $started_at, $completed_at );
		$this->insert_progress( $lesson_id, $user2, 'lesson', 'in-progress' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Bypass WP filters to test SQL post_status filter directly.
		$wpdb->update( $wpdb->posts, array( 'post_status' => $excluded_status ), array( 'ID' => $lesson_id ) );
		clean_post_cache( $lesson_id );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_totals( array( $lesson_id ) );

		/* Assert. */
		$this->assertSame( 0, $result['unique_student_count'], "Lesson with post_status '{$excluded_status}' should not count towards unique students." );
		$this->assertSame( 0, $result['lesson_start_count'], "Lesson with post_status '{$excluded_status}' should not count towards lesson starts." );
		$this->assertSame( 0, $result['lesson_completed_count'], "Lesson with post_status '{$excluded_status}' should not count towards completions." );
		$this->assertSame( 0, $result['days_to_complete_count'], "Lesson with post_status '{$excluded_status}' should not have a completion date." );
		$this->assertSame( 0, $result['days_to_complete_sum'], "Lesson with post_status '{$excluded_status}' should not contribute to days to complete." );
	}

	public function testGetLessonTotals_WithUtcDatesNearMidnight_ConvertsToLocalTimeBeforeDatediff(): void {
		/* Arrange. */
		global $wpdb;

		// Set site timezone to UTC-5 (e.g. America/New_York EST).
		$original_offset   = get_option( 'gmt_offset' );
		$original_timezone = get_option( 'timezone_string' );
		update_option( 'gmt_offset', -5 );
		update_option( 'timezone_string', '' );

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		// UTC dates span two days (Jan 1 05:00 → Jan 2 04:00),
		// but in UTC-5 they're same day (Jan 1 00:00 → Jan 1 23:00).
		$started_at   = '2024-01-01 05:00:00';
		$completed_at = '2024-01-02 04:00:00';
		$this->insert_progress_with_dates( $lesson_id, $user_id, 'lesson', 'complete', $started_at, $completed_at );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_totals( [ $lesson_id ] );

		/* Assert. */
		$this->assertSame( 1, $result['days_to_complete_sum'], 'UTC dates spanning midnight should be 1 day in local time (UTC-5).' );

		/* Cleanup. */
		update_option( 'gmt_offset', $original_offset );
		update_option( 'timezone_string', $original_timezone );
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

		$this->insert_progress( $lesson_id, $regular_user, 'lesson', 'in-progress' );
		$this->insert_progress( $lesson_id, $guest_user, 'lesson', 'ungraded' );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

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

	public function testCountStatuses_LessonWithQuizButNoSubmission_UsesQuizStatus(): void {
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
		update_post_meta( $lesson_id, '_quiz_has_questions', 1 );

		// Quiz progress exists but no quiz submission (e.g. migration phantom or lost data).
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'complete' );
		$this->insert_progress( $quiz_id, $user_id, 'quiz', 'passed' );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type' => 'lesson',
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['passed'], 'Quiz progress status should take precedence over lesson status.' );
		$this->assertArrayNotHasKey( 'complete', $result, 'Lesson status should not appear when quiz progress exists.' );
	}

	public function testCountStatuses_CourseType_ReturnsStatusCounts(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();

		$this->insert_progress( $course_id, $user_id, 'course', 'in-progress' );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type' => 'course',
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['in-progress'] );
	}

	public function testCountStatuses_CourseWithMultipleStatuses_CountsAll(): void {
		/* Arrange. */
		global $wpdb;

		$user1     = $this->sensei_factory->user->create();
		$user2     = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();

		$this->insert_progress( $course_id, $user1, 'course', 'in-progress' );
		$this->insert_progress( $course_id, $user2, 'course', 'complete' );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type' => 'course',
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['in-progress'], 'Expected one in-progress course.' );
		$this->assertSame( 1, $result['complete'], 'Expected one complete course.' );
	}

	public function testCountStatuses_TrashedCourse_ExcludedFromCounts(): void {
		/* Arrange. */
		global $wpdb;

		$user_id = $this->sensei_factory->user->create();
		$course1 = $this->sensei_factory->course->create();
		$course2 = $this->sensei_factory->course->create();

		$this->insert_progress( $course1, $user_id, 'course', 'complete' );
		$this->insert_progress( $course2, $user_id, 'course', 'in-progress' );

		wp_trash_post( $course2 );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type' => 'course',
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['complete'], 'Non-trashed course should be counted.' );
		$this->assertArrayNotHasKey( 'in-progress', $result, 'Trashed course status should not appear.' );
	}

	public function testCountUngradedQuizzes_NoUngradedQuizzes_ReturnsZero(): void {
		/* Arrange. */
		global $wpdb;

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_ungraded_quizzes();

		/* Assert. */
		$this->assertSame( 0, $result );
	}

	public function testCountUngradedQuizzes_OneUngradedQuiz_ReturnsOne(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_id   = $this->sensei_factory->quiz->create();
		update_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );

		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress' );
		$this->insert_progress( $quiz_id, $user_id, 'quiz', 'ungraded' );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_ungraded_quizzes();

		/* Assert. */
		$this->assertSame( 1, $result );
	}

	public function testCountUngradedQuizzes_QuizWithoutLessonProgressRow_NotCounted(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_id   = $this->sensei_factory->quiz->create();
		update_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );

		$this->insert_progress( $quiz_id, $user_id, 'quiz', 'ungraded' );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_ungraded_quizzes();

		/* Assert. */
		$this->assertSame( 0, $result, 'Ungraded quiz without a matching lesson progress row should not be counted.' );
	}

	public function testCountUngradedQuizzes_NonUngradedStatuses_NotCounted(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_id   = $this->sensei_factory->quiz->create();
		update_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );

		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress' );
		$this->insert_progress( $quiz_id, $user_id, 'quiz', 'graded' );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_ungraded_quizzes();

		/* Assert. */
		$this->assertSame( 0, $result, 'Graded quiz should not be counted as ungraded.' );
	}

	public function testCountUngradedQuizzes_TrashedLesson_NotCounted(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array(
				'post_status' => 'trash',
				'meta_input'  => array( '_lesson_course' => $course_id ),
			)
		);
		$quiz_id   = $this->sensei_factory->quiz->create();
		update_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );

		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress' );
		$this->insert_progress( $quiz_id, $user_id, 'quiz', 'ungraded' );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_ungraded_quizzes();

		/* Assert. */
		$this->assertSame( 0, $result, 'Ungraded quiz on trashed lesson should not be counted.' );
	}

	public function testCountUngradedQuizzes_PrivateLesson_Counted(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array(
				'post_status' => 'private',
				'meta_input'  => array( '_lesson_course' => $course_id ),
			)
		);
		$quiz_id   = $this->sensei_factory->quiz->create();
		update_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );

		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress' );
		$this->insert_progress( $quiz_id, $user_id, 'quiz', 'ungraded' );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_ungraded_quizzes();

		/* Assert. */
		$this->assertSame( 1, $result, 'Ungraded quiz on private lesson should be counted.' );
	}

	public function testCountUngradedQuizzes_DraftLesson_NotCounted(): void {
		/* Arrange. */
		global $wpdb;

		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array(
				'post_status' => 'draft',
				'meta_input'  => array( '_lesson_course' => $course_id ),
			)
		);
		$quiz_id   = $this->sensei_factory->quiz->create();
		update_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );

		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress' );
		$this->insert_progress( $quiz_id, $user_id, 'quiz', 'ungraded' );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_ungraded_quizzes();

		/* Assert. */
		$this->assertSame( 0, $result, 'Ungraded quiz on draft lesson should not be counted.' );
	}

	public function testCountUngradedQuizzes_WithPostInFilter_RestrictsToLessons(): void {
		/* Arrange. */
		global $wpdb;

		$user1     = $this->sensei_factory->user->create();
		$user2     = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_a  = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$lesson_b  = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_a_id = $this->sensei_factory->quiz->create();
		$quiz_b_id = $this->sensei_factory->quiz->create();
		update_post_meta( $lesson_a, '_lesson_quiz', $quiz_a_id );
		update_post_meta( $lesson_b, '_lesson_quiz', $quiz_b_id );

		// Lesson A: 1 ungraded quiz. Lesson B: 2 ungraded quizzes.
		$this->insert_progress( $lesson_a, $user1, 'lesson', 'in-progress' );
		$this->insert_progress( $quiz_a_id, $user1, 'quiz', 'ungraded' );
		$this->insert_progress( $lesson_b, $user1, 'lesson', 'in-progress' );
		$this->insert_progress( $quiz_b_id, $user1, 'quiz', 'ungraded' );
		$this->insert_progress( $lesson_b, $user2, 'lesson', 'in-progress' );
		$this->insert_progress( $quiz_b_id, $user2, 'quiz', 'ungraded' );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act & Assert. */
		$this->assertSame( 1, $service->count_ungraded_quizzes( array( 'post__in' => array( $lesson_a ) ) ), 'post__in=[A] should count only lesson A ungraded quizzes.' );
		$this->assertSame( 2, $service->count_ungraded_quizzes( array( 'post__in' => array( $lesson_b ) ) ), 'post__in=[B] should count only lesson B ungraded quizzes.' );
	}

	public function testCountUngradedQuizzes_WithExcludeUserLoginPrefixes_ExcludesMatchingUsers(): void {
		/* Arrange. */
		global $wpdb;

		$regular_user = $this->sensei_factory->user->create();
		$guest_user   = $this->sensei_factory->user->create(
			array( 'user_login' => 'sensei_guest_42' )
		);
		$course_id    = $this->sensei_factory->course->create();
		$lesson_id    = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_id      = $this->sensei_factory->quiz->create();
		update_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );

		$this->insert_progress( $lesson_id, $regular_user, 'lesson', 'in-progress' );
		$this->insert_progress( $quiz_id, $regular_user, 'quiz', 'ungraded' );
		$this->insert_progress( $lesson_id, $guest_user, 'lesson', 'in-progress' );
		$this->insert_progress( $quiz_id, $guest_user, 'quiz', 'ungraded' );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act & Assert. */
		$this->assertSame( 1, $service->count_ungraded_quizzes( array( 'exclude_user_login_prefixes' => array( 'sensei_guest_' ) ) ), 'Matching prefix should exclude the guest user.' );
		$this->assertSame( 2, $service->count_ungraded_quizzes( array( 'exclude_user_login_prefixes' => array( 'no_match_' ) ) ), 'Non-matching prefix should leave both users counted.' );
	}
}

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
	 * @param int|null    $parent_post_id The parent post ID.
	 */
	private function insert_progress( int $post_id, int $user_id, string $type, string $status, ?int $parent_post_id = null ): void {
		$wpdb  = $GLOBALS['wpdb'];
		$table = $wpdb->prefix . 'sensei_lms_progress';
		$now   = current_time( 'mysql' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test helper inserting directly into custom table.
		$wpdb->insert(
			$table,
			[
				'post_id'        => $post_id,
				'user_id'        => $user_id,
				'parent_post_id' => $parent_post_id,
				'type'           => $type,
				'status'         => $status,
				'started_at'     => $now,
				'created_at'     => $now,
				'updated_at'     => $now,
			],
			[ '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' ]
		);
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

		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress', $course_id );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'    => 'lesson',
				'post_id' => $lesson_id,
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

		$this->insert_progress( $lesson1, $user_id, 'lesson', 'in-progress', $course_id );
		$this->insert_progress( $lesson2, $user_id, 'lesson', 'complete', $course_id );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'    => 'lesson',
				'post_id' => $lesson1,
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['in-progress'] );
		$this->assertArrayNotHasKey( 'complete', $result );
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

		$this->insert_progress( $lesson1, $user_id, 'lesson', 'complete', $course_id );
		$this->insert_progress( $lesson2, $user_id, 'lesson', 'complete', $course_id );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'     => 'lesson',
				'post__in' => [ $lesson1 ],
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['complete'] );
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

		$this->insert_progress( $lesson_id, $user1, 'lesson', 'complete', $course_id );
		$this->insert_progress( $lesson_id, $user2, 'lesson', 'complete', $course_id );

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
		$this->assertSame( 1, $result['complete'] );
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

		$this->insert_progress( $lesson_id, $regular_user, 'lesson', 'in-progress', $course_id );
		$this->insert_progress( $lesson_id, $guest_user, 'lesson', 'in-progress', $course_id );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'                        => 'lesson',
				'post_id'                     => $lesson_id,
				'exclude_user_login_prefixes' => [ 'sensei_guest_' ],
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['in-progress'] );
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

		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'complete', $course_id );
		$this->insert_progress( $quiz_id, $user_id, 'quiz', 'graded', $lesson_id );
		$this->insert_quiz_submission( $quiz_id, $user_id );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'    => 'lesson',
				'post_id' => $lesson_id,
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

		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress', $course_id );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'    => 'lesson',
				'post_id' => $lesson_id,
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['in-progress'] );
		$this->assertArrayNotHasKey( 'graded', $result );
		$this->assertArrayNotHasKey( 'passed', $result );
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

		$this->insert_progress( $lesson_id, $user1, 'lesson', 'complete', $course_id );
		$this->insert_progress( $quiz_id, $user1, 'quiz', 'graded', $lesson_id );
		$this->insert_quiz_submission( $quiz_id, $user1 );
		$this->insert_progress( $lesson_id, $user2, 'lesson', 'complete', $course_id );
		$this->insert_progress( $quiz_id, $user2, 'quiz', 'passed', $lesson_id );
		$this->insert_quiz_submission( $quiz_id, $user2 );
		$this->insert_progress( $lesson_id, $user3, 'lesson', 'in-progress', $course_id );
		$this->insert_progress( $quiz_id, $user3, 'quiz', 'ungraded', $lesson_id );
		$this->insert_quiz_submission( $quiz_id, $user3 );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'    => 'lesson',
				'post_id' => $lesson_id,
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['graded'] );
		$this->assertSame( 1, $result['passed'] );
		$this->assertSame( 1, $result['ungraded'] );
		$this->assertArrayNotHasKey( 'complete', $result );
		$this->assertArrayNotHasKey( 'in-progress', $result );
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

		$this->insert_progress( $lesson_id, $regular_user, 'lesson', 'in-progress', $course_id );
		$this->insert_progress( $lesson_id, $guest_user, 'lesson', 'ungraded', $course_id );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'                        => 'lesson',
				'post_id'                     => $lesson_id,
				'exclude_user_login_prefixes' => [ 'sensei_guest_' ],
				'include_statuses_override'   => [ 'ungraded' ],
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['in-progress'] );
		$this->assertSame( 1, $result['ungraded'] );
	}

	public function testCountStatuses_LessonWithQuizButNoSubmission_UsesLessonStatus(): void {
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
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'complete', $course_id );
		$this->insert_progress( $quiz_id, $user_id, 'quiz', 'passed', $lesson_id );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type'    => 'lesson',
				'post_id' => $lesson_id,
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['complete'], 'Quiz progress without submission should fall back to lesson status.' );
		$this->assertArrayNotHasKey( 'passed', $result, 'Quiz progress without submission should not count as graded.' );
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
				'type'    => 'course',
				'post_id' => $course_id,
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
				'type'    => 'course',
				'post_id' => $course_id,
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['in-progress'] );
		$this->assertSame( 1, $result['complete'] );
	}

	public function testCountStatuses_TrashedCourse_ExcludedFromCounts(): void {
		/* Arrange. */
		global $wpdb;

		$user_id = $this->sensei_factory->user->create();
		$course1 = $this->sensei_factory->course->create();
		$course2 = $this->sensei_factory->course->create();

		$this->insert_progress( $course1, $user_id, 'course', 'complete' );
		$this->insert_progress( $course2, $user_id, 'course', 'complete' );

		wp_trash_post( $course2 );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type' => 'course',
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['complete'] );
	}

	public function testCountStatuses_TrashedLesson_ExcludedFromCounts(): void {
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

		$this->insert_progress( $lesson1, $user_id, 'lesson', 'complete', $course_id );
		$this->insert_progress( $lesson2, $user_id, 'lesson', 'complete', $course_id );

		wp_trash_post( $lesson2 );

		$service = new Tables_Based_Progress_Aggregation_Service( $wpdb );

		/* Act. */
		$result = $service->count_statuses(
			[
				'type' => 'lesson',
			]
		);

		/* Assert. */
		$this->assertSame( 1, $result['complete'] );
	}
}

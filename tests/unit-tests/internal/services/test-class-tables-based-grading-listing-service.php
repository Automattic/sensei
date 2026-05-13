<?php

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Tables_Based_Grading_Listing_Service;
use Sensei\Internal\Services\Grading_Item;

/**
 * Class Tables_Based_Grading_Listing_Service_Test.
 *
 * @covers \Sensei\Internal\Services\Tables_Based_Grading_Listing_Service
 */
class Tables_Based_Grading_Listing_Service_Test extends \WP_UnitTestCase {

	private $sensei_factory;

	public function setUp(): void {
		parent::setUp();
		$this->sensei_factory = new \Sensei_Factory();
	}

	/**
	 * Insert a progress row directly into the HPPS progress table.
	 *
	 * @param int      $post_id        The post ID.
	 * @param int      $user_id        The user ID.
	 * @param string   $type           The progress type.
	 * @param string   $status         The progress status.
	 * @param int|null $parent_post_id The parent post ID.
	 */
	private function insert_progress( int $post_id, int $user_id, string $type, string $status, ?int $parent_post_id = null ): void {
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
		if ( null !== $parent_post_id ) {
			$data['parent_post_id'] = $parent_post_id;
			$format[]               = '%d';
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test helper.
		$wpdb->insert( $table, $data, $format );
	}

	/**
	 * Insert a quiz submission row.
	 *
	 * @param int      $quiz_id     The quiz post ID.
	 * @param int      $user_id     The user ID.
	 * @param int|null $final_grade The final grade.
	 */
	private function insert_quiz_submission( int $quiz_id, int $user_id, ?int $final_grade = null ): void {
		$wpdb   = $GLOBALS['wpdb'];
		$table  = $wpdb->prefix . 'sensei_lms_quiz_submissions';
		$now    = current_time( 'mysql' );
		$data   = [
			'quiz_id'    => $quiz_id,
			'user_id'    => $user_id,
			'created_at' => $now,
			'updated_at' => $now,
		];
		$format = [ '%d', '%d', '%s', '%s' ];
		if ( null !== $final_grade ) {
			$data['final_grade'] = $final_grade;
			$format[]            = '%d';
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test helper.
		$wpdb->insert( $table, $data, $format );
	}

	/**
	 * Build default query args for get_lesson_progress_items.
	 *
	 * @param array $overrides Args to override.
	 * @return array
	 */
	private function get_default_args( array $overrides = [] ): array {
		return array_merge(
			[
				'type'    => 'sensei_lesson_status',
				'number'  => 10,
				'offset'  => 0,
				'orderby' => '',
				'order'   => 'DESC',
				'status'  => 'any',
			],
			$overrides
		);
	}

	public function testGetLessonProgressItems_WithLessonStatus_ReturnsGradingItems(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress', $course_id );

		$service = new Tables_Based_Grading_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_progress_items(
			$this->get_default_args( [ 'post_id' => $lesson_id ] )
		);

		/* Assert. */
		$this->assertSame( 1, $result['total_count'], 'Expected exactly one result.' );
		$this->assertCount( 1, $result['items'], 'Expected exactly one item.' );
		$this->assertInstanceOf( Grading_Item::class, $result['items'][0], 'Expected a Grading_Item instance.' );
		$this->assertSame( 'in-progress', $result['items'][0]->status, 'Expected in-progress status.' );
		$this->assertSame( $user_id, $result['items'][0]->user_id, 'Expected matching user ID.' );
		$this->assertSame( $lesson_id, $result['items'][0]->lesson_id, 'Expected matching lesson ID.' );
		$this->assertNull( $result['items'][0]->grade, 'Expected null grade for non-graded item.' );
	}

	public function testGetLessonProgressItems_WithQuizStatus_UsesCoalescedStatus(): void {
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
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'complete', $course_id );
		$this->insert_progress( $quiz_id, $user_id, 'quiz', 'passed', $lesson_id );
		$this->insert_quiz_submission( $quiz_id, $user_id, 90 );

		$service = new Tables_Based_Grading_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_progress_items(
			$this->get_default_args( [ 'post_id' => $lesson_id ] )
		);

		/* Assert. */
		$this->assertSame( 'passed', $result['items'][0]->status, 'Quiz status should override lesson status.' );
		$this->assertSame( 90.0, $result['items'][0]->grade, 'Expected grade from quiz submission.' );
	}

	public function testGetLessonProgressItems_WithPostInFilter_ReturnsMatchingLessons(): void {
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
		$lesson3   = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$this->insert_progress( $lesson1, $user_id, 'lesson', 'in-progress', $course_id );
		$this->insert_progress( $lesson2, $user_id, 'lesson', 'complete', $course_id );
		$this->insert_progress( $lesson3, $user_id, 'lesson', 'in-progress', $course_id );

		$service = new Tables_Based_Grading_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_progress_items(
			$this->get_default_args( [ 'post__in' => [ $lesson1, $lesson2 ] ] )
		);

		/* Assert. */
		$this->assertSame( 2, $result['total_count'], 'Expected two items for the two filtered lessons.' );
		$returned_lesson_ids = array_map(
			function ( $item ) {
				return $item->lesson_id;
			},
			$result['items']
		);
		$this->assertContains( $lesson1, $returned_lesson_ids, 'Expected lesson1 in results.' );
		$this->assertContains( $lesson2, $returned_lesson_ids, 'Expected lesson2 in results.' );
		$this->assertNotContains( $lesson3, $returned_lesson_ids, 'Expected lesson3 excluded from results.' );
	}

	public function testGetLessonProgressItems_WithOffsetBeyondTotal_CorrectsPagination(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress', $course_id );

		$service = new Tables_Based_Grading_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_progress_items(
			$this->get_default_args(
				[
					'offset'  => 100,
					'post_id' => $lesson_id,
				]
			)
		);

		/* Assert. */
		$this->assertSame( 1, $result['total_count'], 'Expected one total item.' );
		$this->assertCount( 1, $result['items'], 'Expected offset correction to return items.' );
	}

	public function testGetLessonProgressItems_WithMultipleStatusArray_FiltersCorrectly(): void {
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

		$this->insert_progress( $lesson_id, $user1, 'lesson', 'in-progress', $course_id );
		// User2 completed and submitted the quiz so is not excluded.
		$this->insert_progress( $lesson_id, $user2, 'lesson', 'complete', $course_id );
		$this->insert_progress( $quiz_id, $user2, 'quiz', 'complete', $lesson_id );
		$this->insert_quiz_submission( $quiz_id, $user2 );

		$this->insert_progress( $lesson_id, $user3, 'lesson', 'complete', $course_id );
		$this->insert_progress( $quiz_id, $user3, 'quiz', 'graded', $lesson_id );
		$this->insert_quiz_submission( $quiz_id, $user3 );

		$service = new Tables_Based_Grading_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_progress_items(
			$this->get_default_args(
				[
					'status'  => [ 'in-progress', 'complete' ],
					'post_id' => $lesson_id,
				]
			)
		);

		/* Assert. */
		$this->assertSame( 2, $result['total_count'], 'Expected two items matching in-progress or complete.' );
		$statuses = array_map(
			function ( $item ) {
				return $item->status;
			},
			$result['items']
		);
		$this->assertNotContains( 'graded', $statuses, 'Graded status should be excluded.' );
	}

	public function testGetLessonProgressItems_WithStatusFilter_FiltersResults(): void {
		/* Arrange. */
		global $wpdb;
		$user1     = $this->sensei_factory->user->create();
		$user2     = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$this->insert_progress( $lesson_id, $user1, 'lesson', 'in-progress', $course_id );
		$this->insert_progress( $lesson_id, $user2, 'lesson', 'complete', $course_id );

		$service = new Tables_Based_Grading_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_progress_items(
			$this->get_default_args(
				[
					'status'  => 'in-progress',
					'post_id' => $lesson_id,
				]
			)
		);

		/* Assert. */
		$this->assertSame( 1, $result['total_count'] );
		$this->assertSame( 'in-progress', $result['items'][0]->status );
	}

	public function testGetLessonProgressItems_WithPagination_RespectsLimitAndOffset(): void {
		/* Arrange. */
		global $wpdb;
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		for ( $i = 0; $i < 3; $i++ ) {
			$user_id = $this->sensei_factory->user->create();
			$this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress', $course_id );
		}

		$service = new Tables_Based_Grading_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_progress_items(
			$this->get_default_args(
				[
					'number'  => 2,
					'post_id' => $lesson_id,
				]
			)
		);

		/* Assert. */
		$this->assertSame( 3, $result['total_count'] );
		$this->assertCount( 2, $result['items'] );
	}

	public function testGetLessonProgressItems_WithUserIdFilter_RestrictsToUser(): void {
		/* Arrange. */
		global $wpdb;
		$user1     = $this->sensei_factory->user->create();
		$user2     = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$this->insert_progress( $lesson_id, $user1, 'lesson', 'in-progress', $course_id );
		$this->insert_progress( $lesson_id, $user2, 'lesson', 'complete', $course_id );

		$service = new Tables_Based_Grading_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_progress_items(
			$this->get_default_args( [ 'user_id' => $user1 ] )
		);

		/* Assert. */
		$this->assertSame( 1, $result['total_count'] );
		$this->assertSame( $user1, $result['items'][0]->user_id );
	}

	public function testGetStatusCounts_AfterGetLessonProgressItems_ReturnsPerStatusCounts(): void {
		/* Arrange. */
		global $wpdb;
		$user1     = $this->sensei_factory->user->create();
		$user2     = $this->sensei_factory->user->create();
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

		// User1: in-progress lesson (no quiz interaction).
		$this->insert_progress( $lesson_id, $user1, 'lesson', 'in-progress', $course_id );

		// User2: completed lesson with passed quiz.
		$this->insert_progress( $lesson_id, $user2, 'lesson', 'complete', $course_id );
		$this->insert_progress( $quiz_id, $user2, 'quiz', 'passed', $lesson_id );
		$this->insert_quiz_submission( $quiz_id, $user2, 85 );

		$service = new Tables_Based_Grading_Listing_Service( $wpdb );

		/* Act. */
		$service->get_lesson_progress_items(
			$this->get_default_args( [ 'post_id' => $lesson_id ] )
		);
		$counts = $service->get_status_counts();

		/* Assert. */
		$this->assertIsArray( $counts, 'Expected an array of status counts.' );
		$this->assertSame( 1, $counts['in-progress'] ?? 0, 'Expected 1 in-progress.' );
		$this->assertSame( 1, $counts['passed'] ?? 0, 'Expected 1 passed (coalesced from quiz).' );
	}

	public function testGetStatusCounts_BeforeGetLessonProgressItems_ReturnsNull(): void {
		/* Arrange. */
		global $wpdb;
		$service = new Tables_Based_Grading_Listing_Service( $wpdb );

		/* Act & Assert. */
		$this->assertNull( $service->get_status_counts(), 'Expected null before any query.' );
	}

	public function testGetLessonProgressItems_WithExcludeUserLoginPrefixes_ExcludesMatchingUsers(): void {
		/* Arrange. */
		global $wpdb;
		$user1     = $this->sensei_factory->user->create( [ 'user_login' => 'real_student' ] );
		$user2     = $this->sensei_factory->user->create( [ 'user_login' => 'preview_guest_123' ] );
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$this->insert_progress( $lesson_id, $user1, 'lesson', 'in-progress', $course_id );
		$this->insert_progress( $lesson_id, $user2, 'lesson', 'in-progress', $course_id );

		$service = new Tables_Based_Grading_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_progress_items(
			$this->get_default_args(
				[
					'post_id'                     => $lesson_id,
					'exclude_user_login_prefixes' => [ 'preview_' ],
				]
			)
		);

		/* Assert. */
		$this->assertSame( 1, $result['total_count'], 'Expected excluded user to be filtered out.' );
		$this->assertSame( $user1, $result['items'][0]->user_id, 'Expected only the real student.' );
	}

	public function testGetLessonProgressItems_WithIncludeStatusesOverride_KeepsExcludedUserWithMatchingStatus(): void {
		/* Arrange. */
		global $wpdb;
		$user1     = $this->sensei_factory->user->create( [ 'user_login' => 'real_student' ] );
		$user2     = $this->sensei_factory->user->create( [ 'user_login' => 'preview_guest_456' ] );
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

		// Real student: in-progress.
		$this->insert_progress( $lesson_id, $user1, 'lesson', 'in-progress', $course_id );

		// Preview user: completed with ungraded quiz — should be kept because of override.
		$this->insert_progress( $lesson_id, $user2, 'lesson', 'complete', $course_id );
		$this->insert_progress( $quiz_id, $user2, 'quiz', 'ungraded', $lesson_id );
		$this->insert_quiz_submission( $quiz_id, $user2 );

		$service = new Tables_Based_Grading_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_progress_items(
			$this->get_default_args(
				[
					'post_id'                     => $lesson_id,
					'exclude_user_login_prefixes' => [ 'preview_' ],
					'include_statuses_override'   => [ 'ungraded' ],
				]
			)
		);

		/* Assert. */
		$this->assertSame( 2, $result['total_count'], 'Expected both users — preview user kept by status override.' );
		$returned_user_ids = array_map(
			function ( $item ) {
				return $item->user_id;
			},
			$result['items']
		);
		$this->assertContains( $user1, $returned_user_ids, 'Expected real student in results.' );
		$this->assertContains( $user2, $returned_user_ids, 'Expected preview user kept by ungraded override.' );
	}

	public function testGetStatusCounts_WithExcludeUserLoginPrefixes_ExcludesFromCounts(): void {
		/* Arrange. */
		global $wpdb;
		$user1     = $this->sensei_factory->user->create( [ 'user_login' => 'real_student2' ] );
		$user2     = $this->sensei_factory->user->create( [ 'user_login' => 'preview_guest_789' ] );
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$this->insert_progress( $lesson_id, $user1, 'lesson', 'in-progress', $course_id );
		$this->insert_progress( $lesson_id, $user2, 'lesson', 'in-progress', $course_id );

		$service = new Tables_Based_Grading_Listing_Service( $wpdb );

		/* Act. */
		$service->get_lesson_progress_items(
			$this->get_default_args(
				[
					'post_id'                     => $lesson_id,
					'exclude_user_login_prefixes' => [ 'preview_' ],
				]
			)
		);
		$counts = $service->get_status_counts();

		/* Assert. */
		$this->assertSame( 1, $counts['in-progress'] ?? 0, 'Expected cached counts to also exclude the preview user.' );
	}

	public function testGetLessonProgressItems_WithTrashedLesson_ExcludesFromResults(): void {
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

		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress', $course_id );
		$this->insert_progress( $published_id, $user_id, 'lesson', 'in-progress', $course_id );

		wp_trash_post( $lesson_id );

		$service = new Tables_Based_Grading_Listing_Service( $wpdb );

		/* Act. */
		$result = $service->get_lesson_progress_items( $this->get_default_args() );
		$counts = $service->get_status_counts();

		/* Assert. */
		$this->assertSame( 1, $result['total_count'], 'Trashed lesson progress should be excluded from total count.' );
		$returned_lesson_ids = array_map(
			function ( $item ) {
				return $item->lesson_id;
			},
			$result['items']
		);
		$this->assertNotContains( $lesson_id, $returned_lesson_ids, 'Trashed lesson should not appear in items.' );
		$this->assertContains( $published_id, $returned_lesson_ids, 'Published lesson should appear in items.' );
		$this->assertSame( 1, $counts['in-progress'] ?? 0, 'Status counts should also exclude trashed lesson.' );
	}

	public function testGetStatusCounts_WithStatusFilter_ReturnsAllStatuses(): void {
		/* Arrange. */
		global $wpdb;
		$user1     = $this->sensei_factory->user->create();
		$user2     = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$this->insert_progress( $lesson_id, $user1, 'lesson', 'in-progress', $course_id );
		$this->insert_progress( $lesson_id, $user2, 'lesson', 'complete', $course_id );

		$service = new Tables_Based_Grading_Listing_Service( $wpdb );

		/* Act -- query with status filter for in-progress only. */
		$service->get_lesson_progress_items(
			$this->get_default_args(
				[
					'status'  => 'in-progress',
					'post_id' => $lesson_id,
				]
			)
		);
		$counts = $service->get_status_counts();

		/* Assert -- counts should include ALL statuses, not just in-progress. */
		$this->assertSame( 1, $counts['in-progress'] ?? 0, 'Expected 1 in-progress.' );
		$this->assertSame( 1, $counts['complete'] ?? 0, 'Expected 1 complete even though status filter was in-progress.' );
	}

	public function testGetLessonProgressItems_CalledTwice_UsesCachedStatusCounts(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress', $course_id );

		$service = new Tables_Based_Grading_Listing_Service( $wpdb );
		$service->get_lesson_progress_items( $this->get_default_args( array( 'post_id' => $lesson_id ) ) );

		// Mutate the underlying data without going through Sensei write paths,
		// so the cache is not invalidated. The cached counts should still be returned.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Test helper.
		$wpdb->query( "UPDATE {$wpdb->prefix}sensei_lms_progress SET status='complete' WHERE type='lesson'" );

		/* Act. */
		$service->get_lesson_progress_items( $this->get_default_args( array( 'post_id' => $lesson_id ) ) );
		$counts = $service->get_status_counts();

		/* Assert. */
		$this->assertSame( 1, $counts['in-progress'] ?? 0, 'Cached status counts should be returned on the second call.' );
		$this->assertArrayNotHasKey( 'complete', $counts, 'Updated status should not appear because the cache is not yet invalidated.' );
	}

	public function testGetLessonProgressItems_AfterCacheInvalidation_RefreshesStatusCounts(): void {
		/* Arrange. */
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'in-progress', $course_id );

		$service = new Tables_Based_Grading_Listing_Service( $wpdb );
		$service->get_lesson_progress_items( $this->get_default_args( array( 'post_id' => $lesson_id ) ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Test helper.
		$wpdb->query( "UPDATE {$wpdb->prefix}sensei_lms_progress SET status='complete' WHERE type='lesson'" );

		( new \Sensei\Internal\Services\Grading_Listing_Cache_Invalidator() )->bump_version();

		/* Act. */
		$service->get_lesson_progress_items( $this->get_default_args( array( 'post_id' => $lesson_id ) ) );
		$counts = $service->get_status_counts();

		/* Assert. */
		$this->assertSame( 1, $counts['complete'] ?? 0, 'After invalidation the refreshed status counts should be returned.' );
	}
}

<?php

use Sensei\Internal\Services\Progress_Aggregation_Service_Interface;

/**
 * Shared behavioral coverage for the Courses Overview aggregation APIs.
 *
 * @covers \Sensei\Internal\Services\Comments_Based_Progress_Aggregation_Service
 * @covers \Sensei\Internal\Services\Tables_Based_Progress_Aggregation_Service
 */
abstract class Progress_Aggregation_Service_Test extends \WP_UnitTestCase {
	/**
	 * Factory shared by the behavioral and backend-specific tests.
	 *
	 * @var \Sensei_Factory
	 */
	protected $sensei_factory;

	public function setUp(): void {
		parent::setUp();
		$this->sensei_factory = new \Sensei_Factory();
	}

	public function tearDown(): void {
		$this->sensei_factory->tearDown();
		parent::tearDown();
	}

	public function testCountStatusesByPost_ExcludedUserLoginPrefixesGiven_CountsOnlyOtherUsers(): void {
		/* Arrange. */
		$course           = $this->sensei_factory->course->create();
		$completed_course = $this->sensei_factory->course->create();
		foreach ( array( 'registered_student', 'sensei_guest_student', 'sensei_preview_student', 'senseiXguest_student' ) as $login ) {
			$user_id = $this->sensei_factory->user->create( array( 'user_login' => $login ) );
			$this->seed_progress( $course, $user_id, 'course', 'in-progress' );
			$this->seed_progress( $completed_course, $user_id, 'course', 'complete' );
		}
		$service = $this->get_service();

		/* Act. */
		$actual = $service->count_statuses_by_post(
			array( $course, $completed_course ),
			array( 'exclude_user_login_prefixes' => array( 'sensei_guest_', 'sensei_preview_' ) )
		);

		/* Assert. */
		self::assertSame(
			array(
				$course           => array( 'in-progress' => 2 ),
				$completed_course => array( 'complete' => 2 ),
			),
			$this->sort_counts( $actual )
		);
	}

	public function testCountStatusesByPost_ProgressIdMappingGiven_ReturnsCountsUnderRequestedIds(): void {
		/* Arrange. */
		$original    = $this->sensei_factory->course->create();
		$translation = $this->sensei_factory->course->create();
		$user_id     = $this->sensei_factory->user->create();
		$this->seed_progress( $original, $user_id, 'course', 'complete' );
		$this->add_progress_id_filter( array( $translation => $original ) );
		$service = $this->get_service();

		/* Act. */
		$actual = $service->count_statuses_by_post( array( $original, $translation ) );

		/* Assert. */
		self::assertSame(
			array(
				$original    => array( 'complete' => 1 ),
				$translation => array( 'complete' => 1 ),
			),
			$this->sort_counts( $actual )
		);
	}

	public function testCountStatusesByPost_PostRestrictionsGiven_ReturnsOnlyRequestedPosts(): void {
		/* Arrange. */
		$course       = $this->sensei_factory->course->create();
		$other_course = $this->sensei_factory->course->create();
		$user_id      = $this->sensei_factory->user->create();
		$other_user   = $this->sensei_factory->user->create();
		$this->seed_progress( $course, $user_id, 'course', 'complete' );
		$this->seed_progress( $course, $other_user, 'course', 'in-progress' );
		$this->seed_progress( $other_course, $user_id, 'course', 'complete' );
		$service = $this->get_service();

		/* Act. */
		$actual = $service->count_statuses_by_post( array( $course ) );

		/* Assert. */
		self::assertSame(
			array(
				$course => array(
					'complete'    => 1,
					'in-progress' => 1,
				),
			),
			$this->sort_counts( $actual )
		);
	}

	public function testCountStatusesByPost_MultipleCoursesGiven_ReturnsCountsGroupedByPost(): void {
		/* Arrange. */
		$user1                   = $this->sensei_factory->user->create();
		$user2                   = $this->sensei_factory->user->create();
		$user3                   = $this->sensei_factory->user->create();
		$course_id1              = $this->sensei_factory->course->create();
		$course_id2              = $this->sensei_factory->course->create();
		$course_without_progress = $this->sensei_factory->course->create();
		$this->seed_progress( $course_id1, $user1, 'course', 'complete' );
		$this->seed_progress( $course_id1, $user2, 'course', 'in-progress' );
		$this->seed_progress( $course_id1, $user3, 'course', 'in-progress' );
		$this->seed_progress( $course_id2, $user1, 'course', 'in-progress' );
		$this->seed_progress( $course_id2, $user2, 'course', 'in-progress' );
		$this->seed_progress( $course_id2, $user3, 'course', 'in-progress' );
		$service = $this->get_service();

		/* Act. */
		$actual = $service->count_statuses_by_post( array( $course_id1, $course_id2, $course_without_progress ) );

		/* Assert. */
		self::assertSame(
			array(
				$course_id1 => array(
					'complete'    => 1,
					'in-progress' => 2,
				),
				$course_id2 => array( 'in-progress' => 3 ),
			),
			$this->sort_counts( $actual )
		);
	}

	public function testGetLessonCompletionCounts_TemporaryUsersGiven_CountsOnlyRegisteredUsers(): void {
		/* Arrange. */
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		foreach ( array( 'registered_student', 'sensei_guest_student', 'sensei_preview_student' ) as $login ) {
			$user_id = $this->sensei_factory->user->create( array( 'user_login' => $login ) );
			$this->seed_progress( $lesson_id, $user_id, 'lesson', 'complete' );
		}
		$service = $this->get_service();

		/* Act. */
		$actual = $service->get_lesson_completion_counts(
			array( $lesson_id ),
			array( 'exclude_user_login_prefixes' => array( 'sensei_guest_', 'sensei_preview_' ) )
		);

		/* Assert. */
		self::assertSame( array( $lesson_id => 1 ), $actual );
	}

	public function testGetLessonCompletionCounts_ProgressIdMappingGiven_ReturnsCountUnderRequestedId(): void {
		/* Arrange. */
		$course_id  = $this->sensei_factory->course->create();
		$original   = $this->sensei_factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		$translated = $this->sensei_factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		$user_id    = $this->sensei_factory->user->create();
		$this->seed_progress( $original, $user_id, 'lesson', 'complete' );
		$this->add_lesson_progress_id_filter( array( $translated => $original ) );
		$service = $this->get_service();

		/* Act. */
		$actual = $service->get_lesson_completion_counts( array( $translated ) );

		/* Assert. */
		self::assertSame( array( $translated => 1 ), $actual );
	}

	public function testGetLessonCompletionCounts_TrashedParentCourseGiven_ReturnsNoCounts(): void {
		/* Arrange. */
		$course_id = $this->sensei_factory->course->create( array( 'post_status' => 'trash' ) );
		$lesson_id = $this->sensei_factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		$user_id   = $this->sensei_factory->user->create();
		$this->seed_progress( $lesson_id, $user_id, 'lesson', 'complete' );
		$service = $this->get_service();

		/* Act. */
		$actual = $service->get_lesson_completion_counts( array( $lesson_id ) );

		/* Assert. */
		self::assertSame( array(), $actual );
	}

	public function testGetLessonCompletionCounts_CompletedAndStartedLessonsGiven_ReturnsOnlyCompletionCounts(): void {
		/* Arrange. */
		$user_id          = $this->sensei_factory->user->create();
		$second_user_id   = $this->sensei_factory->user->create();
		$course_id        = $this->sensei_factory->course->create();
		$ungraded_lesson  = $this->sensei_factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		$completed_lesson = $this->sensei_factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		$started_lesson   = $this->sensei_factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		$quiz_id          = $this->sensei_factory->quiz->create();
		update_post_meta( $ungraded_lesson, '_lesson_quiz', $quiz_id );
		$this->seed_ungraded_quiz( $ungraded_lesson, $quiz_id, $user_id );
		$this->seed_progress( $completed_lesson, $user_id, 'lesson', 'complete' );
		$this->seed_progress( $completed_lesson, $second_user_id, 'lesson', 'complete' );
		$this->seed_progress( $started_lesson, $user_id, 'lesson', 'in-progress' );
		$service = $this->get_service();

		/* Act. */
		$actual = $service->get_lesson_completion_counts( array( $ungraded_lesson, $completed_lesson, $started_lesson ) );

		/* Assert. */
		self::assertSame(
			array(
				$ungraded_lesson  => 1,
				$completed_lesson => 2,
			),
			$actual
		);
	}

	public function testGetLessonCompletionCounts_EmptyLessonIdsGiven_ReturnsEmptyArray(): void {
		/* Arrange. */
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		$user_id   = $this->sensei_factory->user->create();
		$this->seed_progress( $lesson_id, $user_id, 'lesson', 'complete' );
		$service = $this->get_service();

		/* Act. */
		$actual = $service->get_lesson_completion_counts( array() );

		/* Assert. */
		self::assertSame( array(), $actual );
	}

	/**
	 * Create the backend under test.
	 *
	 * @return Progress_Aggregation_Service_Interface
	 */
	abstract protected function get_service(): Progress_Aggregation_Service_Interface;

	/**
	 * Store progress with local start and completion dates in the selected backend.
	 */
	abstract protected function seed_progress( int $post_id, int $user_id, string $type, string $status, ?string $started_at = '2022-01-01 00:00:00', ?string $completed_at = '2022-01-02 00:00:00' ): void;

	/**
	 * Store a submitted quiz awaiting grading using the selected backend's lesson semantics.
	 */
	abstract protected function seed_ungraded_quiz( int $lesson_id, int $quiz_id, int $user_id ): void;

	private function add_progress_id_filter( array $map ): void {
		add_filter(
			'sensei_course_progress_get_course_id',
			static function ( $post_id ) use ( $map ) {
				return $map[ $post_id ] ?? $post_id;
			}
		);
	}

	private function add_lesson_progress_id_filter( array $map ): void {
		add_filter(
			'sensei_lesson_progress_get_lesson_id',
			static function ( $post_id ) use ( $map ) {
				return $map[ $post_id ] ?? $post_id;
			}
		);
	}

	/**
	 * Keep count comparisons strict without depending on database row order.
	 *
	 * @param array $counts Counts grouped by post and status.
	 * @return array Counts sorted by post ID and status.
	 */
	private function sort_counts( array $counts ): array {
		foreach ( $counts as &$statuses ) {
			ksort( $statuses );
		}
		unset( $statuses );
		ksort( $counts );

		return $counts;
	}
}

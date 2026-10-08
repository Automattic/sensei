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

	/**
	 * Original timezone options restored after date-sensitive tests.
	 *
	 * @var array
	 */
	private $timezone_options;

	public function setUp(): void {
		parent::setUp();
		$this->sensei_factory   = new \Sensei_Factory();
		$this->timezone_options = array(
			'timezone_string' => get_option( 'timezone_string' ),
			'gmt_offset'      => get_option( 'gmt_offset' ),
		);
	}

	public function tearDown(): void {
		foreach ( $this->timezone_options as $option => $value ) {
			update_option( $option, $value );
		}
		$this->sensei_factory->tearDown();
		parent::tearDown();
	}

	/**
	 * Temporary completions distinguish report counts from generic progress counts.
	 *
	 * @dataProvider report_lesson_population_provider
	 */
	public function testGetLessonStudentCount_TemporaryActivityCreated_CountsRegisteredStudents( array $registered_statuses, int $students, int $completed ): void {
		$lesson = $this->sensei_factory->lesson->create();
		foreach ( array_merge(
			$registered_statuses,
			array(
				'sensei_guest_student'   => 'complete',
				'sensei_preview_student' => 'complete',
			)
		) as $login => $status ) {
			$user = $this->sensei_factory->user->create( array( 'user_login' => $login ) );
			$this->seed_progress( $lesson, $user, 'lesson', $status );
		}

		$actual = $this->get_service()->get_lesson_student_count(
			array(
				'post_id' => $lesson,
				'status'  => 'any',
			)
		);

		$this->assertSame( $students, $actual );
	}

	public function testGetLessonStudentCount_TranslatedLessonQueried_CountsOriginalLessonStudents(): void {
		$original_lesson_id   = $this->sensei_factory->lesson->create();
		$translated_lesson_id = $this->sensei_factory->lesson->create();
		$user_id              = $this->sensei_factory->user->create();
		$this->seed_progress( $original_lesson_id, $user_id, 'lesson', 'complete' );
		$this->add_lesson_progress_id_filter( array( $translated_lesson_id => $original_lesson_id ) );

		$actual = $this->get_service()->get_lesson_student_count( array( 'post_id' => $translated_lesson_id ) );

		$this->assertSame( 1, $actual );
	}

	/**
	 * Temporary completions must not mask a registered student's unfinished lesson.
	 *
	 * @dataProvider report_lesson_population_provider
	 */
	public function testGetLessonCompletionCount_TemporaryActivityCreated_CountsRegisteredCompletions( array $registered_statuses, int $students, int $completed ): void {
		$lesson = $this->sensei_factory->lesson->create();
		foreach ( array_merge(
			$registered_statuses,
			array(
				'sensei_guest_student'   => 'complete',
				'sensei_preview_student' => 'complete',
			)
		) as $login => $status ) {
			$user = $this->sensei_factory->user->create( array( 'user_login' => $login ) );
			$this->seed_progress( $lesson, $user, 'lesson', $status );
		}

		$actual = $this->get_service()->get_lesson_completion_count(
			array(
				'post_id' => $lesson,
				'type'    => 'sensei_lesson_status',
				'status'  => \Sensei\Internal\Services\Reports_Item::COMPLETED_STATUSES,
			)
		);

		$this->assertSame( $completed, $actual );
	}

	public function report_lesson_population_provider(): array {
		return array(
			'registered completion'  => array( array( 'registered' => 'complete' ), 1, 1 ),
			'registered in progress' => array( array( 'registered' => 'in-progress' ), 1, 0 ),
			'temporary only'         => array( array(), 0, 0 ),
		);
	}

	public function testCountStatuses_TemporaryProgressCreated_KeepsExclusionConfigurable(): void {
		$lesson = $this->sensei_factory->lesson->create();
		foreach ( array( 'registered', 'sensei_guest_student', 'sensei_preview_student' ) as $login ) {
			$user = $this->sensei_factory->user->create( array( 'user_login' => $login ) );
			$this->seed_progress( $lesson, $user, 'lesson', 'complete' );
		}
		$service = $this->get_service();
		$args    = array(
			'type'    => 'lesson',
			'post_id' => $lesson,
		);

		$all      = $service->count_statuses( $args );
		$eligible = $service->count_statuses( array_merge( $args, array( 'exclude_user_login_prefixes' => \Sensei\Internal\Services\Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES ) ) );

		$this->assertSame( 3, $all['complete'], 'Generic counts include temporary progress by default.' );
		$this->assertSame( 1, $eligible['complete'], 'Callers can still explicitly exclude temporary progress.' );
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

	public function testGetAverageDaysToCompletionByCourse_MissingStartDateGiven_ExcludesItFromDenominator(): void {
		/* Arrange. */
		$course     = $this->sensei_factory->course->create();
		$user_id    = $this->sensei_factory->user->create();
		$other_user = $this->sensei_factory->user->create();
		$this->seed_progress( $course, $user_id, 'course', 'complete', '2022-01-01 00:00:00', '2022-01-04 00:00:00' );
		$this->seed_progress( $course, $other_user, 'course', 'complete', null, '2022-01-04 00:00:00' );
		$service = $this->get_service();

		/* Act. */
		$actual = $service->get_average_days_to_completion_by_course( array( $course ) );

		/* Assert. */
		self::assertSame( array( $course => 4.0 ), $actual );
	}

	/**
	 * Completion days use each event's local calendar date.
	 *
	 * @dataProvider completion_days_timezone_cases
	 */
	public function testGetAverageDaysToCompletionByCourse_LocalDatesGiven_UsesLocalCalendarDays( string $timezone, int $offset, string $start, string $end, float $expected ): void {
		/* Arrange. */
		update_option( 'timezone_string', $timezone );
		update_option( 'gmt_offset', $offset );
		$course  = $this->sensei_factory->course->create();
		$user_id = $this->sensei_factory->user->create();
		$this->seed_progress( $course, $user_id, 'course', 'complete', $start, $end );

		/* Act. */
		$actual = $this->get_service()->get_average_days_to_completion_by_course( array( $course ) );

		/* Assert. */
		self::assertSame( array( $course => $expected ), $actual );
	}

	public function testGetAverageDaysToCompletionByCourse_MultipleCoursesGiven_ReturnsRoundedCourseAverages(): void {
		/* Arrange. */
		$user1      = $this->sensei_factory->user->create();
		$user2      = $this->sensei_factory->user->create();
		$user3      = $this->sensei_factory->user->create();
		$user4      = $this->sensei_factory->user->create();
		$course_id1 = $this->sensei_factory->course->create();
		$course_id2 = $this->sensei_factory->course->create();
		$this->seed_progress( $course_id1, $user1, 'course', 'complete', '2022-03-11 23:27:51', '2022-03-11 23:29:06' );
		$this->seed_progress( $course_id1, $user2, 'course', 'complete', '2022-03-11 23:27:51', '2022-03-11 23:29:06' );
		$this->seed_progress( $course_id1, $user3, 'course', 'complete', '2022-03-11 23:27:51', '2022-03-11 23:29:06' );
		$this->seed_progress( $course_id1, $user4, 'course', 'complete', '2022-03-09 00:22:34', '2022-03-12 00:22:37' );
		$this->seed_progress( $course_id2, $user1, 'course', 'complete', '2022-03-09 00:22:34', '2022-03-12 00:22:37' );
		$service = $this->get_service();

		/* Act. */
		$actual = $service->get_average_days_to_completion_by_course( array( $course_id1, $course_id2 ) );

		/* Assert. */
		// Course 1: ceil((1 + 1 + 1 + 4) / 4) = 2. Course 2: 4.
		self::assertSame(
			array(
				$course_id1 => 2.0,
				$course_id2 => 4.0,
			),
			$actual
		);
	}

	public function testGetAverageDaysToCompletionByCourse_EmptyCourseIdsGiven_ReturnsEmptyArray(): void {
		/* Arrange. */
		$course_id = $this->sensei_factory->course->create();
		$user_id   = $this->sensei_factory->user->create();
		$this->seed_progress( $course_id, $user_id, 'course', 'complete', '2022-01-01 00:00:00', '2022-01-04 00:00:00' );
		$service = $this->get_service();

		/* Act. */
		$actual = $service->get_average_days_to_completion_by_course( array() );

		/* Assert. */
		self::assertSame( array(), $actual );
	}

	public function testGetAverageDaysToCompletionByCourse_TemporaryUsersGiven_RetainsRegisteredCompletionDays(): void {
		/* Arrange. */
		$course = $this->sensei_factory->course->create();
		foreach ( array(
			'registered_student'     => 4,
			'sensei_guest_student'   => 20,
			'sensei_preview_student' => 30,
		) as $login => $days ) {
			$user_id = $this->sensei_factory->user->create( array( 'user_login' => $login ) );
			$this->seed_progress( $course, $user_id, 'course', 'complete', '2022-01-01 00:00:00', sprintf( '2022-01-%02d 00:00:00', $days ) );
		}

		/* Act. */
		$actual = $this->get_service()->get_average_days_to_completion_by_course(
			array( $course ),
			array( 'exclude_user_login_prefixes' => \Sensei\Internal\Services\Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES )
		);

		/* Assert. */
		// Only the registered student's four inclusive calendar days contribute.
		self::assertSame( array( $course => 4.0 ), $actual );
	}

	public function testGetAverageDaysToCompletionByCourse_MixedPostStatusesGiven_IncludesPublishedAndPrivateCourses(): void {
		/* Arrange. */
		$courses = array();
		$user    = $this->sensei_factory->user->create();
		foreach ( array(
			'publish' => 2,
			'private' => 4,
			'draft'   => 20,
			'trash'   => 20,
			'future'  => 20,
		) as $status => $days ) {
			$course    = $this->sensei_factory->course->create(
				array(
					'post_status' => $status,
					'post_date'   => 'future' === $status ? '2036-01-01 00:00:00' : '2022-01-01 00:00:00',
				)
			);
			$courses[] = $course;
			$this->seed_progress( $course, $user, 'course', 'complete', '2022-01-01 00:00:00', sprintf( '2022-01-%02d 00:00:00', $days ) );
		}

		/* Act. */
		$actual = $this->get_service()->get_average_days_to_completion_by_course( $courses );

		/* Assert. */
		// Only the published and private courses contribute their two- and four-day averages.
		self::assertSame(
			array(
				$courses[0] => 2.0,
				$courses[1] => 4.0,
			),
			$actual
		);
	}

	public function testGetAverageDaysToCompletionByCourse_StartedCourseAndCourseWithoutProgressGiven_ReturnsEmptyArray(): void {
		/* Arrange. */
		$started_course = $this->sensei_factory->course->create();
		$empty_course   = $this->sensei_factory->course->create();
		$user           = $this->sensei_factory->user->create();
		$this->seed_progress( $started_course, $user, 'course', 'in-progress' );

		/* Act. */
		$actual = $this->get_service()->get_average_days_to_completion_by_course( array( $started_course, $empty_course ) );

		/* Assert. */
		// Neither course has a qualifying completion, so neither appears in the result.
		self::assertSame( array(), $actual );
	}

	public function testGetAverageDaysToCompletionByCourse_TranslatedCourseGiven_ReturnsRequestedKeys(): void {
		/* Arrange. */
		$original   = $this->sensei_factory->course->create();
		$translated = $this->sensei_factory->course->create();
		$user       = $this->sensei_factory->user->create();
		$this->seed_progress( $original, $user, 'course', 'complete', '2022-01-01 00:00:00', '2022-01-04 00:00:00' );
		$this->add_progress_id_filter( array( $translated => $original ) );

		/* Act. */
		$actual = $this->get_service()->get_average_days_to_completion_by_course( array( $original, $translated ) );

		/* Assert. */
		// Both requested IDs share the same four-day original progress.
		self::assertSame(
			array(
				$original   => 4.0,
				$translated => 4.0,
			),
			$actual
		);
	}

	public static function completion_days_timezone_cases(): array {
		return array(
			// Winter events cross midnight using their historical offset, despite the summer setting.
			'historical offset' => array( 'America/New_York', -4, '2024-01-01 23:30:00', '2024-01-02 00:30:00', 2.0 ),
			// Two local calendar dates span a 23-hour daylight-saving interval.
			'daylight saving'   => array( 'America/New_York', -4, '2024-03-09 23:30:00', '2024-03-10 23:30:00', 2.0 ),
			// Both events stay on the same local date with a fixed UTC offset.
			'fixed offset'      => array( '', -5, '2024-01-01 00:00:00', '2024-01-01 23:00:00', 1.0 ),
		);
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

<?php

/**
 * Sensei Reports Overview Service Courses Test Class
 *
 * @covers Sensei_Reports_Overview_Service_Courses
 */
class Sensei_Reports_Overview_Service_Courses_Enrollments_Test extends WP_UnitTestCase {
	use Sensei_HPPS_Helpers;

	private static $initial_hook_suffix;

	/**
	 * Factory for setting up testing data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$initial_hook_suffix = $GLOBALS['hook_suffix'] ?? null;
		$GLOBALS['hook_suffix']    = null;
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		$GLOBALS['hook_suffix'] = self::$initial_hook_suffix;
	}

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
		$this->maybe_enable_hpps_tables_repository();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		$this->maybe_reset_hpps_repository();
		parent::tearDown();

		$this->factory->tearDown();
	}

	public function testGetTotalAverageProgress_WPMLTranslatedCourseGiven_UsesOriginalLessonsAndEnrollments(): void {
		/* Arrange. */
		$original_course   = $this->factory->course->create();
		$translated_course = $this->factory->course->create();
		$first_user        = $this->factory->user->create();
		$second_user       = $this->factory->user->create();
		$completed_lesson  = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $original_course ) ) );
		$unfinished_lesson = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $original_course ) ) );
		$this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $translated_course ) ) );
		Sensei_Utils::sensei_start_lesson( $completed_lesson, $first_user, true );
		Sensei_Utils::sensei_start_lesson( $unfinished_lesson, $first_user );
		Sensei_Utils::user_start_course( $second_user, $original_course );
		$this->add_translation_filters( array( $translated_course => $original_course ) );
		$service = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $service->get_total_average_progress( array( $translated_course ) );

		/* Assert. */
		// One completed lesson out of two lessons for each of two students: 25%.
		self::assertSame( 25.0, $actual );
	}

	public function testGetTotalEnrollments_EmptyCourseIdsGiven_ReturnsZero() {

		/* Arrange. */
		$course_id = $this->factory->course->create();
		$user_id   = $this->factory->user->create();
		$this->seed_course_completion_with_dates( $course_id, $user_id, '2022-01-01 00:00:00', '2022-01-04 00:00:00' );
		$instance = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $instance->get_total_enrollments( array() );

		/* Assert. */
		self::assertSame( 0, $actual );
	}

	public function testGetTotalEnrollments_SameStudentInDifferentCoursesGiven_ReturnsSumOfEnrollments() {

		/* Arrange. */
		$user1_id   = $this->factory->user->create();
		$course1_id = $this->factory->course->create();
		$course2_id = $this->factory->course->create();

		// Add 2 lessons to the course.
		$lesson_course_1 = $this->factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course1_id ) )
		);
		$lesson_course_2 = $this->factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course2_id ) )
		);

		// Enroll the same student in both courses and their lessons, but don't complete the lessons.
		Sensei_Utils::sensei_start_lesson( $lesson_course_1, $user1_id );
		Sensei_Utils::sensei_start_lesson( $lesson_course_2, $user1_id );

		$instance = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $instance->get_total_enrollments( array( $course1_id, $course2_id ) );

		/* Assert. */
		self::assertSame( 2, $actual );
	}

	public function testGetTotalEnrollments_WPMLTranslatedCourseGiven_ReturnsOriginalEnrollments() {
		/* Arrange. */
		$original_course   = $this->factory->course->create();
		$translated_course = $this->factory->course->create();
		$original_lesson   = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $original_course ) ) );
		$user_id           = $this->factory->user->create();
		Sensei_Utils::sensei_start_lesson( $original_lesson, $user_id, true );
		$this->seed_course_completion_with_dates( $original_course, $user_id, '2022-01-01 00:00:00', '2022-01-02 00:00:00' );
		$this->add_translation_filters( array( $translated_course => $original_course ) );
		$service = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $service->get_total_enrollments( array( $translated_course ) );

		/* Assert. */
		self::assertSame( 1, $actual );
	}

	/**
	 * Seed a completed course status with fixed start/completion dates, using
	 * whichever storage backend is active for the current test run so that the
	 * seeded fixture is readable by the aggregation service under test.
	 *
	 * @param int    $course_id    Course ID.
	 * @param int    $user_id      User ID.
	 * @param string $started_at   Start date/time string (site-local).
	 * @param string $completed_at Completion date/time string (site-local).
	 */
	private function seed_course_completion_with_dates( int $course_id, int $user_id, string $started_at, string $completed_at ): void {
		if ( self::is_hpps_tables_mode() ) {
			$timezone        = wp_timezone();
			$course_progress = Sensei()->course_progress_repository->get( $course_id, $user_id )
				?? Sensei()->course_progress_repository->create( $course_id, $user_id );
			$course_progress->start( new DateTimeImmutable( $started_at, $timezone ) );
			$course_progress->complete( new DateTimeImmutable( $completed_at, $timezone ) );
			Sensei()->course_progress_repository->save( $course_progress );
			return;
		}

		$comment_id = Sensei_Utils::update_course_status( $user_id, $course_id, 'complete' );
		wp_update_comment(
			array(
				'comment_ID'   => $comment_id,
				'comment_date' => $completed_at,
			)
		);
		update_comment_meta( $comment_id, 'start', $started_at );
	}

	/**
	 * Simulate WPML resolving translated posts to the original language.
	 *
	 * @param array $map Translated IDs mapped to original IDs.
	 */
	private function add_translation_filters( array $map ): void {
		add_filter(
			'wpml_element_language_details',
			static function () {
				return array(
					'source_language_code' => 'en',
					'language_code'        => 'es',
				);
			}
		);
		add_filter(
			'wpml_object_id',
			static function ( $post_id ) use ( $map ) {
				return $map[ $post_id ] ?? $post_id;
			}
		);
		add_filter( 'sensei_course_progress_get_course_id', array( new \Sensei\WPML\Course_Progress(), 'translate_course_id' ) );
		add_filter( 'sensei_lesson_progress_get_lesson_id', array( new \Sensei\WPML\Lesson_Progress(), 'translate_lesson_id' ) );
	}
}

<?php

/**
 * Sensei Reports Overview Service Courses Test Class
 *
 * @covers Sensei_Reports_Overview_Service_Courses
 */
class Sensei_Reports_Overview_Service_Courses_Lesson_Completions_Test extends WP_UnitTestCase {
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

	/**
	 * Tests getting total average progress value for the course based on the lessons completion for single course.
	 *
	 * @covers Sensei_Reports_Overview_Service_Courses::get_total_average_progress
	 */
	public function testGetTotalAverageProgress_SingleCourseWithPartialProgressGiven_ReturnsAverageProgress() {
		/* Arrange. */
		// Create a course
		$course_id = $this->factory->course->create();

		// Create 2 users
		$user_id_1 = $this->factory->user->create();
		$user_id_2 = $this->factory->user->create();

		//Add 2 lessons to the course
		$lesson_1 = $this->factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$lesson_2 = $this->factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);

		$service = new Sensei_Reports_Overview_Service_Courses();

		// Complete lesson 1 and lesson 2 with user_1.
		Sensei_Utils::sensei_start_lesson( $lesson_1, $user_id_1, true );
		Sensei_Utils::sensei_start_lesson( $lesson_2, $user_id_1, true );

		// Enroll student 2 to the course and lessons, but don't complete the lessons.
		Sensei_Utils::sensei_start_lesson( $lesson_1, $user_id_2 );
		Sensei_Utils::sensei_start_lesson( $lesson_2, $user_id_2 );

		/* Act. */
		$actual = $service->get_total_average_progress( array( $course_id ) );

		/* Assert. */
		$this->assertSame(
			50.0,
			$actual,
			'Find totals of lessons completed single course.'
		);
	}

	/**
	 * Tests getting total average progress value for the course based on the lessons completion for multiple courses.
	 *
	 * @covers Sensei_Reports_Overview_Service_Courses::get_total_average_progress
	 */
	public function testGetTotalAverageProgress_MultipleCoursesWithPartialProgressGiven_ReturnsAverageProgress() {
		/* Arrange. */
		// Create a course 1
		$course_id_1 = $this->factory->course->create();

		// Create a course 2
		$course_id_2 = $this->factory->course->create();

		// Create 2 users
		$user_id_1 = $this->factory->user->create();
		$user_id_2 = $this->factory->user->create();

		//Add 2 lessons to the course 1
		$lesson_1 = $this->factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id_1 ) )
		);
		$lesson_2 = $this->factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id_1 ) )
		);
		//Add 2 lessons to the course 2
		$lesson_3 = $this->factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id_2 ) )
		);
		$lesson_4 = $this->factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id_2 ) )
		);
		$service  = new Sensei_Reports_Overview_Service_Courses();
		// Complete lesson 1 and lesson 2 with user_1.
		Sensei_Utils::sensei_start_lesson( $lesson_1, $user_id_1, true );
		Sensei_Utils::sensei_start_lesson( $lesson_2, $user_id_1, true );

		// Enroll student 2 to the course and lessons, but don't complete the lessons.
		Sensei_Utils::sensei_start_lesson( $lesson_1, $user_id_2 );
		Sensei_Utils::sensei_start_lesson( $lesson_2, $user_id_2 );

		// Complete lesson 1 and lesson 2 with user_1.
		Sensei_Utils::sensei_start_lesson( $lesson_3, $user_id_1, true );
		Sensei_Utils::sensei_start_lesson( $lesson_4, $user_id_1 );

		// Enroll student 2 to the course and lessons, but don't complete the lessons.
		Sensei_Utils::sensei_start_lesson( $lesson_3, $user_id_2 );
		Sensei_Utils::sensei_start_lesson( $lesson_4, $user_id_2 );
		/* Act. */
		$actual = $service->get_total_average_progress( array( $course_id_1, $course_id_2 ) );

		/* Assert. */
		$this->assertSame(
			38.0,
			$actual,
			'Find totals of lessons completed multiple courses.'
		);
	}

	/**
	 * Tests getting total average progress value for the course based on the lessons completion to be zero.
	 *
	 * @covers Sensei_Reports_Overview_Service_Courses::get_total_average_progress
	 */
	public function testGetTotalAverageProgress_StartedLessonsGiven_ReturnsZero() {
		/* Arrange. */
		// Create a course 1
		$course_id_1 = $this->factory->course->create();

		// Create single
		$user_id_2 = $this->factory->user->create();

		//Add 2 lessons to the course 1
		$lesson_1 = $this->factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id_1 ) )
		);
		$lesson_2 = $this->factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id_1 ) )
		);
		$service  = new Sensei_Reports_Overview_Service_Courses();

		// Enroll student 2 to the course and lessons, but don't complete the lessons.
		Sensei_Utils::sensei_start_lesson( $lesson_1, $user_id_2 );
		Sensei_Utils::sensei_start_lesson( $lesson_2, $user_id_2 );

		/* Act. */
		$actual = $service->get_total_average_progress( array( $course_id_1 ) );

		/* Assert. */
		$this->assertSame(
			0.0,
			$actual,
			'Find average progress total is 0 when no lesson is completed'
		);
	}

	/**
	 * Tests that an empty course selection excludes existing completed progress.
	 *
	 * @covers Sensei_Reports_Overview_Service_Courses::get_total_average_progress
	 */
	public function testGetTotalAverageProgress_EmptyCourseIdsGiven_ReturnsZero() {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		$user_id   = $this->factory->user->create();
		Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id, true );

		$service = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $service->get_total_average_progress( array() );

		/* Assert. */
		$this->assertSame(
			0.0,
			$actual,
			'An empty selection must exclude progress in other courses.'
		);
	}

	/**
	 * Tests getting total average progress value for the course based on the lessons completion for multiple students.
	 *
	 * @covers Sensei_Reports_Overview_Service_Courses::get_total_average_progress
	 */
	public function testGetTotalAverageProgress_CompletedLessonsForMultipleStudentsGiven_ReturnsFullProgress() {
		/* Arrange. */
		// Create first course
		$course_id_1 = $this->factory->course->create();

		// Create second course
		$course_id_2 = $this->factory->course->create();

		// Create 3 users
		$user_id_1 = $this->factory->user->create();
		$user_id_2 = $this->factory->user->create();
		$user_id_3 = $this->factory->user->create();

		//Add 2 lessons to the first course
		$lesson_1 = $this->factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id_1 ) )
		);
		$lesson_2 = $this->factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id_1 ) )
		);

		// Add 1 lesson to the second course
		$lesson_3 = $this->factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id_2 ) )
		);
		$service  = new Sensei_Reports_Overview_Service_Courses();

		// Complete lesson 1 and lesson 2 with user_1.
		Sensei_Utils::sensei_start_lesson( $lesson_1, $user_id_1, true );
		Sensei_Utils::sensei_start_lesson( $lesson_2, $user_id_1, true );

		// Enroll student 2 to the course and lessons, but don't complete the lessons.
		Sensei_Utils::sensei_start_lesson( $lesson_1, $user_id_2, true );
		Sensei_Utils::sensei_start_lesson( $lesson_2, $user_id_2, true );

		// Enroll 1 student to the second course and complete lesson
		Sensei_Utils::sensei_start_lesson( $lesson_3, $user_id_3, true );

		/* Act. */
		$actual = $service->get_total_average_progress( array( $course_id_1, $course_id_2 ) );

		/* Assert. */
		$this->assertSame(
			100.0,
			$actual,
			'Find totals of lessons completed single course.'
		);
	}
	public function testGetTotalAverageProgress_WPMLTranslatedCourseGiven_ReturnsOriginalProgress() {
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
		$actual = $service->get_total_average_progress( array( $translated_course ) );

		/* Assert. */
		self::assertSame( 100.0, $actual );
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

<?php

use Sensei\Internal\Services\Grading_Stats_Service_Interface;
use Sensei\Internal\Services\Progress_Aggregation_Service_Interface;

/**
 * Sensei Reports Overview Service Courses Test Class
 *
 * @covers Sensei_Reports_Overview_Service_Courses
 */
class Sensei_Reports_Overview_Service_Courses_Test extends WP_UnitTestCase {
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
	 * Returns complete counts, including zero for courses without completions.
	 *
	 * @covers Sensei_Reports_Overview_Service_Courses::get_completion_counts_by_course
	 */
	public function testGetCompletionCountsByCourse_CoursesWithMixedStatusesGiven_ReturnsCompletionCountsIncludingZero(): void {
		/* Arrange. */
		$aggregation_service = $this->createMock( Progress_Aggregation_Service_Interface::class );
		$aggregation_service->method( 'count_statuses_by_post' )->willReturn(
			array(
				11 => array(
					'complete'    => 2,
					'in-progress' => 1,
				),
				22 => array( 'in-progress' => 3 ),
			)
		);
		$service = Sensei_Reports_Overview_Service_Courses::create_with_dependencies(
			$this->createMock( Grading_Stats_Service_Interface::class ),
			$aggregation_service
		);

		/* Act. */
		$actual = $service->get_completion_counts_by_course( array( 11, 22, 33 ) );

		/* Assert. */
		self::assertSame(
			array(
				11 => 2,
				22 => 0,
				33 => 0,
			),
			$actual
		);
	}

	public function testGetTotalAverageProgress_CoursesGiven_AveragesCourseProgressAndRoundsUp(): void {
		/* Arrange. */
		$course_ids = array( 11, 22 );
		$service    = $this->getMockBuilder( Sensei_Reports_Overview_Service_Courses::class )
			->onlyMethods( array( 'get_average_progress_by_course' ) )
			->getMock();
		$service
			->expects( self::once() )
			->method( 'get_average_progress_by_course' )
			->with( $course_ids )
			->willReturn(
				array(
					11 => 50.0,
					22 => 25.0,
				)
			);

		/* Act. */
		$actual = $service->get_total_average_progress( $course_ids );

		/* Assert. */
		// The 37.5 average of 50% and 25% is rounded up to 38%.
		self::assertSame( 38.0, $actual );
	}

	public function testGetTotalAverageProgress_CourseWithoutPerCourseResultGiven_ContributesZeroToAverage(): void {
		/* Arrange. */
		$course_ids = array( 11, 22 );
		$service    = $this->getMockBuilder( Sensei_Reports_Overview_Service_Courses::class )
			->onlyMethods( array( 'get_average_progress_by_course' ) )
			->getMock();
		$service
			->expects( self::once() )
			->method( 'get_average_progress_by_course' )
			->with( $course_ids )
			->willReturn( array( 11 => 50.0 ) );

		/* Act. */
		$actual = $service->get_total_average_progress( $course_ids );

		/* Assert. */
		// The missing course contributes 0%, so the average is ( 50 + 0 ) / 2 = 25%.
		self::assertSame( 25.0, $actual );
	}

	public function testGetTotalAverageProgress_EmptyCourseIdsGiven_ReturnsZero(): void {
		/* Arrange. */
		$service = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $service->get_total_average_progress( array() );

		/* Assert. */
		self::assertSame( 0.0, $actual );
	}

	public function testGetAverageProgressByCourse_CoursesGiven_ReturnsAverageProgressByCourse(): void {
		/* Arrange. */
		$first_course  = $this->factory->course->create();
		$second_course = $this->factory->course->create();
		$first_user    = $this->factory->user->create();
		$second_user   = $this->factory->user->create();
		$first_lessons = array(
			$this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $first_course ) ) ),
			$this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $first_course ) ) ),
		);
		$second_lesson = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $second_course ) ) );

		foreach ( $first_lessons as $lesson_id ) {
			Sensei_Utils::sensei_start_lesson( $lesson_id, $first_user, true );
			Sensei_Utils::sensei_start_lesson( $lesson_id, $second_user );
		}
		Sensei_Utils::sensei_start_lesson( $second_lesson, $first_user, true );

		$service = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $service->get_average_progress_by_course( array( $first_course, $second_course ) );

		/* Assert. */
		// First course: 2 completed student-lesson pairs / ( 2 students * 2 lessons ) = 50%.
		// Second course: 1 completed student-lesson pair / ( 1 student * 1 lesson ) = 100%.
		$expected = array(
			$first_course  => 50.0,
			$second_course => 100.0,
		);
		self::assertSame( $expected, $actual );
	}

	public function testGetAverageProgressByCourse_NoLessonCompletionsGiven_ReturnsZeroProgress(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$user_id   = $this->factory->user->create();
		$lessons   = array(
			$this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) ),
			$this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) ),
		);
		foreach ( $lessons as $lesson_id ) {
			Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );
		}
		$service = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $service->get_average_progress_by_course( array( $course_id ) );

		/* Assert. */
		// The student completed none of the two lessons: 0 / 2 = 0%.
		self::assertSame( array( $course_id => 0.0 ), $actual );
	}

	public function testGetAverageProgressByCourse_CourseWithoutStudentsGiven_ReturnsNoProgress(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		$service = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $service->get_average_progress_by_course( array( $course_id ) );

		/* Assert. */
		self::assertSame( array(), $actual );
	}

	public function testGetAverageProgressByCourse_TemporaryUsersGiven_ExcludesTheirCompletionsAndEnrollments(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		$users     = array(
			'registered_complete' => $this->factory->user->create( array( 'user_login' => 'registered_complete' ) ),
			'registered_started'  => $this->factory->user->create( array( 'user_login' => 'registered_started' ) ),
			'guest_complete'      => $this->factory->user->create( array( 'user_login' => 'sensei_guest_complete' ) ),
			'preview_complete'    => $this->factory->user->create( array( 'user_login' => 'sensei_preview_complete' ) ),
		);

		Sensei_Utils::sensei_start_lesson( $lesson_id, $users['registered_complete'], true );
		Sensei_Utils::sensei_start_lesson( $lesson_id, $users['registered_started'] );
		Sensei_Utils::sensei_start_lesson( $lesson_id, $users['guest_complete'], true );
		Sensei_Utils::sensei_start_lesson( $lesson_id, $users['preview_complete'], true );
		$service = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $service->get_average_progress_by_course( array( $course_id ) );

		/* Assert. */
		// One of the two registered students completed the only lesson: 1 / 2 = 50%.
		self::assertSame( array( $course_id => 50.0 ), $actual );
	}

	public function testGetAverageProgressByCourse_LessonsWithMixedPostStatusesGiven_CountsPublishedAndPrivateLessons(): void {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$user_id    = $this->factory->user->create();
		$lesson_ids = array(
			'published' => $this->factory->lesson->create(
				array(
					'post_status' => 'publish',
					'meta_input'  => array( '_lesson_course' => $course_id ),
				)
			),
			'private'   => $this->factory->lesson->create(
				array(
					'post_status' => 'private',
					'meta_input'  => array( '_lesson_course' => $course_id ),
				)
			),
			'draft_one' => $this->factory->lesson->create(
				array(
					'post_status' => 'draft',
					'meta_input'  => array( '_lesson_course' => $course_id ),
				)
			),
			'draft_two' => $this->factory->lesson->create(
				array(
					'post_status' => 'draft',
					'meta_input'  => array( '_lesson_course' => $course_id ),
				)
			),
			'trashed'   => $this->factory->lesson->create(
				array(
					'post_status' => 'publish',
					'meta_input'  => array( '_lesson_course' => $course_id ),
				)
			),
		);

		$course_progress = Sensei()->course_progress_repository->create( $course_id, $user_id );
		$course_progress->start();
		Sensei()->course_progress_repository->save( $course_progress );

		foreach ( array( 'published', 'draft_one', 'draft_two', 'trashed' ) as $completed_lesson ) {
			$lesson_progress = Sensei()->lesson_progress_repository->create( $lesson_ids[ $completed_lesson ], $user_id );
			$lesson_progress->complete();
			Sensei()->lesson_progress_repository->save( $lesson_progress );
		}

		$private_lesson_progress = Sensei()->lesson_progress_repository->create( $lesson_ids['private'], $user_id );
		$private_lesson_progress->start();
		Sensei()->lesson_progress_repository->save( $private_lesson_progress );
		wp_trash_post( $lesson_ids['trashed'] );

		$service = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $service->get_average_progress_by_course( array( $course_id ) );

		/* Assert. */
		// Only publish and private count: one of those two lessons is complete, so progress is 50%.
		self::assertSame( array( $course_id => 50.0 ), $actual );
	}

	public function testGetAverageProgressByCourse_TranslatedCourseGiven_UsesOriginalLessonsAndEnrollments(): void {
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
		$this->add_progress_id_filter( array( $translated_course => $original_course ) );
		$service = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $service->get_average_progress_by_course( array( $translated_course ) );

		/* Assert. */
		// One completed lesson out of two lessons for each of two students: 25%.
		self::assertSame( array( $translated_course => 25.0 ), $actual );
	}

	public function testGetAverageProgressByCourse_EmptyCourseIdsGiven_ReturnsEmptyArray(): void {
		/* Arrange. */
		$service = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $service->get_average_progress_by_course( array() );

		/* Assert. */
		self::assertSame( array(), $actual );
	}

	/**
	 * Tests that average grade returns zero when courses have no graded quizzes.
	 *
	 * @covers Sensei_Reports_Overview_Service_Courses::get_courses_average_grade
	 */
	public function testGetCoursesAverageGrade_WhenNoGradedQuizzes_ReturnsZero() {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$instance  = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $instance->get_courses_average_grade( array( $course_id ) );

		/* Assert. */
		self::assertSame( 0.0, $actual, 'Average grade should be zero when there are no graded quizzes.' );
	}

	/**
	 * The overall grade averages eligible course averages, without weighting by attempt counts or rounding first.
	 *
	 * @covers Sensei_Reports_Overview_Service_Courses::get_courses_average_grade
	 * @dataProvider course_grade_totals
	 */
	public function testGetCoursesAverageGrade_PerCourseTotalsGiven_AveragesUnroundedEligibleCourses( array $totals, float $expected, array $translations = array() ): void {
		$this->add_progress_id_filter( $translations );
		$service = $this->getMockBuilder( Sensei_Reports_Overview_Service_Courses::class )
			->onlyMethods( array( 'get_grade_totals_by_course' ) )
			->getMock();
		$service->method( 'get_grade_totals_by_course' )->willReturnCallback(
			static function ( $course_ids ) use ( $totals ) {
				return array_intersect_key( $totals, array_flip( $course_ids ) );
			}
		);

		$actual = $service->get_courses_average_grade( array( 11, 22, 33 ) );

		self::assertEqualsWithDelta( $expected, $actual, 0.0000000001 );
	}

	/**
	 * Course averages preserve requested keys, zero grades, and precision while omitting missing grades.
	 *
	 * @covers Sensei_Reports_Overview_Service_Courses::get_average_grades_by_course
	 */
	public function testGetAverageGradesByCourse_GradeTotalsGiven_ReturnsUnroundedEligibleCourseGrades(): void {
		$service = $this->getMockBuilder( Sensei_Reports_Overview_Service_Courses::class )
			->onlyMethods( array( 'get_grade_totals_by_course' ) )
			->getMock();
		$service->method( 'get_grade_totals_by_course' )->willReturn(
			array(
				17 => array(
					'count' => 3,
					'sum'   => 100.0,
				),
				21 => array(
					'count' => 1,
					'sum'   => 0.0,
				),
				23 => array(
					'count' => 0,
					'sum'   => 0.0,
				),
			)
		);

		$actual = $service->get_average_grades_by_course( array( 17, 21, 23 ) );

		self::assertSame(
			array(
				17 => 33.333333333333336,
				21 => 0.0,
			),
			$actual
		);
	}

	/**
	 * One lesson with quiz questions makes the course eligible, and all its lesson grades contribute.
	 *
	 * @covers Sensei_Reports_Overview_Service_Courses::get_grade_totals_by_course
	 * @dataProvider course_quiz_question_meta
	 */
	public function testGetGradeTotalsByCourse_QuizQuestionMetaGiven_IncludesAllLessonsOnlyWhenAnyHasQuestions( array $quiz_question_meta, array $expected ): void {
		$course_id = $this->factory->course->create();
		$grades    = array();
		foreach ( $quiz_question_meta as $has_questions ) {
			$lesson_id            = $this->factory->lesson->create(
				array(
					'meta_input' => array(
						'_lesson_course'      => $course_id,
						'_quiz_has_questions' => $has_questions,
					),
				)
			);
			$grades[ $lesson_id ] = array(
				'count' => 1,
				'sum'   => 40.0,
			);
		}
		$grading = $this->createMock( Grading_Stats_Service_Interface::class );
		$grading->method( 'get_grade_totals_by_lesson' )->willReturn( $grades );
		$service = Sensei_Reports_Overview_Service_Courses::create_with_dependencies( $grading, $this->createMock( Progress_Aggregation_Service_Interface::class ) );

		$actual = $service->get_grade_totals_by_course( array( $course_id ) );

		self::assertSame( array( $course_id => $expected ), $actual );
	}

	/**
	 * Translated courses and lessons use original progress and retain requested course keys.
	 *
	 * @covers Sensei_Reports_Overview_Service_Courses::get_grade_totals_by_course
	 */
	public function testGetGradeTotalsByCourse_TranslatedIdsGiven_ReturnsRequestedCourseTotals(): void {
		$created           = $this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 2,
				'question_count' => 1,
			)
		);
		$original          = $created['course_id'];
		$translation       = $this->factory->course->create();
		$empty             = $this->factory->course->create();
		$translated_lesson = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $original ) ) );
		wp_update_post(
			array(
				'ID'          => $created['lesson_ids'][0],
				'post_status' => 'draft',
			)
		);
		add_filter(
			'sensei_lesson_progress_get_lesson_id',
			static function ( $id ) use ( $translated_lesson, $created ) {
				return $translated_lesson === $id ? $created['lesson_ids'][0] : $id;
			}
		);
		add_filter(
			'sensei_course_progress_get_course_id',
			static function ( $id ) use ( $original, $translation ) {
				return $translation === $id ? $original : $id;
			}
		);
		$grading = $this->createMock( Grading_Stats_Service_Interface::class );
		$grading->expects( self::once() )
			->method( 'get_grade_totals_by_lesson' )
			->with(
				self::callback(
					static function ( $ids ) use ( $created ) {
						sort( $ids );
						return $created['lesson_ids'] === $ids;
					}
				),
				array(
					'exclude_user_login_prefixes' => \Sensei\Internal\Services\Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES,
				)
			)
			->willReturn(
				array(
					$created['lesson_ids'][0] => array(
						'count' => 1,
						'sum'   => 0.0,
					),
					$created['lesson_ids'][1] => array(
						'count' => 1,
						'sum'   => 80.0,
					),
				)
			);
		$service = Sensei_Reports_Overview_Service_Courses::create_with_dependencies( $grading, $this->createMock( Progress_Aggregation_Service_Interface::class ) );

		$actual = $service->get_grade_totals_by_course( array( $translation, $empty ) );

		self::assertSame(
			array(
				$translation => array(
					'count' => 2,
					'sum'   => 80.0,
				),
				$empty       => array(
					'count' => 0,
					'sum'   => 0.0,
				),
			),
			$actual,
			'Translated IDs should resolve to both stored lesson totals and retain the requested course key.'
		);
	}

	public function testGetGradeSumForLessons_LessonIdsGiven_ReturnsServiceResult() {
		/* Arrange. */
		$lesson_ids                   = array( 11, 22 );
		$grading_stats_service        = $this->createMock( Grading_Stats_Service_Interface::class );
		$progress_aggregation_service = $this->createMock( Progress_Aggregation_Service_Interface::class );
		$grading_stats_service
			->expects( self::once() )
			->method( 'get_grade_totals' )
			->with( array( 'post__in' => $lesson_ids ) )
			->willReturn(
				array(
					'count' => 2,
					'sum'   => 75.0,
				)
			);
		$instance = Sensei_Reports_Overview_Service_Courses::create_with_dependencies( $grading_stats_service, $progress_aggregation_service );

		/* Act. */
		$actual = $instance->get_grade_sum_for_lessons( $lesson_ids );

		/* Assert. */
		self::assertSame( 75, $actual );
	}

	public function testGetAverageDaysToCompletion_EmptyCourseIdsGiven_ReturnsZero() {
		/* Arrange. */
		$instance = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $instance->get_average_days_to_completion( array() );

		/* Assert. */
		self::assertSame( 0.0, $actual );
	}

	public function testGetAverageDaysToCompletion_SharedOriginalProgressAndDifferentCompletionCountsGiven_WeightsRequestedCoursesEqually() {
		/* Arrange. */
		$user1_id   = $this->factory->user->create();
		$user2_id   = $this->factory->user->create();
		$course1_id = $this->factory->course->create();
		$course2_id = $this->factory->course->create();

		$this->seed_course_completion_with_dates( $course1_id, $user1_id, '2022-03-11 23:27:51', '2022-03-11 23:29:06' );
		$this->seed_course_completion_with_dates( $course1_id, $user2_id, '2022-03-14 21:34:27', '2022-03-14 21:34:37' );
		$this->seed_course_completion_with_dates( $course2_id, $user1_id, '2022-03-09 00:22:34', '2022-03-12 00:22:37' );

		$translated_course = $this->factory->course->create();
		$this->add_progress_id_filter( array( $translated_course => $course1_id ) );

		$instance = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $instance->get_average_days_to_completion( array( $course1_id, $translated_course, $course2_id ) );

		// Average for the first course: (1 + 1) / 2 = 1.
		// Average for the second course: 4 / 1 = 4.
		// Original and translated courses each retain a weight: (1 + 1 + 4) / 3 = 2.

		/* Assert. */
		self::assertSame( 2.0, $actual );
	}



	/**
	 * An empty selection does not count unrelated course progress.
	 */
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

	/**
	 * One student enrolled in two courses contributes two enrollments.
	 */
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

	public function testGetTotalEnrollments_TemporaryUsersGiven_CountsOnlyRegisteredStudents(): void {
		/* Arrange. */
		$course = $this->factory->course->create();
		foreach ( array( 'registered_student', 'sensei_guest_student', 'sensei_preview_student' ) as $login ) {
			$user_id = $this->factory->user->create( array( 'user_login' => $login ) );
			Sensei_Utils::user_start_course( $user_id, $course );
		}
		$service = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $service->get_total_enrollments( array( $course ) );

		/* Assert. */
		self::assertSame( 1, $actual );
	}

	/**
	 * Translated courses read enrollments stored against the original course.
	 */
	public function testGetTotalEnrollments_TranslatedCourseGiven_ReturnsOriginalEnrollments() {
		/* Arrange. */
		$original_course   = $this->factory->course->create();
		$translated_course = $this->factory->course->create();
		$original_lesson   = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $original_course ) ) );
		$user_id           = $this->factory->user->create();
		Sensei_Utils::sensei_start_lesson( $original_lesson, $user_id, true );
		$this->seed_course_completion_with_dates( $original_course, $user_id, '2022-01-01 00:00:00', '2022-01-02 00:00:00' );
		$this->add_progress_id_filter( array( $translated_course => $original_course ) );
		$service = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $service->get_total_enrollments( array( $translated_course ) );

		/* Assert. */
		self::assertSame( 1, $actual );
	}

	public static function course_quiz_question_meta(): array {
		return array(
			'first lesson has questions' => array(
				array( true, false ),
				array(
					'count' => 2,
					'sum'   => 80.0,
				),
			),
			'last lesson has questions'  => array(
				array( false, true ),
				array(
					'count' => 2,
					'sum'   => 80.0,
				),
			),
			'neither has questions'      => array(
				array( false, false ),
				array(
					'count' => 0,
					'sum'   => 0.0,
				),
			),
		);
	}

	public static function course_grade_totals(): array {
		return array(
			'equal course weights and missing grades' => array(
				array(
					11 => array(
						'count' => 2,
						'sum'   => 80.0,
					),
					22 => array(
						'count' => 1,
						'sum'   => 90.0,
					),
					33 => array(
						'count' => 0,
						'sum'   => 0.0,
					),
				),
				65.0,
			),
			'zero grade included'                     => array(
				array(
					11 => array(
						'count' => 1,
						'sum'   => 0.0,
					),
					22 => array(
						'count' => 1,
						'sum'   => 80.0,
					),
				),
				40.0,
			),
			'no eligible grades'                      => array(
				array(
					11 => array(
						'count' => 0,
						'sum'   => 0.0,
					),
				),
				0.0,
			),
			'shared translated progress counted once' => array(
				array(
					11 => array(
						'count' => 1,
						'sum'   => 40.0,
					),
					22 => array(
						'count' => 1,
						'sum'   => 90.0,
					),
					33 => array(
						'count' => 1,
						'sum'   => 40.0,
					),
				),
				65.0,
				array( 33 => 11 ),
			),
			'per-course precision retained'           => array(
				array(
					11 => array(
						'count' => 3,
						'sum'   => 100.0,
					),
					22 => array(
						'count' => 1,
						'sum'   => 66.67,
					),
				),
				50.001666666666665,
			),
		);
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
	 * Map requested course IDs to the IDs used for stored progress.
	 *
	 * @param array $map Requested IDs mapped to stored progress IDs.
	 */
	private function add_progress_id_filter( array $map ): void {
		add_filter(
			'sensei_course_progress_get_course_id',
			static function ( $post_id ) use ( $map ) {
				return $map[ $post_id ] ?? $post_id;
			}
		);
	}
}

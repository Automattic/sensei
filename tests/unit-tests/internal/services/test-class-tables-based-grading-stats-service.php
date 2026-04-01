<?php
/**
 * Tests for grading stats service.
 *
 * @package sensei-tests
 */

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Tables_Based_Grading_Stats_Service;

/**
 * Class Tables_Based_Grading_Stats_Service_Test.
 *
 * @covers \Sensei\Internal\Services\Tables_Based_Grading_Stats_Service
 */
class Tables_Based_Grading_Stats_Service_Test extends \WP_UnitTestCase {

	/**
	 * Service instance.
	 *
	 * @var mixed
	 */
	private $sensei_factory;

	/**
	 * Test setUp.
	 */
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
		$data   = array(
			'post_id'    => $post_id,
			'user_id'    => $user_id,
			'type'       => $type,
			'status'     => $status,
			'started_at' => $now,
			'created_at' => $now,
			'updated_at' => $now,
		);
		$format = array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' );
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
		$data   = array(
			'quiz_id'    => $quiz_id,
			'user_id'    => $user_id,
			'created_at' => $now,
			'updated_at' => $now,
		);
		$format = array( '%d', '%d', '%s', '%s' );
		if ( null !== $final_grade ) {
			$data['final_grade'] = $final_grade;
			$format[]            = '%d';
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test helper.
		$wpdb->insert( $table, $data, $format );
	}

	/**
	 * Helper to set up a graded lesson with quiz in HPPS tables.
	 *
	 * @param int    $lesson_id  The lesson ID.
	 * @param int    $quiz_id    The quiz ID.
	 * @param int    $user_id    The user ID.
	 * @param int    $course_id  The course ID.
	 * @param string $status     The quiz status.
	 * @param int    $grade      The grade value.
	 */
	private function create_graded_lesson( int $lesson_id, int $quiz_id, int $user_id, int $course_id, string $status, int $grade ): void {
		$this->insert_progress( $lesson_id, $user_id, 'lesson', $status, $course_id );
		$this->insert_progress( $quiz_id, $user_id, 'quiz', $status, $lesson_id );
		$this->insert_quiz_submission( $quiz_id, $user_id, $grade );
	}

	/**
	 * Test testGetGradeTotals_WithNoData_ReturnsZeros.
	 */
	public function testGetGradeTotals_WithNoData_ReturnsZeros(): void {
		global $wpdb;
		$service = new Tables_Based_Grading_Stats_Service( $wpdb );

		$result = $service->get_grade_totals();

		$this->assertSame( 0, $result['count'] );
		$this->assertSame( 0.0, $result['sum'] );
	}

	/**
	 * Test testGetGradeTotals_WithGradedLesson_ReturnsCountAndSum.
	 */
	public function testGetGradeTotals_WithGradedLesson_ReturnsCountAndSum(): void {
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_id   = $this->sensei_factory->quiz->create(
			array( 'post_parent' => $lesson_id )
		);

		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_id, $course_id, 'graded', 80 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals();

		$this->assertSame( 1, $result['count'] );
		$this->assertSame( 80.0, $result['sum'] );
	}

	/**
	 * Test testGetGradeTotals_WithUserIdFilter_ReturnsFilteredResults.
	 */
	public function testGetGradeTotals_WithUserIdFilter_ReturnsFilteredResults(): void {
		global $wpdb;
		$user_1    = $this->sensei_factory->user->create();
		$user_2    = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_id   = $this->sensei_factory->quiz->create(
			array( 'post_parent' => $lesson_id )
		);

		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_1, $course_id, 'graded', 80 );
		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_2, $course_id, 'graded', 60 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals( array( 'user_id' => $user_1 ) );

		$this->assertSame( 1, $result['count'] );
		$this->assertSame( 80.0, $result['sum'] );
	}

	/**
	 * Test testGetGradeTotals_WithLessonIdFilter_ReturnsFilteredResults.
	 */
	public function testGetGradeTotals_WithLessonIdFilter_ReturnsFilteredResults(): void {
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_1  = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_1    = $this->sensei_factory->quiz->create(
			array( 'post_parent' => $lesson_1 )
		);
		$lesson_2  = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_2    = $this->sensei_factory->quiz->create(
			array( 'post_parent' => $lesson_2 )
		);

		$this->create_graded_lesson( $lesson_1, $quiz_1, $user_id, $course_id, 'graded', 80 );
		$this->create_graded_lesson( $lesson_2, $quiz_2, $user_id, $course_id, 'graded', 60 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals( array( 'lesson_id' => $lesson_1 ) );

		$this->assertSame( 1, $result['count'] );
		$this->assertSame( 80.0, $result['sum'] );
	}

	/**
	 * Test testGetGradeTotals_WithPostInFilter_ReturnsFilteredResults.
	 */
	public function testGetGradeTotals_WithPostInFilter_ReturnsFilteredResults(): void {
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_1  = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_1    = $this->sensei_factory->quiz->create(
			array( 'post_parent' => $lesson_1 )
		);
		$lesson_2  = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_2    = $this->sensei_factory->quiz->create(
			array( 'post_parent' => $lesson_2 )
		);
		$lesson_3  = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_3    = $this->sensei_factory->quiz->create(
			array( 'post_parent' => $lesson_3 )
		);

		$this->create_graded_lesson( $lesson_1, $quiz_1, $user_id, $course_id, 'graded', 80 );
		$this->create_graded_lesson( $lesson_2, $quiz_2, $user_id, $course_id, 'graded', 60 );
		$this->create_graded_lesson( $lesson_3, $quiz_3, $user_id, $course_id, 'graded', 40 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals( array( 'post__in' => array( $lesson_1, $lesson_2 ) ) );

		$this->assertSame( 2, $result['count'] );
		$this->assertSame( 140.0, $result['sum'] );
	}

	/**
	 * Test testGetGradeTotals_WithNullFinalGrade_ExcludesFromResults.
	 */
	public function testGetGradeTotals_WithNullFinalGrade_ExcludesFromResults(): void {
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_id   = $this->sensei_factory->quiz->create(
			array( 'post_parent' => $lesson_id )
		);

		// Lesson progress exists but quiz submission has no grade.
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'graded', $course_id );
		$this->insert_progress( $quiz_id, $user_id, 'quiz', 'graded', $lesson_id );
		$this->insert_quiz_submission( $quiz_id, $user_id, null );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals();

		$this->assertSame( 0, $result['count'] );
	}

	/**
	 * Test testGetCoursesAverageGrade_WithNoData_ReturnsZero.
	 */
	public function testGetCoursesAverageGrade_WithNoData_ReturnsZero(): void {
		global $wpdb;
		$service = new Tables_Based_Grading_Stats_Service( $wpdb );

		$result = $service->get_courses_average_grade();

		$this->assertSame( 0.0, $result );
	}

	/**
	 * Test testGetCoursesAverageGrade_WithGradedLessons_ReturnsAverage.
	 */
	public function testGetCoursesAverageGrade_WithGradedLessons_ReturnsAverage(): void {
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_id   = $this->sensei_factory->quiz->create(
			array( 'post_parent' => $lesson_id )
		);

		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_id, $course_id, 'graded', 80 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_courses_average_grade();

		$this->assertSame( 80.0, $result );
	}

	/**
	 * Test testGetCoursesAverageGrade_WithCourseIdsFilter_ReturnsFilteredAverage.
	 */
	public function testGetCoursesAverageGrade_WithCourseIdsFilter_ReturnsFilteredAverage(): void {
		global $wpdb;
		$user_id  = $this->sensei_factory->user->create();
		$course_1 = $this->sensei_factory->course->create();
		$course_2 = $this->sensei_factory->course->create();
		$lesson_1 = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_1 ) )
		);
		$quiz_1   = $this->sensei_factory->quiz->create(
			array( 'post_parent' => $lesson_1 )
		);
		$lesson_2 = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_2 ) )
		);
		$quiz_2   = $this->sensei_factory->quiz->create(
			array( 'post_parent' => $lesson_2 )
		);

		$this->create_graded_lesson( $lesson_1, $quiz_1, $user_id, $course_1, 'graded', 80 );
		$this->create_graded_lesson( $lesson_2, $quiz_2, $user_id, $course_2, 'graded', 60 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_courses_average_grade( array( $course_1 ) );

		$this->assertSame( 80.0, $result );
	}

	/**
	 * Test testGetCoursesAverageGrade_WithMultipleCourses_ReturnsAverageOfAverages.
	 */
	public function testGetCoursesAverageGrade_WithMultipleCourses_ReturnsAverageOfAverages(): void {
		global $wpdb;
		$user_id  = $this->sensei_factory->user->create();
		$course_1 = $this->sensei_factory->course->create();
		$course_2 = $this->sensei_factory->course->create();
		$lesson_1 = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_1 ) )
		);
		$quiz_1   = $this->sensei_factory->quiz->create(
			array( 'post_parent' => $lesson_1 )
		);
		$lesson_2 = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_2 ) )
		);
		$quiz_2   = $this->sensei_factory->quiz->create(
			array( 'post_parent' => $lesson_2 )
		);

		// Course 1: grade 80, Course 2: grade 60. Average of averages = (80 + 60) / 2 = 70.
		$this->create_graded_lesson( $lesson_1, $quiz_1, $user_id, $course_1, 'graded', 80 );
		$this->create_graded_lesson( $lesson_2, $quiz_2, $user_id, $course_2, 'graded', 60 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_courses_average_grade();

		$this->assertSame( 70.0, $result );
	}

	/**
	 * Tests that get_grade_totals only includes graded, passed, and failed statuses.
	 */
	public function testGetGradeTotals_WithMixedStatuses_OnlyCountsGradedPassedFailed(): void {
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();

		// These should be included.
		$lesson_1 = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_1   = $this->sensei_factory->quiz->create(
			array( 'post_parent' => $lesson_1 )
		);
		$this->create_graded_lesson( $lesson_1, $quiz_1, $user_id, $course_id, 'graded', 80 );

		$lesson_2 = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_2   = $this->sensei_factory->quiz->create(
			array( 'post_parent' => $lesson_2 )
		);
		$this->create_graded_lesson( $lesson_2, $quiz_2, $user_id, $course_id, 'passed', 90 );

		$lesson_3 = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_3   = $this->sensei_factory->quiz->create(
			array( 'post_parent' => $lesson_3 )
		);
		$this->create_graded_lesson( $lesson_3, $quiz_3, $user_id, $course_id, 'failed', 40 );

		// This should be excluded: 'in-progress' status with a grade.
		$lesson_4 = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_4   = $this->sensei_factory->quiz->create(
			array( 'post_parent' => $lesson_4 )
		);
		$this->insert_progress( $lesson_4, $user_id, 'lesson', 'in-progress', $course_id );
		$this->insert_progress( $quiz_4, $user_id, 'quiz', 'in-progress', $lesson_4 );
		$this->insert_quiz_submission( $quiz_4, $user_id, 100 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals();

		$this->assertSame( 3, $result['count'] );
		$this->assertSame( 210.0, $result['sum'] ); // 80 + 90 + 40.
	}

	/**
	 * Tests that get_courses_average_grade excludes lessons without quizzes.
	 */
	public function testGetCoursesAverageGrade_WithLessonWithoutQuiz_ExcludesFromAverage(): void {
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();

		// Lesson with quiz.
		$lesson_1 = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_1   = $this->sensei_factory->quiz->create(
			array( 'post_parent' => $lesson_1 )
		);
		$this->create_graded_lesson( $lesson_1, $quiz_1, $user_id, $course_id, 'graded', 80 );

		// Lesson without quiz: only lesson progress, no quiz progress or submission.
		$lesson_2 = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$this->insert_progress( $lesson_2, $user_id, 'lesson', 'complete', $course_id );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_courses_average_grade();

		// Only the lesson with quiz should be included.
		$this->assertSame( 80.0, $result );
	}

	/**
	 * Test testGetUsersAverageGrade_WithNoUserIds_ReturnsZero.
	 */
	public function testGetUsersAverageGrade_WithNoUserIds_ReturnsZero(): void {
		global $wpdb;
		$service = new Tables_Based_Grading_Stats_Service( $wpdb );

		$result = $service->get_users_average_grade( array() );

		$this->assertSame( 0.0, $result );
	}

	/**
	 * Test testGetUsersAverageGrade_WithUserIds_ReturnsAverage.
	 */
	public function testGetUsersAverageGrade_WithUserIds_ReturnsAverage(): void {
		global $wpdb;
		$user_1    = $this->sensei_factory->user->create();
		$user_2    = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_id   = $this->sensei_factory->quiz->create(
			array( 'post_parent' => $lesson_id )
		);

		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_1, $course_id, 'graded', 80 );
		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_2, $course_id, 'graded', 60 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_users_average_grade( array( $user_1, $user_2 ) );

		$this->assertSame( 70.0, $result );
	}

	/**
	 * Test testGetUsersAverageGrade_FilteredBySpecificUser_ReturnsUserAverage.
	 */
	public function testGetUsersAverageGrade_FilteredBySpecificUser_ReturnsUserAverage(): void {
		global $wpdb;
		$user_1    = $this->sensei_factory->user->create();
		$user_2    = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$quiz_id   = $this->sensei_factory->quiz->create(
			array( 'post_parent' => $lesson_id )
		);

		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_1, $course_id, 'graded', 80 );
		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_2, $course_id, 'graded', 60 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_users_average_grade( array( $user_1 ) );

		$this->assertSame( 80.0, $result );
	}
}

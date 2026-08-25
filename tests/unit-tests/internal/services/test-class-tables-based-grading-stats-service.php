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
	 */
	private function insert_progress( int $post_id, int $user_id, string $type, string $status ): void {
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
		update_post_meta( $lesson_id, '_lesson_course', $course_id );
		update_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );

		$this->insert_progress( $lesson_id, $user_id, 'lesson', $status );
		$this->insert_progress( $quiz_id, $user_id, 'quiz', $status );
		$this->insert_quiz_submission( $quiz_id, $user_id, $grade );
	}

	/**
	 * Test testGetGradeTotals_WithNoData_ReturnsZeros.
	 */
	public function testGetGradeTotals_WithNoData_ReturnsZeros(): void {
		global $wpdb;
		$service = new Tables_Based_Grading_Stats_Service( $wpdb );

		$result = $service->get_grade_totals();

		$this->assertSame( 0, $result['count'], 'Count should be 0 when no data exists.' );
		$this->assertSame( 0.0, $result['sum'], 'Sum should be 0.0 when no data exists.' );
	}

	/**
	 * Test testGetGradeTotals_WithGradedLesson_ReturnsCountAndSum.
	 */
	public function testGetGradeTotals_WithGradedLesson_ReturnsCountAndSum(): void {
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create();
		$quiz_id   = $this->sensei_factory->quiz->create();

		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_id, $course_id, 'graded', 80 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals();

		$this->assertSame( 1, $result['count'], 'Count should be 1 for a single graded lesson.' );
		$this->assertSame( 80.0, $result['sum'], 'Sum should equal the single grade value.' );
	}

	/**
	 * Test testGetGradeTotals_WithUserIdFilter_ReturnsFilteredResults.
	 */
	public function testGetGradeTotals_WithUserIdFilter_ReturnsFilteredResults(): void {
		global $wpdb;
		$user_1    = $this->sensei_factory->user->create();
		$user_2    = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create();
		$quiz_id   = $this->sensei_factory->quiz->create();

		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_1, $course_id, 'graded', 80 );
		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_2, $course_id, 'graded', 60 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals( array( 'user_id' => $user_1 ) );

		$this->assertSame( 1, $result['count'], 'user_id filter should match exactly user 1.' );
		$this->assertSame( 80.0, $result['sum'], 'user_id filter should sum only user 1 grades.' );
	}

	/**
	 * Test testGetGradeTotals_WithLessonIdFilter_ReturnsFilteredResults.
	 */
	public function testGetGradeTotals_WithLessonIdFilter_ReturnsFilteredResults(): void {
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_1  = $this->sensei_factory->lesson->create();
		$quiz_1    = $this->sensei_factory->quiz->create();
		$lesson_2  = $this->sensei_factory->lesson->create();
		$quiz_2    = $this->sensei_factory->quiz->create();

		$this->create_graded_lesson( $lesson_1, $quiz_1, $user_id, $course_id, 'graded', 80 );
		$this->create_graded_lesson( $lesson_2, $quiz_2, $user_id, $course_id, 'graded', 60 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals( array( 'lesson_id' => $lesson_1 ) );

		$this->assertSame( 1, $result['count'], 'lesson_id filter should match exactly lesson 1.' );
		$this->assertSame( 80.0, $result['sum'], 'lesson_id filter should sum only lesson 1 grades.' );
	}

	/**
	 * Test testGetGradeTotals_WithPostInFilter_ReturnsFilteredResults.
	 */
	public function testGetGradeTotals_WithPostInFilter_ReturnsFilteredResults(): void {
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_1  = $this->sensei_factory->lesson->create();
		$quiz_1    = $this->sensei_factory->quiz->create();
		$lesson_2  = $this->sensei_factory->lesson->create();
		$quiz_2    = $this->sensei_factory->quiz->create();
		$lesson_3  = $this->sensei_factory->lesson->create();
		$quiz_3    = $this->sensei_factory->quiz->create();

		$this->create_graded_lesson( $lesson_1, $quiz_1, $user_id, $course_id, 'graded', 80 );
		$this->create_graded_lesson( $lesson_2, $quiz_2, $user_id, $course_id, 'graded', 60 );
		$this->create_graded_lesson( $lesson_3, $quiz_3, $user_id, $course_id, 'graded', 40 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals( array( 'post__in' => array( $lesson_1, $lesson_2 ) ) );

		$this->assertSame( 2, $result['count'], 'post__in filter should match the two specified lessons.' );
		$this->assertSame( 140.0, $result['sum'], 'post__in filter should sum only the specified lessons.' );
	}

	/**
	 * Test testGetGradeTotals_WithNullFinalGrade_ExcludesFromResults.
	 */
	public function testGetGradeTotals_WithNullFinalGrade_ExcludesFromResults(): void {
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create();
		$quiz_id   = $this->sensei_factory->quiz->create();

		// Lesson progress exists but quiz submission has no grade.
		update_post_meta( $lesson_id, '_lesson_course', $course_id );
		update_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'graded' );
		$this->insert_progress( $quiz_id, $user_id, 'quiz', 'graded' );
		$this->insert_quiz_submission( $quiz_id, $user_id, null );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals();

		$this->assertSame( 0, $result['count'] );
	}

	/**
	 * Test testGetGradeTotals_WithCombinedFilters_AppliesAllFilters.
	 */
	public function testGetGradeTotals_WithCombinedFilters_AppliesAllFilters(): void {
		global $wpdb;
		$user_1    = $this->sensei_factory->user->create();
		$user_2    = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_1  = $this->sensei_factory->lesson->create();
		$quiz_1    = $this->sensei_factory->quiz->create();
		$lesson_2  = $this->sensei_factory->lesson->create();
		$quiz_2    = $this->sensei_factory->quiz->create();

		// User 1: grade 80 on lesson 1, grade 70 on lesson 2.
		$this->create_graded_lesson( $lesson_1, $quiz_1, $user_1, $course_id, 'graded', 80 );
		$this->create_graded_lesson( $lesson_2, $quiz_2, $user_1, $course_id, 'graded', 70 );
		// User 2: grade 60 on lesson 1.
		$this->create_graded_lesson( $lesson_1, $quiz_1, $user_2, $course_id, 'graded', 60 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals(
			array(
				'user_id'   => $user_1,
				'lesson_id' => $lesson_1,
			)
		);

		// Only user 1's grade on lesson 1 should match.
		$this->assertSame( 1, $result['count'], 'Combined user_id and lesson_id filter should match exactly one row.' );
		$this->assertSame( 80.0, $result['sum'], 'Combined user_id and lesson_id filter should sum only user 1 on lesson 1.' );
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
		$lesson_id = $this->sensei_factory->lesson->create();
		$quiz_id   = $this->sensei_factory->quiz->create();

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
		$lesson_1 = $this->sensei_factory->lesson->create();
		$quiz_1   = $this->sensei_factory->quiz->create();
		$lesson_2 = $this->sensei_factory->lesson->create();
		$quiz_2   = $this->sensei_factory->quiz->create();

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
		$lesson_1 = $this->sensei_factory->lesson->create();
		$quiz_1   = $this->sensei_factory->quiz->create();
		$lesson_2 = $this->sensei_factory->lesson->create();
		$quiz_2   = $this->sensei_factory->quiz->create();
		$lesson_3 = $this->sensei_factory->lesson->create();
		$quiz_3   = $this->sensei_factory->quiz->create();

		// Course 1: grades 80, 60 -> avg 70.
		$this->create_graded_lesson( $lesson_1, $quiz_1, $user_id, $course_1, 'graded', 80 );
		$this->create_graded_lesson( $lesson_2, $quiz_2, $user_id, $course_1, 'passed', 60 );
		// Course 2: grade 90 -> avg 90.
		$this->create_graded_lesson( $lesson_3, $quiz_3, $user_id, $course_2, 'failed', 90 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_courses_average_grade();

		// Average of averages: (70 + 90) / 2 = 80.
		$this->assertSame( 80.0, $result );
	}

	/**
	 * Tests that get_grade_totals only includes graded, passed, and failed statuses.
	 */
	public function testGetGradeTotals_WithMixedStatuses_OnlyCountsGradedPassedFailed(): void {
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();

		// These should be included.
		$lesson_1 = $this->sensei_factory->lesson->create();
		$quiz_1   = $this->sensei_factory->quiz->create();
		$this->create_graded_lesson( $lesson_1, $quiz_1, $user_id, $course_id, 'graded', 80 );

		$lesson_2 = $this->sensei_factory->lesson->create();
		$quiz_2   = $this->sensei_factory->quiz->create();
		$this->create_graded_lesson( $lesson_2, $quiz_2, $user_id, $course_id, 'passed', 90 );

		$lesson_3 = $this->sensei_factory->lesson->create();
		$quiz_3   = $this->sensei_factory->quiz->create();
		$this->create_graded_lesson( $lesson_3, $quiz_3, $user_id, $course_id, 'failed', 40 );

		// This should be excluded: 'in-progress' status with a grade.
		$lesson_4 = $this->sensei_factory->lesson->create();
		$quiz_4   = $this->sensei_factory->quiz->create();
		update_post_meta( $lesson_4, '_lesson_course', $course_id );
		update_post_meta( $lesson_4, '_lesson_quiz', $quiz_4 );
		$this->insert_progress( $lesson_4, $user_id, 'lesson', 'in-progress' );
		$this->insert_progress( $quiz_4, $user_id, 'quiz', 'in-progress' );
		$this->insert_quiz_submission( $quiz_4, $user_id, 100 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals();

		$this->assertSame( 3, $result['count'], 'Only graded/passed/failed statuses should be counted.' );
		$this->assertSame( 210.0, $result['sum'], 'Sum should only include graded/passed/failed grades (80 + 90 + 40).' );
	}

	/**
	 * Tests that get_courses_average_grade excludes lessons without quizzes.
	 */
	public function testGetCoursesAverageGrade_WithLessonWithoutQuiz_ExcludesFromAverage(): void {
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();

		// Lesson with quiz.
		$lesson_1 = $this->sensei_factory->lesson->create();
		$quiz_1   = $this->sensei_factory->quiz->create();
		$this->create_graded_lesson( $lesson_1, $quiz_1, $user_id, $course_id, 'graded', 80 );

		// Lesson without quiz: only lesson progress, no quiz progress or submission.
		$lesson_2 = $this->sensei_factory->lesson->create();
		update_post_meta( $lesson_2, '_lesson_course', $course_id );
		$this->insert_progress( $lesson_2, $user_id, 'lesson', 'complete' );

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
		$lesson_id = $this->sensei_factory->lesson->create();
		$quiz_id   = $this->sensei_factory->quiz->create();

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
		$lesson_id = $this->sensei_factory->lesson->create();
		$quiz_id   = $this->sensei_factory->quiz->create();

		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_1, $course_id, 'graded', 80 );
		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_2, $course_id, 'graded', 60 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_users_average_grade( array( $user_1 ) );

		$this->assertSame( 80.0, $result );
	}

	/**
	 * Test testGetGradeTotalsByUser_WithNoUserIds_ReturnsEmptyArray.
	 */
	public function testGetGradeTotalsByUser_WithNoUserIds_ReturnsEmptyArray(): void {
		global $wpdb;
		$service = new Tables_Based_Grading_Stats_Service( $wpdb );

		$result = $service->get_grade_totals_by_user( array() );

		$this->assertSame( array(), $result );
	}

	/**
	 * Test testGetGradeTotalsByUser_WithMultipleGrades_GroupsByUser.
	 */
	public function testGetGradeTotalsByUser_WithMultipleGrades_GroupsByUser(): void {
		global $wpdb;
		$user      = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_1  = $this->sensei_factory->lesson->create();
		$quiz_1    = $this->sensei_factory->quiz->create();
		$lesson_2  = $this->sensei_factory->lesson->create();
		$quiz_2    = $this->sensei_factory->quiz->create();

		$this->create_graded_lesson( $lesson_1, $quiz_1, $user, $course_id, 'graded', 80 );
		$this->create_graded_lesson( $lesson_2, $quiz_2, $user, $course_id, 'graded', 60 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals_by_user( array( $user ) );

		$this->assertSame( 2, $result[ $user ]['count'] );
		$this->assertSame( 140.0, $result[ $user ]['sum'] );
	}

	/**
	 * Test testGetGradeTotalsByUser_WithMultipleUsers_ReturnsSeparateTotals.
	 */
	public function testGetGradeTotalsByUser_WithMultipleUsers_ReturnsSeparateTotals(): void {
		global $wpdb;
		$user_1    = $this->sensei_factory->user->create();
		$user_2    = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create();
		$quiz_id   = $this->sensei_factory->quiz->create();

		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_1, $course_id, 'graded', 80 );
		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_2, $course_id, 'graded', 60 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals_by_user( array( $user_1, $user_2 ) );

		$this->assertSame( 1, $result[ $user_1 ]['count'] );
		$this->assertSame( 80.0, $result[ $user_1 ]['sum'] );
		$this->assertSame( 1, $result[ $user_2 ]['count'] );
		$this->assertSame( 60.0, $result[ $user_2 ]['sum'] );
	}

	/**
	 * Test testGetGradeTotalsByUser_WithLessonWithoutSubmission_ExcludesFromTotals.
	 *
	 * Pins parity with the comments-based implementation: a lesson that was
	 * auto-passed (no quiz submission) must not be counted, mirroring how the
	 * comments-based implementation excludes lessons without quiz_answers.
	 */
	public function testGetGradeTotalsByUser_WithLessonWithoutSubmission_ExcludesFromTotals(): void {
		global $wpdb;
		$user      = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_1  = $this->sensei_factory->lesson->create();
		$quiz_1    = $this->sensei_factory->quiz->create();
		$lesson_2  = $this->sensei_factory->lesson->create();
		$quiz_2    = $this->sensei_factory->quiz->create();

		// Graded lesson with a quiz submission.
		$this->create_graded_lesson( $lesson_1, $quiz_1, $user, $course_id, 'graded', 80 );
		$this->create_graded_lesson( $lesson_2, $quiz_2, $user, $course_id, 'graded', 60 );

		// Auto-passed lesson: progress marked graded, but no quiz submission row.
		$lesson_3 = $this->sensei_factory->lesson->create();
		$quiz_3   = $this->sensei_factory->quiz->create();
		update_post_meta( $lesson_3, '_lesson_course', $course_id );
		update_post_meta( $lesson_3, '_lesson_quiz', $quiz_3 );
		$this->insert_progress( $lesson_3, $user, 'lesson', 'graded' );
		$this->insert_progress( $quiz_3, $user, 'quiz', 'graded' );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals_by_user( array( $user ) );

		$this->assertSame( 2, $result[ $user ]['count'], 'Auto-passed lesson without a submission should be excluded.' );
		$this->assertSame( 140.0, $result[ $user ]['sum'] );
	}

	/**
	 * Test testGetGradeTotalsByCourse_WithNoCourseIds_ReturnsEmptyArray.
	 */
	public function testGetGradeTotalsByCourse_WithNoCourseIds_ReturnsEmptyArray(): void {
		global $wpdb;
		$service = new Tables_Based_Grading_Stats_Service( $wpdb );

		$result = $service->get_grade_totals_by_course( array() );

		$this->assertSame( array(), $result );
	}

	/**
	 * Test testGetGradeTotalsByCourse_WithMultipleStudents_GroupsByCourse.
	 */
	public function testGetGradeTotalsByCourse_WithMultipleStudents_GroupsByCourse(): void {
		global $wpdb;
		$user_1    = $this->sensei_factory->user->create();
		$user_2    = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create();
		$quiz_id   = $this->sensei_factory->quiz->create();

		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_1, $course_id, 'graded', 80 );
		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_2, $course_id, 'passed', 60 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals_by_course( array( $course_id ) );

		$this->assertSame( 2, $result[ $course_id ]['count'] );
		$this->assertSame( 140.0, $result[ $course_id ]['sum'] );
	}

	/**
	 * Test testGetGradeTotalsByCourse_WithMultipleCourses_ReturnsSeparateTotals.
	 */
	public function testGetGradeTotalsByCourse_WithMultipleCourses_ReturnsSeparateTotals(): void {
		global $wpdb;
		$user_id  = $this->sensei_factory->user->create();
		$course_1 = $this->sensei_factory->course->create();
		$course_2 = $this->sensei_factory->course->create();
		$lesson_1 = $this->sensei_factory->lesson->create();
		$quiz_1   = $this->sensei_factory->quiz->create();
		$lesson_2 = $this->sensei_factory->lesson->create();
		$quiz_2   = $this->sensei_factory->quiz->create();

		$this->create_graded_lesson( $lesson_1, $quiz_1, $user_id, $course_1, 'graded', 80 );
		$this->create_graded_lesson( $lesson_2, $quiz_2, $user_id, $course_2, 'failed', 90 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals_by_course( array( $course_1, $course_2 ) );

		$this->assertSame( 1, $result[ $course_1 ]['count'] );
		$this->assertSame( 80.0, $result[ $course_1 ]['sum'] );
		$this->assertSame( 1, $result[ $course_2 ]['count'] );
		$this->assertSame( 90.0, $result[ $course_2 ]['sum'] );
	}

	/**
	 * Test testGetGradeTotalsByCourse_WithCourseIdsFilter_ExcludesUnrequestedCourses.
	 */
	public function testGetGradeTotalsByCourse_WithCourseIdsFilter_ExcludesUnrequestedCourses(): void {
		global $wpdb;
		$user_id  = $this->sensei_factory->user->create();
		$course_1 = $this->sensei_factory->course->create();
		$course_2 = $this->sensei_factory->course->create();
		$lesson_1 = $this->sensei_factory->lesson->create();
		$quiz_1   = $this->sensei_factory->quiz->create();
		$lesson_2 = $this->sensei_factory->lesson->create();
		$quiz_2   = $this->sensei_factory->quiz->create();

		$this->create_graded_lesson( $lesson_1, $quiz_1, $user_id, $course_1, 'graded', 80 );
		$this->create_graded_lesson( $lesson_2, $quiz_2, $user_id, $course_2, 'graded', 60 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals_by_course( array( $course_1 ) );

		$this->assertArrayHasKey( $course_1, $result );
		$this->assertArrayNotHasKey( $course_2, $result, 'Only the requested course should be present in the result.' );
	}

	/**
	 * Test testGetGradeTotalsByCourse_WithLessonWithoutSubmission_ExcludesFromTotals.
	 *
	 * Pins parity with the comments-based implementation: a lesson that was
	 * auto-passed (no quiz submission) must not be counted, mirroring how the
	 * comments-based implementation excludes lessons without quiz_answers.
	 */
	public function testGetGradeTotalsByCourse_WithLessonWithoutSubmission_ExcludesFromTotals(): void {
		global $wpdb;
		$user      = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_1  = $this->sensei_factory->lesson->create();
		$quiz_1    = $this->sensei_factory->quiz->create();

		// Graded lesson with a quiz submission.
		$this->create_graded_lesson( $lesson_1, $quiz_1, $user, $course_id, 'graded', 80 );

		// Auto-passed lesson: progress marked graded, but no quiz submission row.
		$lesson_2 = $this->sensei_factory->lesson->create();
		$quiz_2   = $this->sensei_factory->quiz->create();
		update_post_meta( $lesson_2, '_lesson_course', $course_id );
		update_post_meta( $lesson_2, '_lesson_quiz', $quiz_2 );
		$this->insert_progress( $lesson_2, $user, 'lesson', 'graded' );
		$this->insert_progress( $quiz_2, $user, 'quiz', 'graded' );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals_by_course( array( $course_id ) );

		$this->assertSame( 1, $result[ $course_id ]['count'], 'Auto-passed lesson without a submission should be excluded.' );
		$this->assertSame( 80.0, $result[ $course_id ]['sum'] );
	}
}

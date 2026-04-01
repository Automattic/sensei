<?php

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Tables_Based_Grading_Stats_Service;

/**
 * Class Tables_Based_Grading_Stats_Service_Test.
 *
 * @covers \Sensei\Internal\Services\Tables_Based_Grading_Stats_Service
 */
class Tables_Based_Grading_Stats_Service_Test extends \WP_UnitTestCase {

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

	public function testGetGradeTotals_WithNoData_ReturnsZeros(): void {
		global $wpdb;
		$service = new Tables_Based_Grading_Stats_Service( $wpdb );

		$result = $service->get_grade_totals();

		$this->assertSame( 0, $result['count'] );
		$this->assertSame( 0.0, $result['sum'] );
	}

	public function testGetGradeTotals_WithGradedLesson_ReturnsCountAndSum(): void {
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$quiz_id   = $this->sensei_factory->quiz->create(
			[ 'post_parent' => $lesson_id ]
		);

		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_id, $course_id, 'graded', 80 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals();

		$this->assertSame( 1, $result['count'] );
		$this->assertSame( 80.0, $result['sum'] );
	}

	public function testGetGradeTotals_WithUserIdFilter_ReturnsFilteredResults(): void {
		global $wpdb;
		$user_1    = $this->sensei_factory->user->create();
		$user_2    = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$quiz_id   = $this->sensei_factory->quiz->create(
			[ 'post_parent' => $lesson_id ]
		);

		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_1, $course_id, 'graded', 80 );
		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_2, $course_id, 'graded', 60 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals( [ 'user_id' => $user_1 ] );

		$this->assertSame( 1, $result['count'] );
		$this->assertSame( 80.0, $result['sum'] );
	}

	public function testGetGradeTotals_WithLessonIdFilter_ReturnsFilteredResults(): void {
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_1  = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$quiz_1    = $this->sensei_factory->quiz->create(
			[ 'post_parent' => $lesson_1 ]
		);
		$lesson_2  = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$quiz_2    = $this->sensei_factory->quiz->create(
			[ 'post_parent' => $lesson_2 ]
		);

		$this->create_graded_lesson( $lesson_1, $quiz_1, $user_id, $course_id, 'graded', 80 );
		$this->create_graded_lesson( $lesson_2, $quiz_2, $user_id, $course_id, 'graded', 60 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals( [ 'lesson_id' => $lesson_1 ] );

		$this->assertSame( 1, $result['count'] );
		$this->assertSame( 80.0, $result['sum'] );
	}

	public function testGetGradeTotals_WithPostInFilter_ReturnsFilteredResults(): void {
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_1  = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$quiz_1    = $this->sensei_factory->quiz->create(
			[ 'post_parent' => $lesson_1 ]
		);
		$lesson_2  = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$quiz_2    = $this->sensei_factory->quiz->create(
			[ 'post_parent' => $lesson_2 ]
		);
		$lesson_3  = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$quiz_3    = $this->sensei_factory->quiz->create(
			[ 'post_parent' => $lesson_3 ]
		);

		$this->create_graded_lesson( $lesson_1, $quiz_1, $user_id, $course_id, 'graded', 80 );
		$this->create_graded_lesson( $lesson_2, $quiz_2, $user_id, $course_id, 'graded', 60 );
		$this->create_graded_lesson( $lesson_3, $quiz_3, $user_id, $course_id, 'graded', 40 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals( [ 'post__in' => [ $lesson_1, $lesson_2 ] ] );

		$this->assertSame( 2, $result['count'] );
		$this->assertSame( 140.0, $result['sum'] );
	}

	public function testGetGradeTotals_WithNullFinalGrade_ExcludesFromResults(): void {
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$quiz_id   = $this->sensei_factory->quiz->create(
			[ 'post_parent' => $lesson_id ]
		);

		// Lesson progress exists but quiz submission has no grade.
		$this->insert_progress( $lesson_id, $user_id, 'lesson', 'graded', $course_id );
		$this->insert_progress( $quiz_id, $user_id, 'quiz', 'graded', $lesson_id );
		$this->insert_quiz_submission( $quiz_id, $user_id, null );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals();

		$this->assertSame( 0, $result['count'] );
	}

	public function testGetCoursesAverageGrade_WithNoData_ReturnsZero(): void {
		global $wpdb;
		$service = new Tables_Based_Grading_Stats_Service( $wpdb );

		$result = $service->get_courses_average_grade();

		$this->assertSame( 0.0, $result );
	}

	public function testGetCoursesAverageGrade_WithGradedLessons_ReturnsAverage(): void {
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$quiz_id   = $this->sensei_factory->quiz->create(
			[ 'post_parent' => $lesson_id ]
		);

		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_id, $course_id, 'graded', 80 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_courses_average_grade();

		$this->assertSame( 80.0, $result );
	}

	public function testGetCoursesAverageGrade_WithCourseIdsFilter_ReturnsFilteredAverage(): void {
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_1  = $this->sensei_factory->course->create();
		$course_2  = $this->sensei_factory->course->create();
		$lesson_1  = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_1 ] ]
		);
		$quiz_1    = $this->sensei_factory->quiz->create(
			[ 'post_parent' => $lesson_1 ]
		);
		$lesson_2  = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_2 ] ]
		);
		$quiz_2    = $this->sensei_factory->quiz->create(
			[ 'post_parent' => $lesson_2 ]
		);

		$this->create_graded_lesson( $lesson_1, $quiz_1, $user_id, $course_1, 'graded', 80 );
		$this->create_graded_lesson( $lesson_2, $quiz_2, $user_id, $course_2, 'graded', 60 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_courses_average_grade( [ $course_1 ] );

		$this->assertSame( 80.0, $result );
	}

	public function testGetCoursesAverageGrade_WithMultipleCourses_ReturnsAverageOfAverages(): void {
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$course_1  = $this->sensei_factory->course->create();
		$course_2  = $this->sensei_factory->course->create();
		$lesson_1  = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_1 ] ]
		);
		$quiz_1    = $this->sensei_factory->quiz->create(
			[ 'post_parent' => $lesson_1 ]
		);
		$lesson_2  = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_2 ] ]
		);
		$quiz_2    = $this->sensei_factory->quiz->create(
			[ 'post_parent' => $lesson_2 ]
		);

		// Course 1: grade 80, Course 2: grade 60. Average of averages = (80 + 60) / 2 = 70.
		$this->create_graded_lesson( $lesson_1, $quiz_1, $user_id, $course_1, 'graded', 80 );
		$this->create_graded_lesson( $lesson_2, $quiz_2, $user_id, $course_2, 'graded', 60 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_courses_average_grade();

		$this->assertSame( 70.0, $result );
	}

	public function testGetUsersAverageGrade_WithNoUserIds_ReturnsZero(): void {
		global $wpdb;
		$service = new Tables_Based_Grading_Stats_Service( $wpdb );

		$result = $service->get_users_average_grade( [] );

		$this->assertSame( 0.0, $result );
	}

	public function testGetUsersAverageGrade_WithUserIds_ReturnsAverage(): void {
		global $wpdb;
		$user_1    = $this->sensei_factory->user->create();
		$user_2    = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$quiz_id   = $this->sensei_factory->quiz->create(
			[ 'post_parent' => $lesson_id ]
		);

		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_1, $course_id, 'graded', 80 );
		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_2, $course_id, 'graded', 60 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_users_average_grade( [ $user_1, $user_2 ] );

		$this->assertSame( 70.0, $result );
	}

	public function testGetUsersAverageGrade_FilteredBySpecificUser_ReturnsUserAverage(): void {
		global $wpdb;
		$user_1    = $this->sensei_factory->user->create();
		$user_2    = $this->sensei_factory->user->create();
		$course_id = $this->sensei_factory->course->create();
		$lesson_id = $this->sensei_factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$quiz_id   = $this->sensei_factory->quiz->create(
			[ 'post_parent' => $lesson_id ]
		);

		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_1, $course_id, 'graded', 80 );
		$this->create_graded_lesson( $lesson_id, $quiz_id, $user_2, $course_id, 'graded', 60 );

		$service = new Tables_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_users_average_grade( [ $user_1 ] );

		$this->assertSame( 80.0, $result );
	}
}

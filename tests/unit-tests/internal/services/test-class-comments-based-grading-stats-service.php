<?php
/**
 * Tests for grading stats service.
 *
 * @package sensei-tests
 */

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Comments_Based_Grading_Stats_Service;

/**
 * Class Comments_Based_Grading_Stats_Service_Test.
 *
 * @covers \Sensei\Internal\Services\Comments_Based_Grading_Stats_Service
 */
class Comments_Based_Grading_Stats_Service_Test extends \WP_UnitTestCase {

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
	 * Helper: create a lesson status comment with a grade.
	 *
	 * @param int    $lesson_id Lesson post ID.
	 * @param int    $user_id   User ID.
	 * @param string $status    Comment status (e.g. 'graded', 'passed', 'failed').
	 * @param int    $grade     The grade value.
	 */
	private function create_lesson_status_with_grade( int $lesson_id, int $user_id, string $status, int $grade ): void {
		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'  => $lesson_id,
				'user_id'          => $user_id,
				'comment_type'     => 'sensei_lesson_status',
				'comment_approved' => $status,
				'comment_content'  => '',
			)
		);
		update_comment_meta( $comment_id, 'grade', $grade );
	}

	/**
	 * Test testGetGradeTotals_WithNoData_ReturnsZeros.
	 */
	public function testGetGradeTotals_WithNoData_ReturnsZeros(): void {
		global $wpdb;
		$service = new Comments_Based_Grading_Stats_Service( $wpdb );

		$result = $service->get_grade_totals();

		$this->assertSame( 0, $result['count'] );
		$this->assertSame( 0.0, $result['sum'] );
	}

	/**
	 * Test testGetGradeTotals_WithGradedLessons_ReturnsCountAndSum.
	 */
	public function testGetGradeTotals_WithGradedLessons_ReturnsCountAndSum(): void {
		global $wpdb;
		$user_id   = $this->sensei_factory->user->create();
		$lesson_id = $this->sensei_factory->lesson->create();

		$this->create_lesson_status_with_grade( $lesson_id, $user_id, 'graded', 80 );

		$service = new Comments_Based_Grading_Stats_Service( $wpdb );
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
		$lesson_id = $this->sensei_factory->lesson->create();

		$this->create_lesson_status_with_grade( $lesson_id, $user_1, 'graded', 80 );
		$this->create_lesson_status_with_grade( $lesson_id, $user_2, 'graded', 60 );

		$service = new Comments_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals( array( 'user_id' => $user_1 ) );

		$this->assertSame( 1, $result['count'] );
		$this->assertSame( 80.0, $result['sum'] );
	}

	/**
	 * Test testGetGradeTotals_WithLessonIdFilter_ReturnsFilteredResults.
	 */
	public function testGetGradeTotals_WithLessonIdFilter_ReturnsFilteredResults(): void {
		global $wpdb;
		$user_id  = $this->sensei_factory->user->create();
		$lesson_1 = $this->sensei_factory->lesson->create();
		$lesson_2 = $this->sensei_factory->lesson->create();

		$this->create_lesson_status_with_grade( $lesson_1, $user_id, 'graded', 80 );
		$this->create_lesson_status_with_grade( $lesson_2, $user_id, 'graded', 60 );

		$service = new Comments_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals( array( 'lesson_id' => $lesson_1 ) );

		$this->assertSame( 1, $result['count'] );
		$this->assertSame( 80.0, $result['sum'] );
	}

	/**
	 * Test testGetGradeTotals_WithPostInFilter_ReturnsFilteredResults.
	 */
	public function testGetGradeTotals_WithPostInFilter_ReturnsFilteredResults(): void {
		global $wpdb;
		$user_id  = $this->sensei_factory->user->create();
		$lesson_1 = $this->sensei_factory->lesson->create();
		$lesson_2 = $this->sensei_factory->lesson->create();
		$lesson_3 = $this->sensei_factory->lesson->create();

		$this->create_lesson_status_with_grade( $lesson_1, $user_id, 'graded', 80 );
		$this->create_lesson_status_with_grade( $lesson_2, $user_id, 'graded', 60 );
		$this->create_lesson_status_with_grade( $lesson_3, $user_id, 'graded', 40 );

		$service = new Comments_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals( array( 'post__in' => array( $lesson_1, $lesson_2 ) ) );

		$this->assertSame( 2, $result['count'] );
		$this->assertSame( 140.0, $result['sum'] );
	}

	/**
	 * Test testGetCoursesAverageGrade_WithNoData_ReturnsZero.
	 */
	public function testGetCoursesAverageGrade_WithNoData_ReturnsZero(): void {
		global $wpdb;
		$service = new Comments_Based_Grading_Stats_Service( $wpdb );

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
			array(
				'meta_input' => array(
					'_lesson_course'      => $course_id,
					'_quiz_has_questions' => 1,
				),
			)
		);

		$this->create_lesson_status_with_grade( $lesson_id, $user_id, 'graded', 80 );

		$service = new Comments_Based_Grading_Stats_Service( $wpdb );
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
			array(
				'meta_input' => array(
					'_lesson_course'      => $course_1,
					'_quiz_has_questions' => 1,
				),
			)
		);
		$lesson_2 = $this->sensei_factory->lesson->create(
			array(
				'meta_input' => array(
					'_lesson_course'      => $course_2,
					'_quiz_has_questions' => 1,
				),
			)
		);

		$this->create_lesson_status_with_grade( $lesson_1, $user_id, 'graded', 80 );
		$this->create_lesson_status_with_grade( $lesson_2, $user_id, 'graded', 60 );

		$service = new Comments_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_courses_average_grade( array( $course_1 ) );

		$this->assertSame( 80.0, $result );
	}

	/**
	 * Tests that get_grade_totals only includes graded, passed, and failed statuses.
	 */
	public function testGetGradeTotals_WithMixedStatuses_OnlyCountsGradedPassedFailed(): void {
		global $wpdb;
		$user_id = $this->sensei_factory->user->create();

		// These should be included.
		$lesson_1 = $this->sensei_factory->lesson->create();
		$lesson_2 = $this->sensei_factory->lesson->create();
		$lesson_3 = $this->sensei_factory->lesson->create();
		$this->create_lesson_status_with_grade( $lesson_1, $user_id, 'graded', 80 );
		$this->create_lesson_status_with_grade( $lesson_2, $user_id, 'passed', 90 );
		$this->create_lesson_status_with_grade( $lesson_3, $user_id, 'failed', 40 );

		// This should be excluded: 'complete' status with grade meta.
		$lesson_4   = $this->sensei_factory->lesson->create();
		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'  => $lesson_4,
				'user_id'          => $user_id,
				'comment_type'     => 'sensei_lesson_status',
				'comment_approved' => 'complete',
				'comment_content'  => '',
			)
		);
		update_comment_meta( $comment_id, 'grade', 100 );

		$service = new Comments_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals();

		$this->assertSame( 3, $result['count'] );
		$this->assertSame( 210.0, $result['sum'] ); // 80 + 90 + 40.
	}

	/**
	 * Tests that get_courses_average_grade returns the average of per-course averages.
	 */
	public function testGetCoursesAverageGrade_WithMultipleCourses_ReturnsAverageOfAverages(): void {
		global $wpdb;
		$user_id  = $this->sensei_factory->user->create();
		$course_1 = $this->sensei_factory->course->create();
		$course_2 = $this->sensei_factory->course->create();
		$lesson_1 = $this->sensei_factory->lesson->create(
			array(
				'meta_input' => array(
					'_lesson_course'      => $course_1,
					'_quiz_has_questions' => 1,
				),
			)
		);
		$lesson_2 = $this->sensei_factory->lesson->create(
			array(
				'meta_input' => array(
					'_lesson_course'      => $course_1,
					'_quiz_has_questions' => 1,
				),
			)
		);
		$lesson_3 = $this->sensei_factory->lesson->create(
			array(
				'meta_input' => array(
					'_lesson_course'      => $course_2,
					'_quiz_has_questions' => 1,
				),
			)
		);

		// Course 1: grades 80, 60 -> avg 70.
		$this->create_lesson_status_with_grade( $lesson_1, $user_id, 'graded', 80 );
		$this->create_lesson_status_with_grade( $lesson_2, $user_id, 'passed', 60 );
		// Course 2: grade 90 -> avg 90.
		$this->create_lesson_status_with_grade( $lesson_3, $user_id, 'failed', 90 );

		$service = new Comments_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_courses_average_grade();

		// Average of averages: (70 + 90) / 2 = 80.
		$this->assertSame( 80.0, $result );
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
			array(
				'meta_input' => array(
					'_lesson_course'      => $course_id,
					'_quiz_has_questions' => 1,
				),
			)
		);
		$this->create_lesson_status_with_grade( $lesson_1, $user_id, 'graded', 80 );

		// Lesson without quiz (no _quiz_has_questions meta).
		$lesson_2 = $this->sensei_factory->lesson->create(
			array(
				'meta_input' => array(
					'_lesson_course' => $course_id,
				),
			)
		);
		$this->create_lesson_status_with_grade( $lesson_2, $user_id, 'graded', 40 );

		$service = new Comments_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_courses_average_grade();

		// Only the lesson with quiz should be included.
		$this->assertSame( 80.0, $result );
	}

	/**
	 * Test testGetUsersAverageGrade_WithNoUserIds_ReturnsZero.
	 */
	public function testGetUsersAverageGrade_WithNoUserIds_ReturnsZero(): void {
		global $wpdb;
		$service = new Comments_Based_Grading_Stats_Service( $wpdb );

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
		$lesson_id = $this->sensei_factory->lesson->create();

		$this->create_lesson_status_with_grade( $lesson_id, $user_1, 'graded', 80 );
		$this->create_lesson_status_with_grade( $lesson_id, $user_2, 'graded', 60 );

		$service = new Comments_Based_Grading_Stats_Service( $wpdb );
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
		$lesson_id = $this->sensei_factory->lesson->create();

		$this->create_lesson_status_with_grade( $lesson_id, $user_1, 'graded', 80 );
		$this->create_lesson_status_with_grade( $lesson_id, $user_2, 'graded', 60 );

		$service = new Comments_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_users_average_grade( array( $user_1 ) );

		$this->assertSame( 80.0, $result );
	}
}

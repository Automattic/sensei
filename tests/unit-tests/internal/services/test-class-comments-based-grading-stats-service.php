<?php

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Comments_Based_Grading_Stats_Service;

/**
 * Class Comments_Based_Grading_Stats_Service_Test.
 *
 * @covers \Sensei\Internal\Services\Comments_Based_Grading_Stats_Service
 */
class Comments_Based_Grading_Stats_Service_Test extends \WP_UnitTestCase {

	private $sensei_factory;

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
			[
				'comment_post_ID'  => $lesson_id,
				'user_id'          => $user_id,
				'comment_type'     => 'sensei_lesson_status',
				'comment_approved' => $status,
				'comment_content'  => '',
			]
		);
		update_comment_meta( $comment_id, 'grade', $grade );
	}

	public function testGetGradeTotals_WithNoData_ReturnsZeros(): void {
		global $wpdb;
		$service = new Comments_Based_Grading_Stats_Service( $wpdb );

		$result = $service->get_grade_totals();

		$this->assertSame( 0, $result['count'] );
		$this->assertSame( 0.0, $result['sum'] );
	}

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

	public function testGetGradeTotals_WithUserIdFilter_ReturnsFilteredResults(): void {
		global $wpdb;
		$user_1    = $this->sensei_factory->user->create();
		$user_2    = $this->sensei_factory->user->create();
		$lesson_id = $this->sensei_factory->lesson->create();

		$this->create_lesson_status_with_grade( $lesson_id, $user_1, 'graded', 80 );
		$this->create_lesson_status_with_grade( $lesson_id, $user_2, 'graded', 60 );

		$service = new Comments_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals( [ 'user_id' => $user_1 ] );

		$this->assertSame( 1, $result['count'] );
		$this->assertSame( 80.0, $result['sum'] );
	}

	public function testGetGradeTotals_WithLessonIdFilter_ReturnsFilteredResults(): void {
		global $wpdb;
		$user_id    = $this->sensei_factory->user->create();
		$lesson_1   = $this->sensei_factory->lesson->create();
		$lesson_2   = $this->sensei_factory->lesson->create();

		$this->create_lesson_status_with_grade( $lesson_1, $user_id, 'graded', 80 );
		$this->create_lesson_status_with_grade( $lesson_2, $user_id, 'graded', 60 );

		$service = new Comments_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals( [ 'lesson_id' => $lesson_1 ] );

		$this->assertSame( 1, $result['count'] );
		$this->assertSame( 80.0, $result['sum'] );
	}

	public function testGetGradeTotals_WithPostInFilter_ReturnsFilteredResults(): void {
		global $wpdb;
		$user_id    = $this->sensei_factory->user->create();
		$lesson_1   = $this->sensei_factory->lesson->create();
		$lesson_2   = $this->sensei_factory->lesson->create();
		$lesson_3   = $this->sensei_factory->lesson->create();

		$this->create_lesson_status_with_grade( $lesson_1, $user_id, 'graded', 80 );
		$this->create_lesson_status_with_grade( $lesson_2, $user_id, 'graded', 60 );
		$this->create_lesson_status_with_grade( $lesson_3, $user_id, 'graded', 40 );

		$service = new Comments_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_grade_totals( [ 'post__in' => [ $lesson_1, $lesson_2 ] ] );

		$this->assertSame( 2, $result['count'] );
		$this->assertSame( 140.0, $result['sum'] );
	}

	public function testGetCoursesAverageGrade_WithNoData_ReturnsZero(): void {
		global $wpdb;
		$service = new Comments_Based_Grading_Stats_Service( $wpdb );

		$result = $service->get_courses_average_grade();

		$this->assertSame( 0.0, $result );
	}

	public function testGetCoursesAverageGrade_WithGradedLessons_ReturnsAverage(): void {
		global $wpdb;
		$user_id    = $this->sensei_factory->user->create();
		$course_id  = $this->sensei_factory->course->create();
		$lesson_id  = $this->sensei_factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course'      => $course_id,
					'_quiz_has_questions' => 1,
				],
			]
		);

		$this->create_lesson_status_with_grade( $lesson_id, $user_id, 'graded', 80 );

		$service = new Comments_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_courses_average_grade();

		$this->assertSame( 80.0, $result );
	}

	public function testGetCoursesAverageGrade_WithCourseIdsFilter_ReturnsFilteredAverage(): void {
		global $wpdb;
		$user_id     = $this->sensei_factory->user->create();
		$course_1    = $this->sensei_factory->course->create();
		$course_2    = $this->sensei_factory->course->create();
		$lesson_1    = $this->sensei_factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course'      => $course_1,
					'_quiz_has_questions' => 1,
				],
			]
		);
		$lesson_2    = $this->sensei_factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course'      => $course_2,
					'_quiz_has_questions' => 1,
				],
			]
		);

		$this->create_lesson_status_with_grade( $lesson_1, $user_id, 'graded', 80 );
		$this->create_lesson_status_with_grade( $lesson_2, $user_id, 'graded', 60 );

		$service = new Comments_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_courses_average_grade( [ $course_1 ] );

		$this->assertSame( 80.0, $result );
	}

	public function testGetUsersAverageGrade_WithNoUserIds_ReturnsZero(): void {
		global $wpdb;
		$service = new Comments_Based_Grading_Stats_Service( $wpdb );

		$result = $service->get_users_average_grade( [] );

		$this->assertSame( 0.0, $result );
	}

	public function testGetUsersAverageGrade_WithUserIds_ReturnsAverage(): void {
		global $wpdb;
		$user_1     = $this->sensei_factory->user->create();
		$user_2     = $this->sensei_factory->user->create();
		$lesson_id  = $this->sensei_factory->lesson->create();

		$this->create_lesson_status_with_grade( $lesson_id, $user_1, 'graded', 80 );
		$this->create_lesson_status_with_grade( $lesson_id, $user_2, 'graded', 60 );

		$service = new Comments_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_users_average_grade( [ $user_1, $user_2 ] );

		$this->assertSame( 70.0, $result );
	}

	public function testGetUsersAverageGrade_FilteredBySpecificUser_ReturnsUserAverage(): void {
		global $wpdb;
		$user_1     = $this->sensei_factory->user->create();
		$user_2     = $this->sensei_factory->user->create();
		$lesson_id  = $this->sensei_factory->lesson->create();

		$this->create_lesson_status_with_grade( $lesson_id, $user_1, 'graded', 80 );
		$this->create_lesson_status_with_grade( $lesson_id, $user_2, 'graded', 60 );

		$service = new Comments_Based_Grading_Stats_Service( $wpdb );
		$result  = $service->get_users_average_grade( [ $user_1 ] );

		$this->assertSame( 80.0, $result );
	}
}

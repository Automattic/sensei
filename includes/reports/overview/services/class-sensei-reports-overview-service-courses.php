<?php
/**
 * File containing the Sensei_Reports_Overview_Service_Courses class.
 *
 * @package sensei
 */

use Sensei\Internal\Services\Grading_Stats_Service_Interface;
use Sensei\Internal\Services\Progress_Aggregation_Service_Interface;
use Sensei\Internal\Services\Progress_Query_Service_Factory;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Courses overview service class.
 *
 * @since 4.4.1
 */
class Sensei_Reports_Overview_Service_Courses {

	/**
	 * The progress aggregation service.
	 *
	 * @var Progress_Aggregation_Service_Interface
	 */
	private Progress_Aggregation_Service_Interface $aggregation_service;

	/**
	 * The grading stats service.
	 *
	 * @var Grading_Stats_Service_Interface
	 */
	private Grading_Stats_Service_Interface $grading_stats_service;

	/**
	 * Constructor.
	 *
	 * @param Progress_Aggregation_Service_Interface|null $aggregation_service The progress aggregation service.
	 * @param Grading_Stats_Service_Interface|null        $grading_stats_service The grading stats service.
	 */
	public function __construct( ?Progress_Aggregation_Service_Interface $aggregation_service = null, ?Grading_Stats_Service_Interface $grading_stats_service = null ) {
		$this->aggregation_service   = $aggregation_service ?? ( new Progress_Query_Service_Factory() )->create_aggregation_service();
		$this->grading_stats_service = $grading_stats_service ?? ( new Progress_Query_Service_Factory() )->create_grading_stats_service();
	}

	/**
	 * Get total average progress value for courses.
	 *
	 * @since  4.4.1
	 * @access public
	 *
	 * @param array $course_ids Courses ids.
	 * @return float total average progress value for all the courses.
	 */
	public function get_total_average_progress( array $course_ids ): float {
		if ( empty( $course_ids ) ) {
			return 0.0;
		}

		$progress_by_course = $this->get_average_progress_per_course( $course_ids );

		if ( empty( $progress_by_course ) ) {
			return 0.0;
		}

		$total_average_progress = array_sum( $progress_by_course );

		// Divide total value to get average total value for average progress for courses.
		return ceil( $total_average_progress / count( $course_ids ) );
	}

	/**
	 * Average progress percentage per course.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $course_ids Course IDs.
	 * @return array<int, float> Map of course_id => average progress percent (0-100).
	 */
	public function get_average_progress_per_course( array $course_ids ): array {
		$progress_by_course = array();

		if ( empty( $course_ids ) ) {
			return $progress_by_course;
		}

		$lessons_count_per_courses = $this->get_lessons_in_courses( $course_ids );

		$all_lesson_ids = array();
		foreach ( $lessons_count_per_courses as $course_lessons ) {
			$all_lesson_ids = array_merge( $all_lesson_ids, array_map( 'intval', explode( ',', $course_lessons->lessons ) ) );
		}
		$all_lesson_ids = array_unique( $all_lesson_ids );

		$lessons_completions       = $this->get_lessons_completions( $all_lesson_ids );
		$student_count_per_courses = $this->get_students_count_in_courses( $course_ids );

		foreach ( $course_ids as $course_id ) {
			if ( ! isset( $lessons_count_per_courses[ $course_id ] ) || ! isset( $student_count_per_courses[ $course_id ] ) ) {
				continue;
			}
			// Get lessons in the course.
			$lessons = $lessons_count_per_courses[ $course_id ]->lessons;
			$lessons = array_map( 'intval', explode( ',', $lessons ) );
			if ( empty( $lessons ) ) {
				continue;
			}

			// Get students count.
			$students_count = $student_count_per_courses[ $course_id ]->students_count;
			if ( ! $students_count ) {
				continue;
			}

			// Get all completed lessons for all the students.
			$completed_count = array_reduce(
				$lessons,
				function ( $carry, $lesson ) use ( $lessons_completions ) {
					if ( ! isset( $lessons_completions[ $lesson ] ) ) {
						return $carry;
					}
					$carry += $lessons_completions[ $lesson ]->completion_count;
					return $carry;
				},
				0
			);

			// Calculate average progress for a course.
			$progress_by_course[ $course_id ] = $completed_count / ( $students_count * count( $lessons ) ) * 100;
		}

		return $progress_by_course;
	}

	/**
	 * Get the average grade of the courses.
	 *
	 * @since 4.4.1
	 * @access public
	 *
	 * @param array $course_ids Courses ids to filter by.
	 * @return double Average grade of all courses.
	 */
	public function get_courses_average_grade( array $course_ids ) {
		if ( empty( $course_ids ) ) {
			return 0;
		}

		return $this->grading_stats_service->get_courses_average_grade( $course_ids );
	}

	/**
	 * Get average days to completion by courses.
	 *
	 * @since 4.4.1
	 * @access public
	 *
	 * @param array $course_ids Courses ids to filter by.
	 * @return float Average days to completion, rounded to the highest integer.
	 */
	public function get_average_days_to_completion( array $course_ids ): float {
		if ( empty( $course_ids ) ) {
			return 0;
		}

		return $this->aggregation_service->get_courses_average_days_to_completion( $course_ids );
	}


	/**
	 * Get total of enrollments
	 *
	 * @since  4.15.1
	 * @param array $course_ids Courses ids to filter by.
	 *
	 * @return int total of enrollments
	 */
	public function get_total_enrollments( $course_ids ): int {
		if ( empty( $course_ids ) ) {
			return 0;
		}
		$total_grouped_by_course = $this->get_students_count_in_courses( $course_ids );

		if ( empty( $total_grouped_by_course ) ) {
			return 0;
		}

		$to_total = function ( $acc, $current ) {
			return $acc + $current->students_count;
		};

		return array_reduce( $total_grouped_by_course, $to_total, 0 );
	}

	/**
	 * Get lessons completions.
	 *
	 * @since  4.4.1
	 *
	 * @param array $lesson_ids The list of lesson ids to get completions for.
	 * @return array lessons completions.
	 */
	private function get_lessons_completions( array $lesson_ids ): array {
		if ( empty( $lesson_ids ) ) {
			return array();
		}

		$counts = $this->aggregation_service->get_lesson_completion_counts( $lesson_ids );

		$result = array();
		foreach ( $counts as $lesson_id => $completion_count ) {
			$result[ $lesson_id ] = (object) array(
				'lesson_id'        => $lesson_id,
				'completion_count' => $completion_count,
			);
		}

		return $result;
	}

	/**
	 * Get lessons grouped by courses.
	 *
	 * @since  4.4.1
	 *
	 * @param array $course_ids The list of courses ids.
	 * @return array lessons count in courses.
	 */
	private function get_lessons_in_courses( $course_ids ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe direct sql.
		return $wpdb->get_results(
			"SELECT pm.meta_value as course_id, GROUP_CONCAT(pm.post_id) as lessons
			FROM {$wpdb->postmeta} pm
			WHERE pm.meta_value IN ( " . implode( ',', $course_ids ) . ' )'  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			. " AND pm.meta_key = '_lesson_course'
			GROUP BY pm.meta_value",
			'OBJECT_K'
		);
	}

	/**
	 * Get students count by courses.
	 *
	 * @since  4.4.1
	 *
	 * @param array $course_ids The array of courses ids.
	 * @return array students in courses.
	 */
	private function get_students_count_in_courses( array $course_ids ): array {
		if ( empty( $course_ids ) ) {
			return array();
		}

		$by_post = $this->aggregation_service->count_statuses_by_post(
			array(
				'type'     => 'course',
				'post__in' => $course_ids,
			)
		);

		$result = array();
		foreach ( $by_post as $course_id => $statuses ) {
			$result[ $course_id ] = (object) array(
				'course_id'      => $course_id,
				'students_count' => ( $statuses['in-progress'] ?? 0 ) + ( $statuses['complete'] ?? 0 ),
			);
		}

		return $result;
	}
}

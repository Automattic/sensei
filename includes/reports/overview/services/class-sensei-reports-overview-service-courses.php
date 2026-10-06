<?php
/**
 * File containing the Sensei_Reports_Overview_Service_Courses class.
 *
 * @package sensei
 */

use Sensei\Internal\Services\Grading_Stats_Service_Interface;
use Sensei\Internal\Services\Progress_Aggregation_Service_Interface;
use Sensei\Internal\Services\Progress_Query_Service_Factory;
use Sensei\Internal\Services\Utils;

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
	 * Grading statistics service.
	 *
	 * @var Grading_Stats_Service_Interface|null
	 */
	private ?Grading_Stats_Service_Interface $grading_stats_service = null;

	/**
	 * Progress aggregation service.
	 *
	 * @var Progress_Aggregation_Service_Interface|null
	 */
	private ?Progress_Aggregation_Service_Interface $aggregation_service = null;

	/**
	 * Create a courses overview service with its dependencies.
	 *
	 * @internal
	 * @since $$next-version$$
	 *
	 * @param Grading_Stats_Service_Interface        $grading_stats_service Grading statistics service.
	 * @param Progress_Aggregation_Service_Interface $aggregation_service   Progress aggregation service.
	 * @return self
	 */
	public static function create_with_dependencies( Grading_Stats_Service_Interface $grading_stats_service, Progress_Aggregation_Service_Interface $aggregation_service ): self {
		$instance                        = new self();
		$instance->grading_stats_service = $grading_stats_service;
		$instance->aggregation_service   = $aggregation_service;

		return $instance;
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

		$course_average_progress = $this->get_average_progress_by_course( $course_ids );

		return ceil( array_sum( $course_average_progress ) / count( $course_ids ) );
	}

	/**
	 * Get the average lesson progress grouped by course.
	 *
	 * @since $$next-version$$
	 *
	 * @param int[] $course_ids Course IDs.
	 * @return float[] Average progress keyed by course ID.
	 */
	public function get_average_progress_by_course( array $course_ids ): array {
		if ( empty( $course_ids ) ) {
			return array();
		}

		$lessons_by_course = $this->get_lessons_in_courses( $course_ids );

		// Limit completion counts to the lessons belonging to the courses in this report.
		$all_lesson_ids = array();
		foreach ( $lessons_by_course as $course_lessons ) {
			$all_lesson_ids = array_merge( $all_lesson_ids, $course_lessons );
		}
		$all_lesson_ids = array_unique( $all_lesson_ids );

		$progress_args             = array( 'exclude_user_login_prefixes' => Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES );
		$lessons_completions       = $this->get_lessons_completions( $all_lesson_ids, $progress_args );
		$student_count_per_courses = $this->get_students_count_in_courses( $course_ids, $progress_args );
		$course_average_progress   = array();

		foreach ( $course_ids as $course_id ) {
			if ( ! isset( $lessons_by_course[ $course_id ] ) || ! isset( $student_count_per_courses[ $course_id ] ) ) {
				continue;
			}
			// Get lessons in the course.
			$lessons = $lessons_by_course[ $course_id ];
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

			$course_average_progress[ $course_id ] = (float) ( $completed_count / ( $students_count * count( $lessons ) ) * 100 );
		}

		return $course_average_progress;
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

		return $this->get_grading_stats_service()->get_courses_average_grade(
			$course_ids,
			array( 'exclude_user_login_prefixes' => Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES )
		);
	}

	/**
	 * Get the sum of all user grades for the lessons in a course.
	 *
	 * @since $$next-version$$
	 *
	 * @param int[] $lesson_ids Lesson IDs in the course.
	 * @return int Sum of the grades.
	 */
	public function get_grade_sum_for_lessons( array $lesson_ids ): int {
		return (int) $this->get_grading_stats_service()->get_grade_totals( array( 'post__in' => $lesson_ids ) )['sum'];
	}

	/**
	 * Get average days to completion by courses.
	 *
	 * @since 4.4.1
	 * @access public
	 *
	 * @param array $course_ids Courses ids to filter by.
	 * @return float Average of rounded per-course completion days.
	 */
	public function get_average_days_to_completion( array $course_ids ): float {
		$course_averages = $this->get_average_days_to_completion_by_course( $course_ids );

		return $course_averages ? array_sum( $course_averages ) / count( $course_averages ) : 0.0;
	}

	/**
	 * Get rounded completion-day averages grouped by requested course ID.
	 *
	 * @since $$next-version$$
	 *
	 * @param int[] $course_ids Course IDs.
	 * @return array<int, float> Rounded completion days keyed by requested course ID.
	 */
	public function get_average_days_to_completion_by_course( array $course_ids ): array {
		if ( empty( $course_ids ) ) {
			return array();
		}

		return $this->get_aggregation_service()->get_course_completion_day_averages(
			$course_ids,
			array( 'exclude_user_login_prefixes' => Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES )
		);
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

		$total_grouped_by_course = $this->get_students_count_in_courses(
			$course_ids,
			array( 'exclude_user_login_prefixes' => Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES )
		);

		if ( empty( $total_grouped_by_course ) ) {
			return 0;
		}

		$to_total = function ( $acc, $current ) {
			return $acc + $current->students_count;
		};

		return array_reduce( $total_grouped_by_course, $to_total, 0 );
	}

	/**
	 * Get completions for the requested lessons.
	 *
	 * @since  4.4.1
	 *
	 * @param int[] $lesson_ids Lesson IDs.
	 * @param array $args       Optional query filters for get_lesson_completion_counts().
	 * @return array lessons completions.
	 */
	private function get_lessons_completions( array $lesson_ids, array $args = array() ): array {
		if ( empty( $lesson_ids ) ) {
			return array();
		}

		$counts = $this->get_aggregation_service()
			->get_lesson_completion_counts( $lesson_ids, $args );

		// Keep the object fields used by the existing progress calculation.
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
	 * @param int[] $course_ids The list of course IDs.
	 * @return array<int, int[]> Lesson IDs keyed by requested course ID.
	 */
	private function get_lessons_in_courses( array $course_ids ): array {
		global $wpdb;
		// Look up lessons on the course resolved by the progress-ID filter so they match its stored progress.
		$course_id_map   = Utils::get_progress_post_id_map( $course_ids, 'course' );
		$course_ids      = array_values( $course_id_map );
		$report_statuses = Utils::get_reports_post_status_sql();

		$query = "SELECT pm.meta_value as course_id, pm.post_id as lesson_id
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_value IN ( " . implode( ',', $course_ids ) . " )
			AND pm.meta_key = '_lesson_course'
			AND p.post_type = 'lesson'
			AND p.post_status IN ( {$report_statuses} )";

		$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Safe direct SQL; course IDs are integers from the progress-ID map.

		$lessons_by_course = array();
		foreach ( $results as $result ) {
			$lessons_by_course[ (int) $result->course_id ][] = (int) $result->lesson_id;
		}

		return Utils::map_results_to_requested_post_ids( $lessons_by_course, $course_id_map );
	}

	/**
	 * Get students count by courses.
	 *
	 * @since  4.4.1
	 *
	 * @param int[] $course_ids The array of course IDs.
	 * @param array $args       Optional query filters for count_statuses_by_post().
	 * @return array students in courses.
	 */
	private function get_students_count_in_courses( array $course_ids, array $args = array() ): array {
		if ( empty( $course_ids ) ) {
			return array();
		}

		$by_post = $this->get_aggregation_service()
			->count_statuses_by_post( $course_ids, $args );

		// Enrollment totals count only started or completed courses, as before.
		$result = array();
		foreach ( $by_post as $course_id => $statuses ) {
			$result[ $course_id ] = (object) array(
				'course_id'      => $course_id,
				'students_count' => ( $statuses['in-progress'] ?? 0 ) + ( $statuses['complete'] ?? 0 ),
			);
		}

		return $result;
	}

	/**
	 * Get the injected grading statistics service or create and retain the default.
	 *
	 * @return Grading_Stats_Service_Interface
	 */
	private function get_grading_stats_service(): Grading_Stats_Service_Interface {
		if ( null === $this->grading_stats_service ) {
			$this->grading_stats_service = ( new Progress_Query_Service_Factory( Sensei()->progress_storage_configuration ) )->create_grading_stats_service();
		}

		return $this->grading_stats_service;
	}

	/**
	 * Keep older constructor calls working when no aggregation service was supplied.
	 *
	 * @return Progress_Aggregation_Service_Interface
	 */
	private function get_aggregation_service(): Progress_Aggregation_Service_Interface {
		if ( null === $this->aggregation_service ) {
			$this->aggregation_service = ( new Progress_Query_Service_Factory( Sensei()->progress_storage_configuration ) )->create_aggregation_service();
		}

		return $this->aggregation_service;
	}
}

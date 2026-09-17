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
	private ?Grading_Stats_Service_Interface $grading_stats_service;

	/**
	 * Progress aggregation service.
	 *
	 * @var Progress_Aggregation_Service_Interface|null
	 */
	private ?Progress_Aggregation_Service_Interface $aggregation_service;

	/**
	 * Constructor.
	 *
	 * @param Grading_Stats_Service_Interface|null        $grading_stats_service Grading statistics service.
	 * @param Progress_Aggregation_Service_Interface|null $aggregation_service Progress aggregation service.
	 */
	public function __construct( ?Grading_Stats_Service_Interface $grading_stats_service = null, ?Progress_Aggregation_Service_Interface $aggregation_service = null ) {
		$this->grading_stats_service = $grading_stats_service;
		$this->aggregation_service   = $aggregation_service;
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
		$lessons_count_per_courses = $this->get_lessons_in_courses( $course_ids );
		$lessons_completions       = $this->get_lessons_completions();
		$student_count_per_courses = $this->get_students_count_in_courses( $course_ids );
		$total_average_progress    = 0;

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
			$course_average_progress = $completed_count / ( $students_count * count( $lessons ) ) * 100;

			// Add value to the total average progress.
			$total_average_progress += $course_average_progress;
		}
		// Divide total value to get average total value for average progress for courses.
		$average_total_average_progress = ceil( $total_average_progress / count( $course_ids ) );
		return $average_total_average_progress;
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

		return $this->get_grading_stats_service()->get_courses_average_grade( $course_ids );
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
		global $wpdb;

		$query = "
		SELECT AVG( aggregated.days_to_completion )
		FROM (
			SELECT CEIL( SUM( ABS( DATEDIFF( {$wpdb->comments}.comment_date, STR_TO_DATE( {$wpdb->commentmeta}.meta_value, '%Y-%m-%d %H:%i:%s' ) ) ) + 1 ) / COUNT({$wpdb->commentmeta}.comment_id) ) AS days_to_completion
			FROM {$wpdb->comments}
			LEFT JOIN {$wpdb->commentmeta} ON {$wpdb->comments}.comment_ID = {$wpdb->commentmeta}.comment_id
				AND {$wpdb->commentmeta}.meta_key = 'start'
			WHERE {$wpdb->comments}.comment_type = 'sensei_course_status'
				AND {$wpdb->comments}.comment_approved = 'complete'
				AND {$wpdb->comments}.comment_post_ID IN ( " . implode( ',', $course_ids ) . ' )' // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		. " GROUP BY {$wpdb->comments}.comment_post_ID
		) AS aggregated
		";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance improvement.
		return (float) $wpdb->get_var( $query );
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
	 * Get all lessons completions.
	 *
	 * @since  4.4.1
	 *
	 * @return array lessons completions.
	 */
	private function get_lessons_completions(): array {

		global $wpdb;
		$reports_statuses = Utils::get_reports_post_status_sql();
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Statuses come from a fixed constant.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe direct sql.
		$results = $wpdb->get_results(
			"SELECT wcom.comment_post_id lesson_id, COUNT(*) completion_count
						FROM {$wpdb->comments} wcom
						WHERE wcom.comment_approved IN ('graded', 'ungraded', 'passed', 'failed','complete')
						AND comment_type IN ('sensei_lesson_status')
						AND wcom.comment_post_ID IN
						(
						SELECT wpm.post_id lesson_id from {$wpdb->posts} wpc
						JOIN {$wpdb->postmeta} wpm on wpm.meta_value = wpc.id
						WHERE wpm.meta_key = '_lesson_course'
						AND wpc.post_status in ( {$reports_statuses} )
						)
						GROUP BY wcom.comment_post_id",
			'OBJECT_K'
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $results;
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
		// Look up lessons on the course resolved by the progress-ID filter so they match its stored progress.
		$course_id_map = Utils::get_progress_post_id_map( $course_ids, 'course' );
		$course_ids    = array_values( $course_id_map );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe direct sql.
		$results = $wpdb->get_results(
			"SELECT pm.meta_value as course_id, GROUP_CONCAT(pm.post_id) as lessons
			FROM {$wpdb->postmeta} pm
			WHERE pm.meta_value IN ( " . implode( ',', $course_ids ) . ' )'  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			. " AND pm.meta_key = '_lesson_course'
			GROUP BY pm.meta_value",
			'OBJECT_K'
		);

		// Keep the requested course IDs as keys for the report calculations.
		$requested_results = array();
		foreach ( $course_id_map as $requested_id => $stored_id ) {
			if ( isset( $results[ $stored_id ] ) ) {
				$requested_results[ $requested_id ] = $results[ $stored_id ];
			}
		}

		return $requested_results;
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

		$by_post = $this->get_aggregation_service()
			->count_statuses_by_post(
				array(
					'type'     => 'course',
					'post__in' => $course_ids,
				)
			);

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

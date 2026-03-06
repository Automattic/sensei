<?php
/**
 * File containing the Sensei_Reports_Overview_Service_Courses_Tables class.
 *
 * @package sensei
 * @since $$next-version$$
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Tables-based courses overview service class.
 *
 * @since $$next-version$$
 */
class Sensei_Reports_Overview_Service_Courses_Tables implements Sensei_Reports_Overview_Service_Courses_Interface {

	/**
	 * Constructor.
	 */
	public function __construct() {
	}

	/**
	 * Get total average progress value for courses.
	 *
	 * @since $$next-version$$
	 * @access public
	 *
	 * @param array $course_ids Courses ids.
	 * @return float Total average progress value for all the courses.
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
	 * @since $$next-version$$
	 * @access public
	 *
	 * @param array $course_ids Courses ids to filter by.
	 * @return double Average grade of all courses.
	 */
	public function get_courses_average_grade( array $course_ids ) {
		if ( empty( $course_ids ) ) {
			return 0;
		}
		global $wpdb;

		$submissions_table = $wpdb->prefix . 'sensei_lms_quiz_submissions';

		$placeholders = implode( ', ', array_fill( 0, count( $course_ids ), '%d' ) );

		/**
		 * The subquery calculates the average grade per course, and the outer query then calculates the
		 * average grade of all courses. To be included in the calculation, a quiz submission must:
		 *   Have a non-null final_grade.
		 *   Be associated with a course via the lesson's _lesson_course meta.
		 *   Have quiz questions (checking for the existence of '_quiz_has_questions' meta).
		 */
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table names and placeholders are safe.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance improvement.
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT AVG(course_average) as courses_average
				FROM (
					SELECT AVG(qs.final_grade) as course_average
					FROM {$submissions_table} qs
					INNER JOIN {$wpdb->posts} quiz ON qs.quiz_id = quiz.ID
					INNER JOIN {$wpdb->postmeta} course ON quiz.post_parent = course.post_id
					INNER JOIN {$wpdb->postmeta} has_questions ON quiz.post_parent = has_questions.post_id
					INNER JOIN {$wpdb->posts} p ON p.ID = course.meta_value
					WHERE qs.final_grade IS NOT NULL
						AND course.meta_key = '_lesson_course'
						AND course.meta_value <> ''
						AND has_questions.meta_key = '_quiz_has_questions'
						AND course.meta_value IN ( {$placeholders} )
					GROUP BY course.meta_value
				) averages_by_course",
				...$course_ids
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		return floatval( $result->courses_average );
	}

	/**
	 * Get average days to completion by courses.
	 *
	 * @since $$next-version$$
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

		$progress_table = $wpdb->prefix . 'sensei_lms_progress';
		$placeholders   = implode( ', ', array_fill( 0, count( $course_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table names and placeholders are safe.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance improvement.
		return (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(aggregated.days_to_completion)
				FROM (
					SELECT CEIL(SUM(ABS(DATEDIFF(p.completed_at, p.started_at)) + 1) / COUNT(p.id)) AS days_to_completion
					FROM {$progress_table} p
					WHERE p.type = 'course'
						AND p.status = 'complete'
						AND p.post_id IN ( {$placeholders} )
						AND p.started_at IS NOT NULL
					GROUP BY p.post_id
				) AS aggregated",
				...$course_ids
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	}

	/**
	 * Get total of enrollments.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $course_ids Courses ids to filter by.
	 * @return int Total of enrollments.
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
	 * Get all lessons completions.
	 *
	 * @since $$next-version$$
	 *
	 * @return array Lessons completions.
	 */
	private function get_lessons_completions(): array {
		global $wpdb;

		$progress_table = $wpdb->prefix . 'sensei_lms_progress';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are safe.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance improvement.
		return $wpdb->get_results(
			"SELECT p.post_id AS lesson_id, COUNT(*) AS completion_count
			FROM {$progress_table} p
			WHERE p.status IN ('graded', 'ungraded', 'passed', 'failed', 'complete')
			AND p.type = 'lesson'
			AND p.post_id IN (
				SELECT wpm.post_id FROM {$wpdb->posts} wpc
				JOIN {$wpdb->postmeta} wpm ON wpm.meta_value = wpc.id
				WHERE wpm.meta_key = '_lesson_course'
				AND wpc.post_status IN ('publish', 'private')
			)
			GROUP BY p.post_id",
			'OBJECT_K'
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Get lessons grouped by courses.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $course_ids The list of courses ids.
	 * @return array Lessons count in courses.
	 */
	private function get_lessons_in_courses( $course_ids ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance improvement.
		return $wpdb->get_results(
			"SELECT pm.meta_value as course_id, GROUP_CONCAT(pm.post_id) as lessons
			FROM {$wpdb->postmeta} pm
			WHERE pm.meta_value IN ( " . implode( ',', $course_ids ) . ' )' // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			. " AND pm.meta_key = '_lesson_course'
			GROUP BY pm.meta_value",
			'OBJECT_K'
		);
	}

	/**
	 * Get students count by courses.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $course_ids The array of courses ids.
	 * @return array Students in courses.
	 */
	private function get_students_count_in_courses( array $course_ids ): array {
		global $wpdb;

		$progress_table = $wpdb->prefix . 'sensei_lms_progress';
		$placeholders   = implode( ', ', array_fill( 0, count( $course_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table names and placeholders are safe.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance improvement.
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.post_id AS course_id, COUNT(p.post_id) AS students_count
				FROM {$progress_table} p
				WHERE p.post_id IN ( {$placeholders} )
				AND p.type = 'course'
				AND p.status IN ('in-progress', 'complete')
				GROUP BY p.post_id",
				...$course_ids
			),
			'OBJECT_K'
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	}
}

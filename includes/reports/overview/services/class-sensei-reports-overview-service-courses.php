<?php
/**
 * File containing the Sensei_Reports_Overview_Service_Courses class.
 *
 * @package sensei
 */

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
	 * Constructor
	 */
	public function __construct() {
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
		global $wpdb;
		/**
		 * The subquery calculates the average grade per course, and the outer query then calculates the
		 * average grade of all courses. To be included in the calculation, a lesson must:
		 *   Have a status of 'graded', 'passed' or 'failed'.
		 *   Have grade data.
		 *   Be associated with a course.
		 *   Have quiz questions (checking for the existence of '_quiz_has_questions' meta is sufficient;
		 *   if it exists its value will be 1).
		 */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance improvement.
		$result = $wpdb->get_row(
			"SELECT AVG(course_average) as courses_average
		FROM (
			SELECT AVG(cm.meta_value) as course_average
			FROM {$wpdb->comments} c
			INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id
			INNER JOIN {$wpdb->postmeta} course ON c.comment_post_ID = course.post_id
			INNER JOIN {$wpdb->postmeta} has_questions ON c.comment_post_ID = has_questions.post_id
			INNER JOIN {$wpdb->posts} p ON p.ID = course.meta_value
			WHERE c.comment_type = 'sensei_lesson_status'
				AND c.comment_approved IN ( 'graded', 'passed', 'failed' )
				AND cm.meta_key = 'grade'
				AND course.meta_key = '_lesson_course'
				AND course.meta_value <> ''
				AND has_questions.meta_key = '_quiz_has_questions'
				AND course.meta_value IN ( " . implode( ',', $course_ids ) . ' ) ' // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			. ' GROUP BY course.meta_value
		) averages_by_course'
		);

		return doubleval( $result->courses_average );
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
	public function get_average_days_to_completion( array $course_ids ) : float {
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
	 * Get all lessons completions.
	 *
	 * @since  4.4.1
	 *
	 * @return array lessons completions.
	 */
	private function get_lessons_completions(): array {

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe direct sql.
		return $wpdb->get_results(
			"SELECT wcom.comment_post_id lesson_id, COUNT(*) completion_count
						FROM {$wpdb->comments} wcom
						WHERE wcom.comment_approved IN ('graded', 'ungraded', 'passed', 'failed','complete')
						AND comment_type IN ('sensei_lesson_status')
						AND wcom.comment_post_ID IN
						(
						SELECT wpm.post_id lesson_id from {$wpdb->posts} wpc
						JOIN {$wpdb->postmeta} wpm on wpm.meta_value = wpc.id
						WHERE wpm.meta_key = '_lesson_course'
						AND wpc.post_status in ('publish','private')
						)
						GROUP BY wcom.comment_post_id",
			'OBJECT_K'
		);
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

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe direct sql.
		return $wpdb->get_results(
			"SELECT c.comment_post_ID as course_id, count(c.comment_post_ID) as students_count
				FROM {$wpdb->comments} c
				WHERE c.comment_post_ID IN ( " . implode( ',', $course_ids ) . ' )'  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			. " AND c.comment_type = 'sensei_course_status'
				AND c.comment_approved IN ( 'in-progress', 'complete' )
				GROUP BY c.comment_post_ID",
			'OBJECT_K'
		);
	}

}

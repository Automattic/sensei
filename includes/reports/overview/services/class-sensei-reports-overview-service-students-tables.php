<?php
/**
 * File containing the Sensei_Reports_Overview_Service_Students_Tables class.
 *
 * @package sensei
 * @since $$next-version$$
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Tables-based students overview service class.
 *
 * @since $$next-version$$
 */
class Sensei_Reports_Overview_Service_Students_Tables extends Sensei_Reports_Overview_Service_Students {

	/**
	 * Get average grade of all lessons graded in all the courses filtered by students.
	 *
	 * @since $$next-version$$
	 * @access public
	 *
	 * @param array $user_ids User ids.
	 * @return double Average value of all the graded lessons in all the courses.
	 */
	public function get_graded_lessons_average_grade( $user_ids ) {
		if ( empty( $user_ids ) ) {
			return 0;
		}
		global $wpdb;

		$submissions_table = $wpdb->prefix . 'sensei_lms_quiz_submissions';
		$placeholders      = implode( ', ', array_fill( 0, count( $user_ids ), '%d' ) );

		// Fetching all the grades of all the quizzes that have been graded.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table names and placeholders are safe.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance improvement.
		$sum_result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT SUM(qs.final_grade) AS grade_sum, COUNT(*) as grade_count
				FROM {$submissions_table} qs
				WHERE qs.user_id IN ( {$placeholders} )
				AND qs.final_grade IS NOT NULL",
				...$user_ids
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$average_grade_value = 0;
		if ( ! $sum_result->grade_count || '0' === $sum_result->grade_count ) {
			return $average_grade_value;
		}
		$average_grade_value = ceil( $sum_result->grade_sum / $sum_result->grade_count );
		return $average_grade_value;
	}
}

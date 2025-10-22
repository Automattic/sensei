<?php
/**
 * File containing the Sensei_Reports_Overview_Service_Students class.
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
class Sensei_Reports_Overview_Service_Students {

	/**
	 * Constructor
	 */
	public function __construct() {     }

	/**
	 * Get average grade of all lessons graded in all the courses filtered by students.
	 *
	 * @since 4.4.1
	 * @access public
	 *
	 * @param array $user_ids user ids.
	 * @return double $graded_lesson_average_grade Average value of all the graded lessons in all the courses.
	 */
	public function get_graded_lessons_average_grade( $user_ids ) {
		if ( empty( $user_ids ) ) {
			return 0;
		}
		global $wpdb;

		// Fetching all the grades of all the lessons that are graded.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance improvement.
		$sum_result          = $wpdb->get_row(
			"SELECT SUM( {$wpdb->commentmeta}.meta_value ) AS grade_sum,COUNT( * ) as grade_count FROM {$wpdb->comments}
			INNER JOIN {$wpdb->commentmeta}  ON ( {$wpdb->comments}.comment_ID = {$wpdb->commentmeta}.comment_id )
			WHERE {$wpdb->comments}.comment_type IN ('sensei_lesson_status') AND ( {$wpdb->commentmeta}.meta_key = 'grade')
			AND {$wpdb->comments}.user_id IN ( " . implode( ',', $user_ids ) . ' )' // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
		$average_grade_value = 0;
		if ( ! $sum_result->grade_count || '0' === $sum_result->grade_count ) {
			return $average_grade_value;
		}
		$average_grade_value = ceil( $sum_result->grade_sum / $sum_result->grade_count );
		return $average_grade_value;
	}

	/**
	 * Retrieves the most recent activity date of a user in GMT from the comments table,
	 * specifically for approved lesson status comments.
	 *
	 * @param int $user_id The ID of the user for whom the last activity is being retrieved.
	 *
	 * @return string The date of the user's last activity in GMT, or an empty string if no activity is found.
	 */
	public function get_user_last_activity_date( int $user_id ): string {
		global $wpdb;

		$sql = "SELECT MAX({$wpdb->comments}.comment_date_gmt)
				FROM {$wpdb->comments}
					USE INDEX (sensei_comment_type_user_id)
				WHERE {$wpdb->comments}.user_id = %d
					AND {$wpdb->comments}.comment_approved IN ('complete', 'passed', 'graded')
					AND {$wpdb->comments}.comment_type = 'sensei_lesson_status'
				ORDER BY {$wpdb->comments}.comment_date_gmt DESC
		";

		$last_activity_date = $wpdb->get_var( $wpdb->prepare( $sql, $user_id ) );

		return $last_activity_date ?? '';
	}
}

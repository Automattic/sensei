<?php
/**
 * File containing the Sensei_Reports_Overview_Data_Provider_Lessons_Tables class.
 *
 * @package sensei
 * @since $$next-version$$
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tables-based data provider for lesson reports.
 *
 * Extends the comments-based lesson data provider, replacing the days to complete
 * and last activity subqueries to use the `sensei_lms_progress` table instead of
 * the comments table.
 *
 * @package sensei
 * @since $$next-version$$
 */
class Sensei_Reports_Overview_Data_Provider_Lessons_Tables extends Sensei_Reports_Overview_Data_Provider_Lessons {

	/**
	 * Add the sum of days taken by each student to complete a lesson with returning lesson row.
	 *
	 * @since $$next-version$$
	 * @access private
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 *
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_days_to_complete_to_lessons_query( array $clauses ): array {
		global $wpdb;

		$progress_table = $wpdb->prefix . 'sensei_lms_progress';

		$clauses['fields'] .= ", (SELECT SUM( ABS( DATEDIFF( {$progress_table}.completed_at, {$progress_table}.started_at ) ) + 1 ) as days_to_complete";
		$clauses['fields'] .= " FROM {$progress_table}";
		$clauses['fields'] .= " WHERE {$progress_table}.post_id = {$wpdb->posts}.ID";
		$clauses['fields'] .= " AND {$progress_table}.type = 'lesson'";
		$clauses['fields'] .= " AND {$progress_table}.status IN ( 'complete', 'graded', 'passed', 'failed', 'ungraded' )";
		$clauses['fields'] .= ') as days_to_complete';

		return $clauses;
	}

	/**
	 * Add the `last_activity` field to the query.
	 *
	 * @since $$next-version$$
	 * @access private
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 *
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_last_activity_to_lessons_query( array $clauses ): array {
		global $wpdb;

		$progress_table = $wpdb->prefix . 'sensei_lms_progress';

		$clauses['fields'] .= ", (
			SELECT MAX({$progress_table}.updated_at)
			FROM {$progress_table}
			WHERE {$progress_table}.post_id = {$wpdb->posts}.ID
			AND {$progress_table}.status IN ('complete', 'passed', 'graded')
			AND {$progress_table}.type = 'lesson'
			ORDER BY {$progress_table}.updated_at DESC
		) AS last_activity_date";

		return $clauses;
	}
}

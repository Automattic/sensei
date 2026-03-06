<?php
/**
 * File containing the Sensei_Reports_Overview_Data_Provider_Students_Tables class.
 *
 * @package sensei
 * @since $$next-version$$
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tables-based data provider for student reports.
 *
 * Extends the comments-based student data provider, replacing the last activity
 * subquery to use the `sensei_lms_progress` table instead of the comments table.
 *
 * @package sensei
 * @since $$next-version$$
 */
class Sensei_Reports_Overview_Data_Provider_Students_Tables extends Sensei_Reports_Overview_Data_Provider_Students {

	/**
	 * Add last activity date to the user query using the progress table.
	 *
	 * @since $$next-version$$
	 *
	 * @param WP_User_Query $query The user query.
	 */
	public function add_last_activity_to_user_query( WP_User_Query $query ) {
		global $wpdb;

		$progress_table = $wpdb->prefix . 'sensei_lms_progress';

		$query->query_fields .= ', last_activity_date';
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe.
		$query->query_from .= $wpdb->prepare(
			" LEFT JOIN (
				SELECT {$progress_table}.user_id, MAX({$progress_table}.updated_at) AS last_activity_date
				FROM {$progress_table}
				WHERE {$progress_table}.status IN (%s, %s, %s)
				AND {$progress_table}.type = %s
				GROUP BY {$progress_table}.user_id
			) AS l ON {$wpdb->users}.ID = l.user_id",
			'complete',
			'passed',
			'graded',
			'lesson'
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}

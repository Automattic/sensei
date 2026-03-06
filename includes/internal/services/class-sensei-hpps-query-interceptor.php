<?php
/**
 * File containing the Sensei_HPPS_Query_Interceptor class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Sensei_HPPS_Query_Interceptor
 *
 * Intercepts WP_Query posts_clauses that reference WordPress comments tables
 * for Sensei progress data, and rewrites them to use the HPPS custom tables
 * (sensei_lms_progress) instead.
 *
 * This is a transparent hook-based approach: no existing code is modified.
 * The interceptor hooks into `posts_clauses` at a later priority than the
 * reports data providers, and rewrites the SQL clauses.
 *
 * @since $$next-version$$
 */
class Sensei_HPPS_Query_Interceptor {

	/**
	 * WordPress database instance.
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Constructor.
	 *
	 * @param wpdb $wpdb WordPress database instance.
	 */
	public function __construct( $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Register the interceptor hooks.
	 *
	 * Hooks into `posts_clauses` at priority 20 (after the data provider's
	 * default priority 10) to rewrite comment-based clauses.
	 *
	 * @since $$next-version$$
	 */
	public function register(): void {
		add_filter( 'posts_clauses', array( $this, 'intercept_clauses' ), 20 );
	}

	/**
	 * Intercept and rewrite posts_clauses that reference Sensei comment tables.
	 *
	 * Only rewrites clauses that contain Sensei-specific comment type references
	 * (sensei_course_status, sensei_lesson_status). Non-Sensei queries pass through
	 * unmodified.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function intercept_clauses( array $clauses ): array {
		$join = $clauses['join'] ?? '';

		// Only intercept queries that reference Sensei comment types.
		$has_course_status = false !== strpos( $join, 'sensei_course_status' );
		$has_lesson_status = false !== strpos( $join, 'sensei_lesson_status' );

		if ( ! $has_course_status && ! $has_lesson_status ) {
			return $clauses;
		}

		if ( $has_lesson_status ) {
			$clauses = $this->rewrite_last_activity_clauses( $clauses );
		}

		if ( $has_course_status ) {
			$clauses = $this->rewrite_days_to_completion_clauses( $clauses );
		}

		return $clauses;
	}

	/**
	 * Rewrite last_activity clauses from comments to sensei_lms_progress.
	 *
	 * Replaces the subquery that JOINs wp_comments (sensei_lesson_status) to get
	 * the last activity date, with a subquery on the progress table instead.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 * @return array Modified associative array of the clauses for the query.
	 */
	private function rewrite_last_activity_clauses( array $clauses ): array {
		$progress_table = $this->wpdb->prefix . 'sensei_lms_progress';

		// Build the replacement subquery for last activity using the progress table.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$lessons_query = 'SELECT p.post_id AS lesson_id, MAX(p.updated_at) AS last_activity_date'
			. " FROM {$progress_table} p"
			. " WHERE p.status IN ('complete', 'passed', 'graded')"
			. " AND p.type = 'lesson'"
			. ' GROUP BY p.post_id';

		$course_query = 'SELECT DISTINCT pm.meta_value AS course_id, cm.last_activity_date'
			. " FROM {$this->wpdb->postmeta} pm JOIN ({$lessons_query}) cm"
			. ' ON cm.lesson_id = pm.post_id'
			. " AND pm.meta_key = '_lesson_course'"
			. ' GROUP BY pm.meta_value';
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Replace the original comments-based last_activity subquery in the JOIN clause.
		// The original pattern JOINs a subquery aliased as `la` that references wp_comments.
		$pattern = '/LEFT JOIN \(SELECT DISTINCT pm\.meta_value AS course_id, cm\.comment_date_gmt\s+'
			. 'FROM \S+postmeta pm JOIN \(SELECT cm\.comment_post_id lesson_id, MAX\(cm\.comment_date_gmt\) as comment_date_gmt\s+'
			. 'FROM \S+comments cm\s+'
			. 'WHERE cm\.comment_approved IN \(\'complete\', \'passed\', \'graded\'\)\s+'
			. 'AND cm\.comment_type = \'sensei_lesson_status\'\s+'
			. 'GROUP BY cm\.comment_post_id\) cm\s+'
			. 'ON cm\.lesson_id = pm\.post_id\s+'
			. 'AND pm\.meta_key = \'_lesson_course\'\s+'
			. 'GROUP BY pm\.meta_value\s*\) AS la ON la\.course_id = \S+\.ID/s';

		$replacement = "LEFT JOIN ({$course_query}) AS la ON la.course_id = {$this->wpdb->posts}.ID";

		$clauses['join'] = preg_replace( $pattern, $replacement, $clauses['join'] );

		// Replace field references: comment_date_gmt -> last_activity_date.
		$clauses['fields'] = str_replace(
			'la.comment_date_gmt AS last_activity_date',
			'la.last_activity_date AS last_activity_date',
			$clauses['fields']
		);

		// Replace where clause references for date filtering.
		$clauses['where'] = str_replace(
			'la.comment_date_gmt',
			'la.last_activity_date',
			$clauses['where']
		);

		return $clauses;
	}

	/**
	 * Rewrite days_to_completion clauses from comments to sensei_lms_progress.
	 *
	 * Replaces JOINs on wp_comments + wp_commentmeta (sensei_course_status + start meta)
	 * with a JOIN on sensei_lms_progress using started_at and completed_at columns.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 * @return array Modified associative array of the clauses for the query.
	 */
	private function rewrite_days_to_completion_clauses( array $clauses ): array {
		$progress_table = $this->wpdb->prefix . 'sensei_lms_progress';

		// Replace the days_to_completion field calculation.
		// Original uses: DATEDIFF( wp_comments.comment_date, STR_TO_DATE( wp_commentmeta.meta_value, '%Y-%m-%d %H:%i:%s' ) )
		// New uses: DATEDIFF( completed_at, started_at ) from the progress table.
		$old_days_field = "SUM(  ABS( DATEDIFF( {$this->wpdb->comments}.comment_date,"
			. " STR_TO_DATE( {$this->wpdb->commentmeta}.meta_value, '%Y-%m-%d %H:%i:%s' ) ) ) + 1 )"
			. ' AS days_to_completion';

		$new_days_field = 'SUM( ABS( DATEDIFF( sensei_progress.completed_at, sensei_progress.started_at ) ) + 1 )'
			. ' AS days_to_completion';

		$clauses['fields'] = str_replace( $old_days_field, $new_days_field, $clauses['fields'] );

		// Replace the count_of_completions field.
		$old_count_field = "COUNT({$this->wpdb->commentmeta}.comment_id) AS count_of_completions";
		$new_count_field = 'COUNT(sensei_progress.id) AS count_of_completions';

		$clauses['fields'] = str_replace( $old_count_field, $new_count_field, $clauses['fields'] );

		// Replace the comment + commentmeta JOINs with a single progress table JOIN.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$old_join_pattern = " LEFT JOIN {$this->wpdb->comments} ON {$this->wpdb->comments}.comment_post_ID = {$this->wpdb->posts}.ID"
			. " AND {$this->wpdb->comments}.comment_type IN ('sensei_course_status')"
			. " AND {$this->wpdb->comments}.comment_approved IN ( 'complete' )"
			. " AND {$this->wpdb->comments}.comment_post_ID = {$this->wpdb->posts}.ID"
			. " LEFT JOIN {$this->wpdb->commentmeta} ON {$this->wpdb->comments}.comment_ID = {$this->wpdb->commentmeta}.comment_id"
			. " AND {$this->wpdb->commentmeta}.meta_key = 'start'";

		$new_join = " LEFT JOIN {$progress_table} AS sensei_progress ON sensei_progress.post_id = {$this->wpdb->posts}.ID"
			. " AND sensei_progress.type = 'course'"
			. " AND sensei_progress.status = 'complete'"
			. ' AND sensei_progress.started_at IS NOT NULL';
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$clauses['join'] = str_replace( $old_join_pattern, $new_join, $clauses['join'] );

		return $clauses;
	}
}

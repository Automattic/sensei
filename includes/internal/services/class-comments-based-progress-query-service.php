<?php
/**
 * File containing the Comments_Based_Progress_Query_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Comments_Based_Progress_Query_Service.
 *
 * Comments-based implementation of the Progress_Query_Service_Interface.
 * Queries wp_comments and wp_commentmeta for progress data.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Comments_Based_Progress_Query_Service implements Progress_Query_Service_Interface {

	/**
	 * The WordPress database object.
	 *
	 * @var \wpdb
	 */
	private \wpdb $wpdb;

	/**
	 * Constructor.
	 *
	 * @since $$next-version$$
	 *
	 * @param \wpdb $wpdb The WordPress database object.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Modify WP_Query clauses to add last activity date to course posts.
	 *
	 * Joins wp_comments via wp_postmeta to find the most recent lesson completion
	 * date for each course.
	 *
	 * @since $$next-version$$
	 *
	 * @param array     $clauses  Associative array of the clauses for the query.
	 * @param \WP_Query $wp_query The WP_Query instance.
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_last_activity_to_courses_clauses( array $clauses, \WP_Query $wp_query ): array {
		$wpdb = $this->wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are from wpdb.
		$lessons_query = "SELECT cm.comment_post_id lesson_id, MAX(cm.comment_date_gmt) as comment_date_gmt
			FROM {$wpdb->comments} cm
			WHERE cm.comment_approved IN ('complete', 'passed', 'graded')
			AND cm.comment_type = 'sensei_lesson_status'
			GROUP BY cm.comment_post_id";

		$course_query = "SELECT DISTINCT pm.meta_value AS course_id, cm.comment_date_gmt
		FROM {$wpdb->postmeta} pm JOIN ({$lessons_query}) cm
		ON cm.lesson_id = pm.post_id
		AND pm.meta_key = '_lesson_course'
		GROUP BY pm.meta_value
		";

		$clauses['fields'] .= ', la.comment_date_gmt AS last_activity_date';
		$clauses['join']   .= " LEFT JOIN ({$course_query}) AS la ON la.course_id = {$wpdb->posts}.ID";
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $clauses;
	}

	/**
	 * Modify WP_Query clauses to add days-to-completion data to course posts.
	 *
	 * Joins wp_comments and wp_commentmeta to calculate the sum of days taken
	 * by each student to complete a course and the number of completions.
	 *
	 * @since $$next-version$$
	 *
	 * @param array     $clauses  Associative array of the clauses for the query.
	 * @param \WP_Query $wp_query The WP_Query instance.
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_days_to_completion_to_courses_clauses( array $clauses, \WP_Query $wp_query ): array {
		$wpdb = $this->wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are from wpdb.
		// Get the number of days to complete a course: `days to complete = complete date - start date + 1`.
		$clauses['fields'] .= ", SUM(  ABS( DATEDIFF( {$wpdb->comments}.comment_date, STR_TO_DATE( {$wpdb->commentmeta}.meta_value, '%Y-%m-%d %H:%i:%s' ) ) ) + 1 ) AS days_to_completion";
		// We consider the course as completed if there is a comment and corresponding meta for it.
		$clauses['fields']  .= ", COUNT({$wpdb->commentmeta}.comment_id) AS count_of_completions";
		$clauses['join']    .= " LEFT JOIN {$wpdb->comments} ON {$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID";
		$clauses['join']    .= " AND {$wpdb->comments}.comment_type IN ('sensei_course_status')";
		$clauses['join']    .= " AND {$wpdb->comments}.comment_approved IN ( 'complete' )";
		$clauses['join']    .= " AND {$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID";
		$clauses['join']    .= " LEFT JOIN {$wpdb->commentmeta} ON {$wpdb->comments}.comment_ID = {$wpdb->commentmeta}.comment_id";
		$clauses['join']    .= " AND {$wpdb->commentmeta}.meta_key = 'start'";
		$clauses['groupby'] .= " {$wpdb->posts}.ID";
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $clauses;
	}

	/**
	 * Modify WP_Query clauses to filter courses by last activity date range.
	 *
	 * @since $$next-version$$
	 *
	 * @param array  $clauses Associative array of the clauses for the query.
	 * @param string $from    Start date for filtering (empty string for no start date).
	 * @param string $to      End date for filtering (empty string for no end date).
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function filter_courses_by_last_activity( array $clauses, string $from = '', string $to = '' ): array {
		$wpdb = $this->wpdb;

		// Filter by start date.
		if ( $from ) {
			$clauses['where'] .= $wpdb->prepare(
				' AND la.comment_date_gmt >= %s',
				$from
			);
		}

		// Filter by end date.
		if ( $to ) {
			$clauses['where'] .= $wpdb->prepare(
				' AND la.comment_date_gmt <= %s',
				$to
			);
		}

		return $clauses;
	}
}

<?php
/**
 * File containing the Tables_Based_Progress_Clauses_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Tables_Based_Progress_Clauses_Service.
 *
 * Tables-based implementation of the Progress_Clauses_Service_Interface.
 * Queries the HPPS custom tables (sensei_lms_progress) for progress data.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Tables_Based_Progress_Clauses_Service implements Progress_Clauses_Service_Interface {

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
	 * Get the progress table name.
	 *
	 * @since $$next-version$$
	 *
	 * @return string The progress table name.
	 */
	private function get_progress_table_name(): string {
		return $this->wpdb->prefix . 'sensei_lms_progress';
	}

	/**
	 * Modify WP_Query clauses to add last activity date to course posts.
	 *
	 * Joins sensei_lms_progress to find the most recent lesson activity date
	 * for each course.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_last_activity_to_courses_clauses( array $clauses ): array {
		$progress_table = $this->get_progress_table_name();

		$wpdb = $this->wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are constructed from wpdb prefix.
		$lessons_query = "SELECT lp.post_id AS lesson_id, MAX(lp.updated_at) AS last_activity_date
			FROM {$progress_table} lp
			WHERE lp.type = 'lesson'
			AND lp.status IN ('complete', 'passed', 'graded')
			GROUP BY lp.post_id";

		$course_query = "SELECT DISTINCT pm.meta_value AS course_id, lp.last_activity_date
			FROM {$wpdb->postmeta} pm
			JOIN ({$lessons_query}) lp ON lp.lesson_id = pm.post_id
			AND pm.meta_key = '_lesson_course'
			GROUP BY pm.meta_value";

		$clauses['fields'] .= ', la.last_activity_date AS last_activity_date';
		$clauses['join']   .= " LEFT JOIN ({$course_query}) AS la ON la.course_id = {$wpdb->posts}.ID";
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $clauses;
	}

	/**
	 * Modify WP_Query clauses to add days-to-completion data to course posts.
	 *
	 * Joins sensei_lms_progress to calculate the sum of days taken by each student
	 * to complete a course and the number of completions using started_at and
	 * completed_at columns.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_days_to_completion_to_courses_clauses( array $clauses ): array {
		$progress_table = $this->get_progress_table_name();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is constructed from wpdb prefix.
		$clauses['fields']  .= ', SUM( ABS( DATEDIFF( cp.completed_at, cp.started_at ) ) + 1 ) AS days_to_completion';
		$clauses['fields']  .= ', COUNT(cp.id) AS count_of_completions';
		$clauses['join']    .= " LEFT JOIN {$progress_table} cp ON cp.post_id = {$this->wpdb->posts}.ID";
		$clauses['join']    .= " AND cp.type = 'course'";
		$clauses['join']    .= " AND cp.status = 'complete'";
		$clauses['groupby'] .= " {$this->wpdb->posts}.ID";
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
				' AND la.last_activity_date >= %s',
				$from
			);
		}

		// Filter by end date.
		if ( $to ) {
			$clauses['where'] .= $wpdb->prepare(
				' AND la.last_activity_date <= %s',
				$to
			);
		}

		return $clauses;
	}
}

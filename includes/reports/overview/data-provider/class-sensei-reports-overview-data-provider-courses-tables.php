<?php
/**
 * File containing the Sensei_Reports_Overview_Data_Provider_Courses_Tables class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Class Sensei_Reports_Overview_Data_Provider_Courses_Tables
 *
 * Tables-based implementation that replaces comment queries with HPPS custom table queries.
 *
 * @since $$next-version$$
 */
class Sensei_Reports_Overview_Data_Provider_Courses_Tables implements Sensei_Reports_Overview_Data_Provider_Interface {

	/**
	 * Total number of courses found with given criteria.
	 *
	 * @var int Total number of items
	 */
	private $last_total_items = 0;

	/**
	 * Contains start date and time for filtering.
	 *
	 * @var string|null
	 */
	private $date_from;

	/**
	 * Contains end date and time for filtering.
	 *
	 * @var string|null
	 */
	private $date_to;

	/**
	 * Get the data for the overview report.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $filters Filters to apply to the data.
	 *
	 * @return array
	 */
	public function get_items( array $filters ): array {
		$this->date_from = $filters['last_activity_date_from'] ?? null;
		$this->date_to   = $filters['last_activity_date_to'] ?? null;

		$course_args = array(
			'post_type'        => 'course',
			'post_status'      => array( 'publish', 'private' ),
			'posts_per_page'   => $filters['number'],
			'offset'           => $filters['offset'],
			'fields'           => $filters['fields'] ?? '',
			'orderby'          => $filters['orderby'] ?? '',
			'order'            => $filters['order'] ?? 'ASC',
			'suppress_filters' => 0,
		);

		if ( isset( $filters['search'] ) ) {
			$course_args['s'] = $filters['search'];
		}

		add_filter( 'posts_clauses', [ $this, 'add_last_activity_to_courses_query' ] );
		add_filter( 'posts_clauses', [ $this, 'add_days_to_completion_to_courses_query' ] );
		add_filter( 'posts_clauses', [ $this, 'filter_courses_by_last_activity' ] );

		if ( 'count_of_completions' === $course_args['orderby'] ) {
			add_filter( 'posts_orderby', array( $this, 'add_orderby_custom_field_to_query' ), 10, 2 );
		}

		/**
		 * Filter the courses query arguments.
		 *
		 * @hook sensei_analysis_overview_filter_courses
		 *
		 * @param {array} $course_args Array of arguments for the courses query.
		 * @return {array} Filtered array of arguments for the courses query.
		 */
		$course_args   = apply_filters( 'sensei_analysis_overview_filter_courses', $course_args );
		$courses_query = new WP_Query( $course_args );

		remove_filter( 'posts_orderby', array( $this, 'add_orderby_custom_field_to_query' ), 10, 2 );
		remove_filter( 'posts_clauses', [ $this, 'filter_courses_by_last_activity' ] );
		remove_filter( 'posts_clauses', [ $this, 'add_days_to_completion_to_courses_query' ] );
		remove_filter( 'posts_clauses', [ $this, 'add_last_activity_to_courses_query' ] );
		remove_filter( 'posts_orderby', array( $this, 'add_orderby_custom_field_to_query' ), 10, 2 );

		$this->last_total_items = $courses_query->found_posts;

		return $courses_query->posts;
	}

	/**
	 * Order query based on the custom field.
	 *
	 * @since  $$next-version$$
	 * @access private
	 *
	 * @param array  $args Arguments Old orderby arguments.
	 * @param object $query Query.
	 */
	public function add_orderby_custom_field_to_query( $args, $query ) {
		return $query->query_vars['orderby'] . ' ' . $query->query_vars['order'];
	}

	/**
	 * Add last activity date for each course.
	 *
	 * Uses the HPPS progress table instead of comments to determine the latest
	 * lesson activity per course.
	 *
	 * @since  $$next-version$$
	 * @access private
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 *
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_last_activity_to_courses_query( array $clauses ): array {
		global $wpdb;

		$progress_table = $wpdb->prefix . 'sensei_lms_progress';

		$lessons_query = "SELECT sp.post_id AS lesson_id, MAX(sp.updated_at) AS last_activity_date
			FROM {$progress_table} sp
			WHERE sp.status IN ('complete', 'passed', 'graded')
			AND sp.type = 'lesson'
			GROUP BY sp.post_id";

		$course_query = "SELECT DISTINCT pm.meta_value AS course_id, lp.last_activity_date
			FROM {$wpdb->postmeta} pm JOIN ({$lessons_query}) lp
			ON lp.lesson_id = pm.post_id
			AND pm.meta_key = '_lesson_course'
			GROUP BY pm.meta_value
		";

		$clauses['fields'] .= ', la.last_activity_date AS last_activity_date';
		$clauses['join']   .= " LEFT JOIN ({$course_query}) AS la ON la.course_id = {$wpdb->posts}.ID";

		return $clauses;
	}

	/**
	 * Filter the courses by last activity start/end date.
	 *
	 * @since  $$next-version$$
	 * @access private
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 *
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function filter_courses_by_last_activity( array $clauses ): array {
		global $wpdb;

		// Filter by start date.
		if ( $this->date_from ) {
			$clauses['where'] .= $wpdb->prepare(
				' AND la.last_activity_date >= %s',
				$this->date_from
			);
		}

		// Filter by end date.
		if ( $this->date_to ) {
			$clauses['where'] .= $wpdb->prepare(
				' AND la.last_activity_date <= %s',
				$this->date_to
			);
		}

		return $clauses;
	}

	/**
	 * Add the sum of days taken by each student to complete a course and the number of completions for each course.
	 *
	 * Uses the HPPS progress table instead of comments/commentmeta to calculate
	 * days to completion and completion counts.
	 *
	 * @since  $$next-version$$
	 * @access private
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 *
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_days_to_completion_to_courses_query( array $clauses ): array {
		global $wpdb;

		$progress_table = $wpdb->prefix . 'sensei_lms_progress';

		// Get the number of days to complete a course: `days to complete = completed_at - started_at + 1`.
		$clauses['fields'] .= ', SUM( ABS( DATEDIFF( cp.completed_at, cp.started_at ) ) + 1 ) AS days_to_completion';
		// We consider the course as completed if there is a progress row with status complete and both dates present.
		$clauses['fields']  .= ', COUNT(cp.id) AS count_of_completions';
		$clauses['join']    .= " LEFT JOIN {$progress_table} cp ON cp.post_id = {$wpdb->posts}.ID";
		$clauses['join']    .= " AND cp.type = 'course'";
		$clauses['join']    .= " AND cp.status = 'complete'";
		$clauses['join']    .= ' AND cp.started_at IS NOT NULL';
		$clauses['join']    .= ' AND cp.completed_at IS NOT NULL';
		$clauses['groupby'] .= " {$wpdb->posts}.ID";

		return $clauses;
	}

	/**
	 * Get the total number of items found for the last query.
	 *
	 * @since $$next-version$$
	 *
	 * @return int
	 */
	public function get_last_total_items(): int {
		return $this->last_total_items;
	}
}

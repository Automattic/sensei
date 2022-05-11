<?php
/**
 * File containing the Sensei_Reports_Overview_Data_Provider_Courses class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Class Sensei_Reports_Overview_Data_Provider_Courses
 *
 * @since 4.3.0
 */
class Sensei_Reports_Overview_Data_Provider_Courses implements Sensei_Reports_Overview_Data_Provider_Interface {

	/**
	 * Total number of courses found with given criteria.
	 *
	 * @var int Total number of items
	 */
	private $last_total_items = 0;

	/**
	 * Array of all the students ids.
	 *
	 * @var array All the student ids.
	 */
	private $all_item_ids = [];

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
			'orderby'          => $filters['orderby'] ?? '',
			'order'            => $filters['order'] ?? 'ASC',
			'suppress_filters' => 0,
		);

		if ( isset( $filters['search'] ) ) {
			$course_args['s'] = $filters['search'];
		}

		add_filter( 'posts_clauses', [ $this, 'filter_courses_by_last_activity' ] );
		add_filter( 'posts_clauses', [ $this, 'add_days_to_completion_to_courses_queries' ] );

		if ( 'count_of_completions' === $course_args['orderby'] ) {
			add_filter( 'posts_orderby', array( $this, 'add_orderby_custom_field_to_query' ), 10, 2 );
		}
		$course_args   = apply_filters( 'sensei_analysis_overview_filter_courses', $course_args );
		$courses_query = new WP_Query( $course_args );

		remove_filter( 'posts_orderby', array( $this, 'add_orderby_custom_field_to_query' ), 10, 2 );

		$all_courses_query  = new WP_Query(
			array_merge(
				$course_args,
				[
					'posts_per_page' => -1,
					'fields'         => 'ids',
				]
			)
		);
		$this->all_item_ids = $all_courses_query->posts;
		remove_filter( 'posts_clauses', [ $this, 'filter_courses_by_last_activity' ] );
		remove_filter( 'posts_clauses', [ $this, 'add_days_to_completion_to_courses_queries' ] );

		$this->last_total_items = $courses_query->found_posts;

		return $courses_query->posts;
	}

	/**
	 * Order query based on the custom field.
	 *
	 * @since  4.3.0
	 * @access private
	 *
	 * @param array  $args Arguments Old orderby arguments.
	 * @param object $query Query.
	 */
	public function add_orderby_custom_field_to_query( $args, $query ) {
		global $wpdb;

		return $wpdb->prepare(
			'%1s %1s', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- not needed.
			$query->query_vars['orderby'],
			$query->query_vars['order']
		);
	}

	/**
	 * Filter the courses by last activity start/end date.
	 *
	 * @access private
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 *
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function filter_courses_by_last_activity( array $clauses ): array {
		global $wpdb;

		if ( ! $this->date_from && ! $this->date_to ) {
			return $clauses;
		}
		// Fetch the lessons within the expected last activity range.
		$lessons_query = "SELECT cm.comment_post_id lesson_id, MAX(cm.comment_date_gmt) as comment_date_gmt
			FROM {$wpdb->comments} cm
			WHERE cm.comment_approved IN ('complete', 'passed', 'graded')
			AND cm.comment_type = 'sensei_lesson_status'";

		// Filter by start date.
		if ( $this->date_from ) {
			$lessons_query .= $wpdb->prepare(
				' AND cm.comment_date_gmt >= %s',
				$this->date_from
			);
		}
		$lessons_query .= ' GROUP BY cm.comment_post_id';

		// Fetch the course IDs associated with those lessons.
		$course_query = "SELECT DISTINCT(pm.meta_value) course_id
		FROM {$wpdb->postmeta} pm JOIN ({$lessons_query}) cm
		ON cm.lesson_id = pm.post_id
		AND pm.meta_key = '_lesson_course'
		GROUP BY pm.meta_value
		";

		// Filter by end date.
		if ( $this->date_to ) {
			$course_query .= $wpdb->prepare(
				' HAVING MAX(cm.comment_date_gmt) <= %s',
				$this->date_to
			);
		}

		$clauses['where'] .= " AND {$wpdb->posts}.ID IN ({$course_query})";

		return $clauses;
	}


	/**
	 * Add the sum of days taken by each student to complete a course and the number of completions for each course.
	 *
	 * @access private
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 *
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_days_to_completion_to_courses_queries( $clauses ) {
		global $wpdb;

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

		return $clauses;
	}

	/**
	 * Get the total number of items found for the last query.
	 *
	 * @return int
	 */
	public function get_last_total_items(): int {
		return $this->last_total_items;
	}

	/**
	 * Get ids of items from the last query.
	 *
	 * @since 4.5.0
	 *
	 * @return array
	 */
	public function get_all_item_ids(): array {
		return $this->all_item_ids;
	}
}

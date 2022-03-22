<?php

class Sensei_Reports_Overview_DataProvider_Courses implements Sensei_Reports_Overview_DataProvider_Interface {

	private $last_total_items = 0;

	// @fixme these two fields are kind of a hack
	private $date_from;
	private $date_to;

	public function get_items( array $args, $date_from = null, $date_to = null ): array {
		$this->date_from = $date_from;
		$this->date_to   = $date_to;
		$course_args = array(
			'post_type'        => $this->post_type,
			'post_status'      => array( 'publish', 'private' ),
			'posts_per_page'   => $args['number'],
			'offset'           => $args['offset'],
			'orderby'          => $args['orderby'],
			'order'            => $args['order'],
			'suppress_filters' => 0,
		);

		if ( isset( $args['search'] ) ) {
			$course_args['s'] = $args['search'];
		}

		add_filter( 'posts_clauses', [ $this, 'filter_courses_by_last_activity' ] );
		add_filter( 'posts_clauses', [ $this, 'add_days_to_completion_to_courses_queries' ] );
		$courses_query = new WP_Query( apply_filters( 'sensei_analysis_overview_filter_courses', $course_args ) );
		remove_filter( 'posts_clauses', [ $this, 'filter_courses_by_last_activity' ] );
		remove_filter( 'posts_clauses', [ $this, 'add_days_to_completion_to_courses_queries' ] );

		$this->last_total_items = $courses_query->found_posts;

		return $courses_query->posts;
	}

	/**
	 * Filter the courses by last activity start/end date.
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 *
	 * @return array Modified associative array of the clauses for the query.
	 * @since  4.2.0
	 * @access private
	 */
	public function filter_courses_by_last_activity( array $clauses ): array {
		global $wpdb;

		$start_date = $this->date_from;
		$end_date   = $this->date_to;

		if ( ! $start_date && ! $end_date ) {
			return $clauses;
		}
		// Fetch the lessons within the expected last activity range.
		$lessons_query = "SELECT cm.comment_post_id lesson_id, MAX(cm.comment_date_gmt) as comment_date_gmt
			FROM {$wpdb->comments} cm
			WHERE cm.comment_approved IN ('complete', 'passed', 'graded')
			AND cm.comment_type = 'sensei_lesson_status'";
		// Filter by start date.
		if ( $start_date ) {
			$lessons_query .= $wpdb->prepare(
				' AND cm.comment_date_gmt >= %s',
				$start_date
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
		if ( $end_date ) {
			$course_query .= $wpdb->prepare(
				' HAVING MAX(cm.comment_date_gmt) <= %s',
				$end_date
			);
		}
		$clauses['where'] .= " AND {$wpdb->posts}.ID IN ({$course_query})";

		return $clauses;
	}


	/**
	 * Add the sum of days taken by each student to complete a course and the number of completions for each course.
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 *
	 * @return array Modified associative array of the clauses for the query.
	 * @since  4.2.0
	 * @access private
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

	public function get_last_total_items(): int {
		return $this->last_total_items;
	}
}

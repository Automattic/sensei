<?php
/**
 * File containing the Sensei_Reports_Overview_Data_Provider_Courses_Migrated class.
 *
 * @package sensei
 */

use Sensei\Internal\Services\Progress_Query_Service_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Sensei_Reports_Overview_Data_Provider_Courses_Migrated
 *
 * Migrated version of Sensei_Reports_Overview_Data_Provider_Courses that delegates
 * progress-related SQL queries to a Progress_Query_Service_Interface implementation.
 * This supports both comments-based and HPPS tables-based storage transparently.
 *
 * @since $$next-version$$
 */
class Sensei_Reports_Overview_Data_Provider_Courses_Migrated implements Sensei_Reports_Overview_Data_Provider_Interface {

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
	 * The progress query service.
	 *
	 * @var Progress_Query_Service_Interface
	 */
	private Progress_Query_Service_Interface $progress_query_service;

	/**
	 * Constructor.
	 *
	 * @since $$next-version$$
	 *
	 * @param Progress_Query_Service_Interface $progress_query_service The progress query service.
	 */
	public function __construct( Progress_Query_Service_Interface $progress_query_service ) {
		$this->progress_query_service = $progress_query_service;
	}

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
			'fields'           => $filters['fields'] ?? '',
			'orderby'          => $filters['orderby'] ?? '',
			'order'            => $filters['order'] ?? 'ASC',
			'suppress_filters' => 0,
		);

		if ( isset( $filters['search'] ) ) {
			$course_args['s'] = $filters['search'];
		}

		add_filter( 'posts_clauses', array( $this, 'add_last_activity_to_courses_query' ), 10, 2 );
		add_filter( 'posts_clauses', array( $this, 'add_days_to_completion_to_courses_query' ), 10, 2 );
		add_filter( 'posts_clauses', array( $this, 'filter_courses_by_last_activity' ) );

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
		remove_filter( 'posts_clauses', array( $this, 'filter_courses_by_last_activity' ) );
		remove_filter( 'posts_clauses', array( $this, 'add_days_to_completion_to_courses_query' ), 10 );
		remove_filter( 'posts_clauses', array( $this, 'add_last_activity_to_courses_query' ), 10 );
		remove_filter( 'posts_orderby', array( $this, 'add_orderby_custom_field_to_query' ), 10, 2 );

		$this->last_total_items = $courses_query->found_posts;

		return $courses_query->posts;
	}

	/**
	 * Order query based on the custom field.
	 *
	 * @since  4.3.0
	 * @access private
	 *
	 * @param array  $args  Old orderby arguments.
	 * @param object $query Query.
	 * @return string Modified orderby clause.
	 */
	public function add_orderby_custom_field_to_query( $args, $query ) {
		return $query->query_vars['orderby'] . ' ' . $query->query_vars['order'];
	}

	/**
	 * Add last activity date for each course.
	 *
	 * Delegates to the progress query service.
	 *
	 * @since $$next-version$$
	 * @access private
	 *
	 * @param array    $clauses  Associative array of the clauses for the query.
	 * @param WP_Query $wp_query The WP_Query instance.
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_last_activity_to_courses_query( array $clauses, WP_Query $wp_query ): array {
		return $this->progress_query_service->add_last_activity_to_courses_clauses( $clauses, $wp_query );
	}

	/**
	 * Filter the courses by last activity start/end date.
	 *
	 * Delegates to the progress query service.
	 *
	 * @since $$next-version$$
	 * @access private
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function filter_courses_by_last_activity( array $clauses ): array {
		return $this->progress_query_service->filter_courses_by_last_activity(
			$clauses,
			$this->date_from ?? '',
			$this->date_to ?? ''
		);
	}

	/**
	 * Add the sum of days taken by each student to complete a course and the number of completions for each course.
	 *
	 * Delegates to the progress query service.
	 *
	 * @since $$next-version$$
	 * @access private
	 *
	 * @param array    $clauses  Associative array of the clauses for the query.
	 * @param WP_Query $wp_query The WP_Query instance.
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_days_to_completion_to_courses_query( array $clauses, WP_Query $wp_query ): array {
		return $this->progress_query_service->add_days_to_completion_to_courses_clauses( $clauses, $wp_query );
	}

	/**
	 * Get the total number of items found for the last query.
	 *
	 * @return int
	 */
	public function get_last_total_items(): int {
		return $this->last_total_items;
	}
}

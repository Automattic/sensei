<?php
/**
 * File containing the Sensei_Reports_Overview_Data_Provider_Courses class.
 *
 * @package sensei
 */

use Sensei\Internal\Services\Progress_Query_Service_Factory;
use Sensei\Internal\Services\Progress_Clauses_Service_Interface;

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
	 * The progress clauses service.
	 *
	 * @var Progress_Clauses_Service_Interface
	 */
	private Progress_Clauses_Service_Interface $progress_clauses_service;

	/**
	 * Constructor.
	 *
	 * @since 4.26.0
	 *
	 * @param Progress_Clauses_Service_Interface|null $progress_clauses_service The progress clauses service.
	 */
	public function __construct( ?Progress_Clauses_Service_Interface $progress_clauses_service = null ) {
		$this->progress_clauses_service = $progress_clauses_service
			?? ( new Progress_Query_Service_Factory() )->create_clauses_service();
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

		add_filter( 'posts_clauses', array( $this, 'add_last_activity_to_courses_query' ) );
		add_filter( 'posts_clauses', array( $this, 'add_days_to_completion_to_courses_query' ) );
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
		remove_filter( 'posts_clauses', array( $this, 'add_days_to_completion_to_courses_query' ) );
		remove_filter( 'posts_clauses', array( $this, 'add_last_activity_to_courses_query' ) );
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
	 * @param array  $args Arguments Old orderby arguments.
	 * @param object $query Query.
	 */
	public function add_orderby_custom_field_to_query( $args, $query ) {
		return $query->query_vars['orderby'] . ' ' . $query->query_vars['order'];
	}

	/**
	 * Add last activity date for each course.
	 *
	 * @since  4.4.1
	 * @access private
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 *
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_last_activity_to_courses_query( array $clauses ): array {
		return $this->progress_clauses_service->add_last_activity_to_courses_clauses( $clauses );
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
		return $this->progress_clauses_service->filter_courses_by_last_activity(
			$clauses,
			$this->date_from ?? '',
			$this->date_to ?? ''
		);
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
	public function add_days_to_completion_to_courses_query( array $clauses ): array {
		return $this->progress_clauses_service->add_days_to_completion_to_courses_clauses( $clauses );
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

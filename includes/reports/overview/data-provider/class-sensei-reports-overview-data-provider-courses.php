<?php
/**
 * File containing the Sensei_Reports_Overview_Data_Provider_Courses class.
 *
 * @package sensei
 */

use Sensei\Internal\Services\Progress_Query_Service_Factory;
use Sensei\Internal\Services\Progress_Clauses_Service_Interface;
use Sensei\Internal\Services\Utils;

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
	 * Storage-neutral course metrics.
	 *
	 * @var Sensei_Reports_Overview_Service_Courses
	 */
	private Sensei_Reports_Overview_Service_Courses $courses_service;

	/**
	 * Completion counts used for ordering the current query.
	 *
	 * @var array<int, int>
	 */
	private array $completion_counts = array();

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
	 * @param Progress_Clauses_Service_Interface|null      $progress_clauses_service The progress clauses service.
	 * @param Sensei_Reports_Overview_Service_Courses|null $courses_service The course metrics service.
	 */
	public function __construct( ?Progress_Clauses_Service_Interface $progress_clauses_service = null, ?Sensei_Reports_Overview_Service_Courses $courses_service = null ) {
		$this->courses_service          = $courses_service ?? new Sensei_Reports_Overview_Service_Courses();
		$this->progress_clauses_service = $progress_clauses_service
			?? ( new Progress_Query_Service_Factory( Sensei()->progress_storage_configuration ) )->create_clauses_service();
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
			'post_status'      => Utils::REPORTS_POST_STATUSES,
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

		/**
		 * Filter the courses query arguments.
		 *
		 * @hook sensei_analysis_overview_filter_courses
		 *
		 * @param {array} $course_args Array of arguments for the courses query.
		 * @return {array} Filtered array of arguments for the courses query.
		 */
		$course_args             = apply_filters( 'sensei_analysis_overview_filter_courses', $course_args );
		$this->completion_counts = array();
		if ( 'count_of_completions' === $course_args['orderby'] ) {
			// Count all candidate courses before pagination, using exactly the same metric as report rows.
			$all_course_args = array_merge(
				$course_args,
				array(
					'posts_per_page' => -1,
					'offset'         => 0,
					'fields'         => 'ids',
					'orderby'        => 'ID',
					'no_found_rows'  => true,
				)
			);
			/**
			 * Course IDs requested by the candidate query.
			 *
			 * @var int[] $candidate_ids
			 */
			$candidate_ids           = get_posts( $all_course_args );
			$this->completion_counts = $this->courses_service->get_completion_counts_by_course( $candidate_ids );
		}

		add_filter( 'posts_clauses', array( $this, 'add_last_activity_to_courses_query' ) );
		add_filter( 'posts_clauses', array( $this, 'add_days_to_completion_to_courses_query' ) );
		add_filter( 'posts_clauses', array( $this, 'filter_courses_by_last_activity' ) );

		if ( 'count_of_completions' === $course_args['orderby'] ) {
			add_filter( 'posts_orderby', array( $this, 'add_orderby_custom_field_to_query' ), 10, 2 );
		}

		$courses_query = new WP_Query( $course_args );

		remove_filter( 'posts_orderby', array( $this, 'add_orderby_custom_field_to_query' ), 10, 2 );
		remove_filter( 'posts_clauses', array( $this, 'filter_courses_by_last_activity' ) );
		remove_filter( 'posts_clauses', array( $this, 'add_days_to_completion_to_courses_query' ) );
		remove_filter( 'posts_clauses', array( $this, 'add_last_activity_to_courses_query' ) );
		remove_filter( 'posts_orderby', array( $this, 'add_orderby_custom_field_to_query' ), 10, 2 );

		$this->last_total_items = $courses_query->found_posts;
		if ( ! in_array( $course_args['fields'], array( 'ids', 'id=>parent' ), true ) ) {
			/**
			 * Full course posts requested by the report query.
			 *
			 * @var WP_Post[] $posts
			 */
			$posts  = $courses_query->posts;
			$counts = ! empty( $this->completion_counts ) ? $this->completion_counts : $this->courses_service->get_completion_counts_by_course( array_map( 'intval', wp_list_pluck( $posts, 'ID' ) ) );
			foreach ( $posts as $course ) {
				/**
				 * Populate the existing extra Reports query field.
				 *
				 * @psalm-suppress UndefinedPropertyAssignment
				 */
				$course->count_of_completions = (string) ( $counts[ (int) $course->ID ] ?? 0 );
			}
		}

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
		if ( 'count_of_completions' !== $query->query_vars['orderby'] ) {
			return $query->query_vars['orderby'] . ' ' . $query->query_vars['order'];
		}

		global $wpdb;
		$cases = '';
		foreach ( $this->completion_counts as $course_id => $count ) {
			if ( $count ) {
				$cases .= $wpdb->prepare( ' WHEN %d THEN %d', $course_id, $count );
			}
		}
		$expression = $cases ? "CASE {$wpdb->posts}.ID {$cases} ELSE 0 END" : '0 + 0';
		$order      = 'DESC' === strtoupper( $query->query_vars['order'] ) ? 'DESC' : 'ASC';

		return "$expression $order, {$wpdb->posts}.ID ASC";
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
		return $this->progress_clauses_service->add_last_activity_to_courses_clauses(
			$clauses,
			array( 'exclude_user_login_prefixes' => Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES )
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

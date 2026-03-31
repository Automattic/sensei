<?php
/**
 * File containing the Sensei_Reports_Overview_Data_Provider_Lessons class.
 *
 * @package sensei
 */

use Sensei\Internal\Services\Progress_Query_Service_Factory;
use Sensei\Internal\Services\Progress_Clauses_Service_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Class Sensei_Reports_Overview_Data_Provider_Lessons
 *
 * @since 4.3.0
 */
class Sensei_Reports_Overview_Data_Provider_Lessons implements Sensei_Reports_Overview_Data_Provider_Interface {
	/**
	 * Total number of lessons found with given criteria.
	 *
	 * @var int Total number of items
	 */
	private $last_total_items = 0;

	/**
	 * Sensei course related services.
	 *
	 * @var Sensei_Course
	 */
	private $course;

	/**
	 * The progress clauses service.
	 *
	 * @var Progress_Clauses_Service_Interface
	 */
	private Progress_Clauses_Service_Interface $progress_clauses_service;

	/**
	 * Constructor.
	 *
	 * @param Sensei_Course                           $course                   Sensei course related services.
	 * @param Progress_Clauses_Service_Interface|null $progress_clauses_service The progress clauses service.
	 */
	public function __construct( Sensei_Course $course, ?Progress_Clauses_Service_Interface $progress_clauses_service = null ) {
		$this->course                   = $course;
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
		if ( ! $filters['course_id'] ) {
			return [];
		}
		// Fetching the lesson ids beforehand because joining both postmeta and comment + commentmeta makes WP_Query very slow.
		$course_lessons = $this->course->course_lessons( $filters['course_id'], 'any', 'ids' );
		if ( empty( $course_lessons ) ) {
			return [];
		}

		$lessons_args = array(
			'post_type'        => 'lesson',
			'post_status'      => array( 'publish', 'private' ),
			'posts_per_page'   => $filters['number'],
			'offset'           => $filters['offset'],
			'orderby'          => $filters['orderby'] ?? '',
			'order'            => $filters['order'] ?? 'ASC',
			'post__in'         => $course_lessons,
			'suppress_filters' => 0,
		);

		if ( isset( $filters['search'] ) ) {
			$lessons_args['s'] = $filters['search'];
		}
		add_filter( 'posts_clauses', [ $this, 'add_days_to_complete_to_lessons_query' ] );
		add_filter( 'posts_clauses', [ $this, 'add_last_activity_to_lessons_query' ] );

		// Using WP_Query as get_posts() doesn't support 'found_posts'.

		/*
		 * Filter the arguments for the query used to fetch the lessons for the overview report.
		 *
		 * @hook sensei_analysis_overview_filter_lessons
		 *
		 * @param {array} $lessons_args Arguments for the query.
		 * @return {array} Filtered arguments for the query.
		 */
		$lessons_query = new WP_Query( apply_filters( 'sensei_analysis_overview_filter_lessons', $lessons_args ) );
		remove_filter( 'posts_clauses', [ $this, 'add_last_activity_to_lessons_query' ] );
		remove_filter( 'posts_clauses', [ $this, 'add_days_to_complete_to_lessons_query' ] );
		$this->last_total_items = $lessons_query->found_posts;
		return $lessons_query->posts;
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
	 * Add the sum of days taken by each student to complete a lesson with returning lesson row.
	 *
	 * @since  4.3.0
	 * @access private
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 *
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_days_to_complete_to_lessons_query( array $clauses ): array {
		return $this->progress_clauses_service->add_days_to_completion_to_lessons_clauses( $clauses );
	}

	/**
	 * Add the `last_activity` field to the query.
	 *
	 * @since  4.4.1
	 * @access private
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 *
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_last_activity_to_lessons_query( array $clauses ): array {
		return $this->progress_clauses_service->add_last_activity_to_lessons_clauses( $clauses );
	}
}

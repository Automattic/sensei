<?php
/**
 * File containing the Sensei_Reports_Overview_Data_Provider_Lessons class.
 *
 * @package sensei
 */

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
	 * Constructor
	 *
	 * @param Sensei_Course $course Sensei course related services.
	 */
	public function __construct( Sensei_Course $course ) {
		$this->course = $course;
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
		global $wpdb;

		$clauses['fields'] .= ", (SELECT SUM( ABS( DATEDIFF( STR_TO_DATE( {$wpdb->commentmeta}.meta_value, '%Y-%m-%d %H:%i:%s' ), {$wpdb->comments}.comment_date )) + 1 ) as days_to_complete";
		$clauses['fields'] .= " FROM {$wpdb->comments}";
		$clauses['fields'] .= " INNER JOIN {$wpdb->commentmeta} ON {$wpdb->comments}.comment_ID = {$wpdb->commentmeta}.comment_id";
		$clauses['fields'] .= " WHERE {$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID";
		$clauses['fields'] .= " AND {$wpdb->comments}.comment_type IN ('sensei_lesson_status')";
		$clauses['fields'] .= " AND {$wpdb->comments}.comment_approved IN ( 'complete', 'graded', 'passed', 'failed', 'ungraded' )";
		$clauses['fields'] .= " AND {$wpdb->commentmeta}.meta_key = 'start') as days_to_complete";

		return $clauses;
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
		global $wpdb;

		$clauses['fields'] .= ", (
			SELECT MAX({$wpdb->comments}.comment_date_gmt)
			FROM {$wpdb->comments}
			WHERE {$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID
			AND {$wpdb->comments}.comment_approved IN ('complete', 'passed', 'graded')
			AND {$wpdb->comments}.comment_type = 'sensei_lesson_status'
			ORDER BY {$wpdb->comments}.comment_date_gmt DESC
		) AS last_activity_date";

		return $clauses;
	}
}

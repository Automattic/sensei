<?php
/**
 * File containing the Comments_Based_Progress_Clauses_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

use Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress_Interface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress_Interface;
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Comments_Based_Progress_Clauses_Service.
 *
 * Comments-based implementation of the Progress_Clauses_Service_Interface.
 * Queries wp_comments and wp_commentmeta for progress data.
 *
 * @internal
 *
 * @since 4.26.0
 */
class Comments_Based_Progress_Clauses_Service implements Progress_Clauses_Service_Interface {

	/**
	 * The WordPress database object.
	 *
	 * @var \wpdb
	 */
	private \wpdb $wpdb;

	/**
	 * Constructor.
	 *
	 * @since 4.26.0
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
	 * @since 4.26.0
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_last_activity_to_courses_clauses( array $clauses ): array {
		$wpdb = $this->wpdb;

		$complete = Lesson_Progress_Interface::STATUS_COMPLETE;
		$passed   = Quiz_Progress_Interface::STATUS_PASSED;
		$graded   = Quiz_Progress_Interface::STATUS_GRADED;

		$lessons_query = "SELECT c.comment_post_id lesson_id, MAX(c.comment_date_gmt) as comment_date_gmt
			FROM {$wpdb->comments} c
			WHERE c.comment_approved IN ('{$complete}', '{$passed}', '{$graded}')
			AND c.comment_type = 'sensei_lesson_status'
			GROUP BY c.comment_post_id";

		$course_query = "SELECT pm.meta_value AS course_id, MAX(lq.comment_date_gmt) AS comment_date_gmt
		FROM {$wpdb->postmeta} pm JOIN ({$lessons_query}) lq
		ON lq.lesson_id = pm.post_id
		AND pm.meta_key = '_lesson_course'
		GROUP BY pm.meta_value
		";

		$clauses['fields'] .= ', la.comment_date_gmt AS last_activity_date';
		$clauses['join']   .= " LEFT JOIN ({$course_query}) AS la ON la.course_id = {$wpdb->posts}.ID";

		return $clauses;
	}

	/**
	 * Modify WP_Query clauses to add days-to-completion data to course posts.
	 *
	 * Joins wp_comments and wp_commentmeta to calculate the sum of days taken
	 * by each student to complete a course and the number of completions.
	 *
	 * @since 4.26.0
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_days_to_completion_to_courses_clauses( array $clauses ): array {
		$wpdb = $this->wpdb;

		// Get the number of days to complete a course: `days to complete = complete date - start date + 1`.
		$clauses['fields'] .= ", SUM(  ABS( DATEDIFF( {$wpdb->comments}.comment_date, STR_TO_DATE( {$wpdb->commentmeta}.meta_value, '%Y-%m-%d %H:%i:%s' ) ) ) + 1 ) AS days_to_completion";
		// We consider the course as completed if there is a comment and corresponding meta for it.
		$clauses['fields']  .= ", COUNT({$wpdb->commentmeta}.comment_id) AS count_of_completions";
		$clauses['join']    .= " LEFT JOIN {$wpdb->comments} ON {$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID";
		$clauses['join']    .= " AND {$wpdb->comments}.comment_type IN ('sensei_course_status')";
		$complete            = Course_Progress_Interface::STATUS_COMPLETE;
		$clauses['join']    .= " AND {$wpdb->comments}.comment_approved IN ( '{$complete}' )";
		$clauses['join']    .= " LEFT JOIN {$wpdb->commentmeta} ON {$wpdb->comments}.comment_ID = {$wpdb->commentmeta}.comment_id";
		$clauses['join']    .= " AND {$wpdb->commentmeta}.meta_key = 'start'";
		$clauses['groupby'] .= " {$wpdb->posts}.ID";

		return $clauses;
	}

	/**
	 * Modify WP_Query clauses to filter courses by last activity date range.
	 *
	 * @since 4.26.0
	 *
	 * @param array  $clauses Associative array of the clauses for the query.
	 * @param string $from    Start date for filtering (empty string for no start date).
	 * @param string $to      End date for filtering (empty string for no end date).
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function filter_courses_by_last_activity( array $clauses, string $from = '', string $to = '' ): array {
		$wpdb = $this->wpdb;

		if ( $from ) {
			$clauses['where'] .= $wpdb->prepare(
				' AND la.comment_date_gmt >= %s',
				$from
			);
		}

		if ( $to ) {
			$clauses['where'] .= $wpdb->prepare(
				' AND la.comment_date_gmt <= %s',
				$to
			);
		}

		return $clauses;
	}

	/**
	 * Modify WP_Query clauses to add last activity date to lesson posts.
	 *
	 * @since 4.26.0
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_last_activity_to_lessons_clauses( array $clauses ): array {
		$wpdb = $this->wpdb;

		$complete = Lesson_Progress_Interface::STATUS_COMPLETE;
		$passed   = Quiz_Progress_Interface::STATUS_PASSED;
		$graded   = Quiz_Progress_Interface::STATUS_GRADED;

		$clauses['fields'] .= ", (
			SELECT MAX({$wpdb->comments}.comment_date_gmt)
			FROM {$wpdb->comments}
			WHERE {$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID
			AND {$wpdb->comments}.comment_approved IN ('{$complete}', '{$passed}', '{$graded}')
			AND {$wpdb->comments}.comment_type = 'sensei_lesson_status'
		) AS last_activity_date";

		return $clauses;
	}

	/**
	 * Modify WP_Query clauses to add days-to-complete data to lesson posts.
	 *
	 * @since 4.26.0
	 *
	 * @param array $clauses Associative array of the clauses for the query.
	 * @return array Modified associative array of the clauses for the query.
	 */
	public function add_days_to_completion_to_lessons_clauses( array $clauses ): array {
		$wpdb           = $this->wpdb;
		$has_completion = "'" . implode( "','", Grading_Item::STATUSES_WITH_COMPLETION_DATE ) . "'";

		$clauses['fields'] .= ", (SELECT SUM( ABS( DATEDIFF( STR_TO_DATE( {$wpdb->commentmeta}.meta_value, '%Y-%m-%d %H:%i:%s' ), {$wpdb->comments}.comment_date )) + 1 ) as days_to_complete";
		$clauses['fields'] .= " FROM {$wpdb->comments}";
		$clauses['fields'] .= " INNER JOIN {$wpdb->commentmeta} ON {$wpdb->comments}.comment_ID = {$wpdb->commentmeta}.comment_id";
		$clauses['fields'] .= " WHERE {$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID";
		$clauses['fields'] .= " AND {$wpdb->comments}.comment_type IN ('sensei_lesson_status')";
		$clauses['fields'] .= " AND {$wpdb->comments}.comment_approved IN ( $has_completion )";
		$clauses['fields'] .= " AND {$wpdb->commentmeta}.meta_key = 'start') as days_to_complete";

		return $clauses;
	}
}

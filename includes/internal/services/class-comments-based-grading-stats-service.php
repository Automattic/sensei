<?php
/**
 * File containing the Comments_Based_Grading_Stats_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Comments_Based_Grading_Stats_Service.
 *
 * Comments-based (legacy) implementation of grading statistics.
 * Queries wp_comments joined with wp_commentmeta for grade data.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Comments_Based_Grading_Stats_Service implements Grading_Stats_Service_Interface {

	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private \wpdb $wpdb;

	/**
	 * Constructor.
	 *
	 * @since $$next-version$$
	 *
	 * @param \wpdb $wpdb WordPress database object.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Get grade count and sum, with optional filters.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args {
	 *     Optional filters.
	 *
	 *     @type int   $user_id   Filter by user.
	 *     @type int   $lesson_id Filter by lesson (post_id).
	 *     @type int[] $post__in  Filter by lesson IDs.
	 * }
	 * @return array { count: int, sum: float }
	 */
	public function get_grade_totals( array $args = array() ): array {
		$wpdb = $this->wpdb;

		$query = $wpdb->prepare( "SELECT COUNT(*) AS count, COALESCE( SUM( cm.meta_value ), 0 ) AS sum FROM %i c INNER JOIN %i cm ON c.comment_ID = cm.comment_id WHERE c.comment_type = 'sensei_lesson_status' AND c.comment_approved IN ( 'graded', 'passed', 'failed' ) AND cm.meta_key = 'grade'", $wpdb->comments, $wpdb->commentmeta );

		$query .= $this->build_user_filter( $args );
		$query .= $this->build_post_filter( $args );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared in advance. Caching handled by callers.
		$row = $wpdb->get_row( $query );
		Utils::log_query_error( $wpdb, 'Comments-based grade totals' );

		if ( ! $row ) {
			return array(
				'count' => 0,
				'sum'   => 0.0,
			);
		}

		return array(
			'count' => (int) $row->count,
			'sum'   => (float) $row->sum,
		);
	}

	/**
	 * Average grade across courses (AVG of per-course AVGs).
	 * Only includes lessons with quizzes that have been graded.
	 *
	 * @since $$next-version$$
	 *
	 * @param int[] $course_ids Optional. Filter by courses. Empty = all.
	 * @return float
	 */
	public function get_courses_average_grade( array $course_ids = array() ): float {
		$wpdb = $this->wpdb;

		$course_filter = '';
		if ( ! empty( $course_ids ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $course_ids ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			$course_filter = $wpdb->prepare( " AND course.meta_value IN ( $placeholders )", $course_ids );
		}

		/**
		 * The subquery calculates the average grade per course, and the outer query
		 * then calculates the average grade of all courses. To be included in the
		 * calculation, a lesson must:
		 *   - Have a status of 'graded', 'passed' or 'failed'.
		 *   - Have grade data.
		 *   - Be associated with a course.
		 *   - Have quiz questions.
		 */
		$query  = $wpdb->prepare(
			"SELECT AVG(course_average) AS courses_average
			FROM (
				SELECT AVG(cm.meta_value) AS course_average
				FROM %i c
				INNER JOIN %i cm ON c.comment_ID = cm.comment_id
				INNER JOIN %i course ON c.comment_post_ID = course.post_id
				INNER JOIN %i has_questions ON c.comment_post_ID = has_questions.post_id
				INNER JOIN %i p ON p.ID = course.meta_value
				WHERE c.comment_type = 'sensei_lesson_status'
					AND c.comment_approved IN ( 'graded', 'passed', 'failed' )
					AND cm.meta_key = 'grade'
					AND course.meta_key = '_lesson_course'
					AND course.meta_value <> ''
					AND has_questions.meta_key = '_quiz_has_questions'",
			$wpdb->comments,
			$wpdb->commentmeta,
			$wpdb->postmeta,
			$wpdb->postmeta,
			$wpdb->posts
		);
		$query .= $course_filter;
		$query .= ' GROUP BY course.meta_value ) averages_by_course';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared above. Caching handled by callers.
		$result = $wpdb->get_row( $query );
		Utils::log_query_error( $wpdb, 'Comments-based courses average grade' );

		return floatval( $result->courses_average ?? 0 );
	}

	/**
	 * Average grade filtered by user IDs.
	 *
	 * @since $$next-version$$
	 *
	 * @param int[] $user_ids User IDs to include.
	 * @return float
	 */
	public function get_users_average_grade( array $user_ids ): float {
		if ( empty( $user_ids ) ) {
			return 0.0;
		}

		$wpdb         = $this->wpdb;
		$placeholders = implode( ', ', array_fill( 0, count( $user_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Placeholders created dynamically. Caching handled by callers.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT SUM( cm.meta_value ) AS grade_sum, COUNT( * ) AS grade_count
				FROM %i c
				INNER JOIN %i cm ON c.comment_ID = cm.comment_id
				WHERE c.comment_type = 'sensei_lesson_status'
					AND c.comment_approved IN ( 'graded', 'passed', 'failed' )
					AND cm.meta_key = 'grade'
					AND c.user_id IN ( $placeholders )",
				array_merge( array( $wpdb->comments, $wpdb->commentmeta ), $user_ids )
			)
		);
		// phpcs:enable
		Utils::log_query_error( $wpdb, 'Comments-based users average grade' );

		if ( ! $row || ! $row->grade_count || '0' === $row->grade_count ) {
			return 0.0;
		}

		return (float) ( $row->grade_sum / $row->grade_count );
	}

	/**
	 * Build SQL clause for filtering by user ID.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Query arguments.
	 * @return string SQL clause.
	 */
	private function build_user_filter( array $args ): string {
		if ( ! empty( $args['user_id'] ) ) {
			return $this->wpdb->prepare( ' AND c.user_id = %d', $args['user_id'] );
		}

		return '';
	}

	/**
	 * Build SQL clause for filtering by post ID(s).
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Query arguments.
	 * @return string SQL clause.
	 */
	private function build_post_filter( array $args ): string {
		$wpdb = $this->wpdb;

		if ( ! empty( $args['lesson_id'] ) ) {
			return $wpdb->prepare( ' AND c.comment_post_ID = %d', $args['lesson_id'] );
		}

		if ( ! empty( $args['post__in'] ) && is_array( $args['post__in'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['post__in'] ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			return $wpdb->prepare( " AND c.comment_post_ID IN ( $placeholders )", $args['post__in'] );
		}

		return '';
	}
}

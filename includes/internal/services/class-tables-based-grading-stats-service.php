<?php
/**
 * File containing the Tables_Based_Grading_Stats_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Tables_Based_Grading_Stats_Service.
 *
 * Tables-based (HPPS) implementation of grading statistics.
 * Queries sensei_lms_progress and sensei_lms_quiz_submissions for grade data.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Tables_Based_Grading_Stats_Service implements Grading_Stats_Service_Interface {

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
	 * Get the progress table name.
	 *
	 * @since $$next-version$$
	 *
	 * @return string
	 */
	private function get_progress_table_name(): string {
		return $this->wpdb->prefix . 'sensei_lms_progress';
	}

	/**
	 * Get the quiz submissions table name.
	 *
	 * @since $$next-version$$
	 *
	 * @return string
	 */
	private function get_submissions_table_name(): string {
		return $this->wpdb->prefix . 'sensei_lms_quiz_submissions';
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
		$wpdb              = $this->wpdb;
		$table             = $this->get_progress_table_name();
		$submissions_table = $this->get_submissions_table_name();

		$query  = 'SELECT COUNT(*) AS count, COALESCE( SUM( qs.final_grade ), 0 ) AS sum';
		$query .= $wpdb->prepare( ' FROM %i p', $table );
		$query .= $wpdb->prepare( " LEFT JOIN %i q ON q.parent_post_id = p.post_id AND q.user_id = p.user_id AND q.type = 'quiz'", $table );
		$query .= $wpdb->prepare( ' LEFT JOIN %i qs ON qs.quiz_id = q.post_id AND qs.user_id = p.user_id', $submissions_table );
		$query .= " WHERE p.type = 'lesson'";
		$query .= " AND COALESCE( q.status, p.status ) IN ( 'graded', 'passed', 'failed' )";
		$query .= ' AND qs.final_grade IS NOT NULL';

		$query .= $this->build_user_filter( $args );
		$query .= $this->build_post_filter( $args );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared in advance. Caching handled by callers.
		$row = $wpdb->get_row( $query );
		Utils::log_query_error( $wpdb, 'Tables-based grade totals' );

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
		$wpdb              = $this->wpdb;
		$table             = $this->get_progress_table_name();
		$submissions_table = $this->get_submissions_table_name();

		$course_filter = '';
		if ( ! empty( $course_ids ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $course_ids ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			$course_filter = $wpdb->prepare( " AND p.parent_post_id IN ( $placeholders )", $course_ids );
		}

		/**
		 * Uses parent_post_id on lesson progress rows as the course ID.
		 * A quiz progress row existing (type='quiz') proves the lesson has a quiz.
		 * The subquery computes AVG grade per course; the outer query averages those.
		 */
		$query  = $wpdb->prepare(
			"SELECT AVG(course_average) AS courses_average
			FROM (
				SELECT AVG(qs.final_grade) AS course_average
				FROM %i p
				INNER JOIN %i q ON q.parent_post_id = p.post_id AND q.user_id = p.user_id AND q.type = 'quiz'
				INNER JOIN %i qs ON qs.quiz_id = q.post_id AND qs.user_id = p.user_id
				WHERE p.type = 'lesson'
					AND COALESCE( q.status, p.status ) IN ( 'graded', 'passed', 'failed' )
					AND qs.final_grade IS NOT NULL
					AND p.parent_post_id IS NOT NULL
					AND p.parent_post_id != 0",
			$table,
			$table,
			$submissions_table
		);
		$query .= $course_filter;
		$query .= ' GROUP BY p.parent_post_id ) averages_by_course';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared above. Caching handled by callers.
		$result = $wpdb->get_row( $query );
		Utils::log_query_error( $wpdb, 'Tables-based courses average grade' );

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

		$wpdb              = $this->wpdb;
		$table             = $this->get_progress_table_name();
		$submissions_table = $this->get_submissions_table_name();
		$placeholders      = implode( ', ', array_fill( 0, count( $user_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Placeholders created dynamically. Caching handled by callers.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT SUM( qs.final_grade ) AS grade_sum, COUNT( * ) AS grade_count
				FROM %i p
				LEFT JOIN %i q ON q.parent_post_id = p.post_id AND q.user_id = p.user_id AND q.type = 'quiz'
				LEFT JOIN %i qs ON qs.quiz_id = q.post_id AND qs.user_id = p.user_id
				WHERE p.type = 'lesson'
					AND COALESCE( q.status, p.status ) IN ( 'graded', 'passed', 'failed' )
					AND qs.final_grade IS NOT NULL
					AND p.user_id IN ( $placeholders )",
				array_merge( array( $table, $table, $submissions_table ), $user_ids )
			)
		);
		// phpcs:enable
		Utils::log_query_error( $wpdb, 'Tables-based users average grade' );

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
			return $this->wpdb->prepare( ' AND p.user_id = %d', $args['user_id'] );
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
			return $wpdb->prepare( ' AND p.post_id = %d', $args['lesson_id'] );
		}

		if ( ! empty( $args['post__in'] ) && is_array( $args['post__in'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['post__in'] ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			return $wpdb->prepare( " AND p.post_id IN ( $placeholders )", $args['post__in'] );
		}

		return '';
	}
}

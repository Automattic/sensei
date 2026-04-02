<?php
/**
 * File containing the Tables_Based_Grading_Stats_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress_Interface;

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
	 * Get the SQL IN clause for graded quiz statuses.
	 *
	 * @since $$next-version$$
	 *
	 * @return string SQL fragment like "( 'graded', 'passed', 'failed' )".
	 */
	private function get_graded_statuses_sql(): string {
		return sprintf(
			"( '%s', '%s', '%s' )",
			Quiz_Progress_Interface::STATUS_GRADED,
			Quiz_Progress_Interface::STATUS_PASSED,
			Quiz_Progress_Interface::STATUS_FAILED
		);
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
		$query .= $wpdb->prepare( ' FROM %i q', $table );
		$query .= $wpdb->prepare( ' INNER JOIN %i qs ON qs.quiz_id = q.post_id AND qs.user_id = q.user_id', $submissions_table );
		$query .= " WHERE q.type = 'quiz'";
		$query .= ' AND q.status IN ' . $this->get_graded_statuses_sql();
		$query .= ' AND qs.final_grade IS NOT NULL';

		$query .= $this->build_user_filter( $args );
		$query .= $this->build_post_filter( $args );

		/** Query result row. @var object|null $row */
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
		 * The subquery computes AVG grade per course; the outer query averages those.
		 */
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Statuses are from constants, not user input.
		$query = $wpdb->prepare(
			"SELECT AVG(course_average) AS courses_average
			FROM (
				SELECT AVG(qs.final_grade) AS course_average
				FROM %i p
				INNER JOIN %i q ON q.parent_post_id = p.post_id AND q.user_id = p.user_id AND q.type = 'quiz'
				INNER JOIN %i qs ON qs.quiz_id = q.post_id AND qs.user_id = p.user_id
				WHERE p.type = 'lesson'
					AND q.status IN " . $this->get_graded_statuses_sql() . '
					AND qs.final_grade IS NOT NULL
					AND p.parent_post_id IS NOT NULL
					AND p.parent_post_id != 0',
			$table,
			$table,
			$submissions_table
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		$query .= $course_filter;
		$query .= ' GROUP BY p.parent_post_id ) averages_by_course';

		/** Query result. @var object|null $result */
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared above. Caching handled by callers.
		$result = $wpdb->get_row( $query );
		Utils::log_query_error( $wpdb, 'Tables-based courses average grade' );

		if ( ! $result ) {
			return 0.0;
		}

		return floatval( $result->courses_average );
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

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Statuses from constants. Placeholders created dynamically. Caching handled by callers.
		/** Query result row. @var object|null $row */
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT SUM( qs.final_grade ) AS grade_sum, COUNT( * ) AS grade_count
				FROM %i q
				INNER JOIN %i qs ON qs.quiz_id = q.post_id AND qs.user_id = q.user_id
				WHERE q.type = 'quiz'
					AND q.status IN " . $this->get_graded_statuses_sql() . "
					AND qs.final_grade IS NOT NULL
					AND q.user_id IN ( $placeholders )",
				array_merge( array( $table, $submissions_table ), $user_ids )
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
			return $this->wpdb->prepare( ' AND q.user_id = %d', $args['user_id'] );
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
			return $wpdb->prepare( ' AND q.parent_post_id = %d', $args['lesson_id'] );
		}

		if ( ! empty( $args['post__in'] ) && is_array( $args['post__in'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['post__in'] ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			return $wpdb->prepare( " AND q.parent_post_id IN ( $placeholders )", $args['post__in'] );
		}

		return '';
	}
}

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
 * @since 4.26.0
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
	 * @since 4.26.0
	 *
	 * @param \wpdb $wpdb WordPress database object.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Get the progress table name.
	 *
	 * @since 4.26.0
	 *
	 * @return string
	 */
	private function get_progress_table_name(): string {
		return $this->wpdb->prefix . 'sensei_lms_progress';
	}

	/**
	 * Get the quiz submissions table name.
	 *
	 * @since 4.26.0
	 *
	 * @return string
	 */
	private function get_submissions_table_name(): string {
		return $this->wpdb->prefix . 'sensei_lms_quiz_submissions';
	}

	/**
	 * Get the SQL IN clause for graded quiz statuses.
	 *
	 * @since 4.26.0
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
	 * @since 4.26.0
	 *
	 * @param array $args {
	 *     Optional filters.
	 *
	 *     @type int   $user_id   Filter by user.
	 *     @type int   $lesson_id Filter by lesson (post_id).
	 *     @type int[] $post__in  Filter by lesson IDs.
	 * }
	 * @return array{count: int, sum: float}
	 */
	public function get_grade_totals( array $args = array() ): array {
		$wpdb              = $this->wpdb;
		$table             = $this->get_progress_table_name();
		$submissions_table = $this->get_submissions_table_name();

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Statuses are from constants, not user input.
		$query = $wpdb->prepare(
			"SELECT COUNT(*) AS count, COALESCE( SUM( qs.final_grade ), 0 ) AS sum
			FROM %i q
			INNER JOIN %i qs ON qs.quiz_id = q.post_id AND qs.user_id = q.user_id
			INNER JOIN %i lesson_quiz ON lesson_quiz.meta_key = '_lesson_quiz' AND lesson_quiz.meta_value = q.post_id
			WHERE q.type = 'quiz'
				AND q.status IN " . $this->get_graded_statuses_sql() . '
				AND qs.final_grade IS NOT NULL',
			$table,
			$submissions_table,
			$wpdb->postmeta
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

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
	 * Only includes student attempts where the quiz was actually submitted
	 * (enforced via INNER JOIN on the quiz submissions table and final_grade IS NOT NULL).
	 *
	 * @since 4.26.0
	 *
	 * @param int[] $course_ids Optional. Filter by courses. Empty = all.
	 * @return float
	 */
	public function get_courses_average_grade( array $course_ids = array() ): float {
		$wpdb              = $this->wpdb;
		$table             = $this->get_progress_table_name();
		$submissions_table = $this->get_submissions_table_name();

		if ( empty( $course_ids ) ) {
			$course_filter = '';
		} else {
			$placeholders = implode( ', ', array_fill( 0, count( $course_ids ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			$course_filter = $wpdb->prepare( " AND lesson_course.meta_value IN ( $placeholders )", $course_ids );
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Statuses are from constants, not user input.
		$query = $wpdb->prepare(
			"SELECT AVG(course_average) AS courses_average
			FROM (
				SELECT AVG(qs.final_grade) AS course_average
				FROM %i p
				INNER JOIN %i lesson_course ON lesson_course.post_id = p.post_id
					AND lesson_course.meta_key = '_lesson_course'
					AND lesson_course.meta_value <> ''
				INNER JOIN %i lesson_quiz ON lesson_quiz.post_id = p.post_id
					AND lesson_quiz.meta_key = '_lesson_quiz'
					AND lesson_quiz.meta_value > 0
				INNER JOIN %i q ON q.post_id = lesson_quiz.meta_value AND q.user_id = p.user_id AND q.type = 'quiz'
				INNER JOIN %i qs ON qs.quiz_id = q.post_id AND qs.user_id = p.user_id
				WHERE p.type = 'lesson'
					AND q.status IN " . $this->get_graded_statuses_sql() . '
					AND qs.final_grade IS NOT NULL',
			$table,
			$wpdb->postmeta,
			$wpdb->postmeta,
			$table,
			$submissions_table
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		$query .= $course_filter;
		$query .= ' GROUP BY lesson_course.meta_value ) averages_by_course';

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
	 * @since 4.26.0
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

		if ( ! $row || ! $row->grade_count ) {
			return 0.0;
		}

		return (float) ( $row->grade_sum / $row->grade_count );
	}

	/**
	 * Build SQL clause for filtering by user ID.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Query arguments.
	 * @return string SQL clause.
	 */
	private function build_user_filter( array $args ): string {
		if ( empty( $args['user_id'] ) ) {
			return '';
		}

		return $this->wpdb->prepare( ' AND q.user_id = %d', $args['user_id'] );
	}

	/**
	 * Build SQL clause for filtering by post ID(s).
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Query arguments.
	 * @return string SQL clause.
	 */
	private function build_post_filter( array $args ): string {
		$wpdb = $this->wpdb;

		if ( empty( $args['lesson_id'] ) && ( empty( $args['post__in'] ) || ! is_array( $args['post__in'] ) ) ) {
			return '';
		}

		if ( ! empty( $args['lesson_id'] ) ) {
			return $wpdb->prepare( ' AND lesson_quiz.post_id = %d', $args['lesson_id'] );
		}

		$placeholders = implode( ', ', array_fill( 0, count( $args['post__in'] ), '%d' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
		return $wpdb->prepare( " AND lesson_quiz.post_id IN ( $placeholders )", $args['post__in'] );
	}
}

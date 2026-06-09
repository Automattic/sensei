<?php
/**
 * File containing the Comments_Based_Grading_Stats_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress_Interface;

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
 * @since 4.26.0
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
	 * @since 4.26.0
	 *
	 * @param \wpdb $wpdb WordPress database object.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
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
		$wpdb = $this->wpdb;

		// The quiz_answers EXISTS check restricts results to attempts where the
		// student actually submitted answers. This excludes auto-passed students
		// whose lesson was marked passed without ever taking the quiz.
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Statuses are from constants, not user input.
		$query = $wpdb->prepare(
			"SELECT COUNT(*) AS count, COALESCE( SUM( cm.meta_value ), 0 ) AS sum
			FROM %i c
			INNER JOIN %i cm ON c.comment_ID = cm.comment_id
			WHERE c.comment_type = 'sensei_lesson_status'
				AND c.comment_approved IN " . $this->get_graded_statuses_sql() . "
				AND cm.meta_key = 'grade'
				AND EXISTS (
					SELECT 1 FROM %i cm2
					WHERE cm2.comment_id = c.comment_ID
						AND cm2.meta_key = 'quiz_answers'
				)",
			$wpdb->comments,
			$wpdb->commentmeta,
			$wpdb->commentmeta
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		$query .= $this->build_user_filter( $args );
		$query .= $this->build_post_filter( $args );

		/** Query result row. @var object|null $row */
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
	 * Only includes student attempts where the quiz was actually submitted
	 * (enforced via the quiz_answers EXISTS check).
	 *
	 * @since 4.26.0
	 *
	 * @param int[] $course_ids Optional. Filter by courses. Empty = all.
	 * @return float
	 */
	public function get_courses_average_grade( array $course_ids = array() ): float {
		$wpdb = $this->wpdb;

		if ( empty( $course_ids ) ) {
			$course_filter = '';
		} else {
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
		 *   - Have quiz answers (excludes auto-passed students who never took the quiz).
		 */
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Statuses are from constants, not user input.
		$query = $wpdb->prepare(
			"SELECT AVG(course_average) AS courses_average
			FROM (
				SELECT AVG(cm.meta_value) AS course_average
				FROM %i c
				INNER JOIN %i cm ON c.comment_ID = cm.comment_id
				INNER JOIN %i course ON c.comment_post_ID = course.post_id
				INNER JOIN %i p ON p.ID = course.meta_value
				WHERE c.comment_type = 'sensei_lesson_status'
					AND c.comment_approved IN " . $this->get_graded_statuses_sql() . "
					AND cm.meta_key = 'grade'
					AND course.meta_key = '_lesson_course'
					AND course.meta_value <> ''
					AND EXISTS (
						SELECT 1 FROM %i cm2
						WHERE cm2.comment_id = c.comment_ID
							AND cm2.meta_key = 'quiz_answers'
					)",
			$wpdb->comments,
			$wpdb->commentmeta,
			$wpdb->postmeta,
			$wpdb->posts,
			$wpdb->commentmeta
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		$query .= $course_filter;
		$query .= ' GROUP BY course.meta_value ) averages_by_course';

		/** Query result. @var object|null $result */
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared above. Caching handled by callers.
		$result = $wpdb->get_row( $query );
		Utils::log_query_error( $wpdb, 'Comments-based courses average grade' );

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

		$wpdb         = $this->wpdb;
		$placeholders = implode( ', ', array_fill( 0, count( $user_ids ), '%d' ) );

		// The quiz_answers EXISTS check restricts results to attempts where the
		// student actually submitted answers. This excludes auto-passed students
		// whose lesson was marked passed without ever taking the quiz.
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Statuses from constants. Placeholders created dynamically. Caching handled by callers.
		/** Query result row. @var object|null $row */
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT SUM( cm.meta_value ) AS grade_sum, COUNT( * ) AS grade_count
				FROM %i c
				INNER JOIN %i cm ON c.comment_ID = cm.comment_id
				WHERE c.comment_type = 'sensei_lesson_status'
					AND c.comment_approved IN " . $this->get_graded_statuses_sql() . "
					AND cm.meta_key = 'grade'
					AND EXISTS (
						SELECT 1 FROM %i cm2
						WHERE cm2.comment_id = c.comment_ID
							AND cm2.meta_key = 'quiz_answers'
					)
					AND c.user_id IN ( $placeholders )",
				array_merge( array( $wpdb->comments, $wpdb->commentmeta, $wpdb->commentmeta ), $user_ids )
			)
		);
		// phpcs:enable
		Utils::log_query_error( $wpdb, 'Comments-based users average grade' );

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

		return $this->wpdb->prepare( ' AND c.user_id = %d', $args['user_id'] );
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
			return $wpdb->prepare( ' AND c.comment_post_ID = %d', $args['lesson_id'] );
		}

		$placeholders = implode( ', ', array_fill( 0, count( $args['post__in'] ), '%d' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
		return $wpdb->prepare( " AND c.comment_post_ID IN ( $placeholders )", $args['post__in'] );
	}
}

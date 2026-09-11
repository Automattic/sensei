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
	 * Get the shared FROM and WHERE clauses for grade statistics queries.
	 *
	 * The quiz_answers check restricts results to attempts where the student
	 * submitted answers, excluding students auto-passed without taking the quiz.
	 *
	 * @since $$next-version$$
	 *
	 * @return string SQL fragment containing the FROM and WHERE clauses.
	 */
	private function get_grade_stats_from_where_sql(): string {
		$wpdb = $this->wpdb;

		return "FROM `{$wpdb->comments}` c
			INNER JOIN `{$wpdb->commentmeta}` cm ON c.comment_ID = cm.comment_id
			WHERE c.comment_type = 'sensei_lesson_status'
				AND c.comment_approved IN " . $this->get_graded_statuses_sql() . "
				AND cm.meta_key = 'grade'
				AND EXISTS (
					SELECT 1 FROM `{$wpdb->commentmeta}` cm2
					WHERE cm2.comment_id = c.comment_ID
						AND cm2.meta_key = 'quiz_answers'
				)";
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

		// Table names are trusted $wpdb properties; statuses are from constants, not user input.
		$query =
			'SELECT COUNT(*) AS count, COALESCE( SUM( cm.meta_value ), 0 ) AS sum
			' . $this->get_grade_stats_from_where_sql();

		$query .= $this->build_user_filter( $args );
		$query .= $this->build_post_filter( $args );

		/** Query result row. @var object|null $row */
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table names trusted; value filters use $wpdb->prepare(). Caching handled by callers.
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
	 * Get average grade grouped by user.
	 *
	 * @since $$next-version$$
	 *
	 * @param int[] $user_ids User IDs to include.
	 * @return array<int, float> Map of user ID to average grade.
	 */
	public function get_average_grades_by_user( array $user_ids ): array {
		if ( empty( $user_ids ) ) {
			return array();
		}

		$wpdb         = $this->wpdb;
		$placeholders = implode( ', ', array_fill( 0, count( $user_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Statuses from constants; placeholders dynamic; caching by callers.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT c.user_id AS user_id, AVG( cm.meta_value ) AS average_grade
				' . $this->get_grade_stats_from_where_sql() . "
					AND c.user_id IN ( $placeholders )
				GROUP BY c.user_id",
				$user_ids
			)
		);
		// phpcs:enable
		Utils::log_query_error( $wpdb, 'Comments-based average grades by user' );

		$average_grades = array();
		foreach ( (array) $rows as $row ) {
			$average_grades[ (int) $row->user_id ] = (float) $row->average_grade;
		}

		return $average_grades;
	}

	/**
	 * Get average grade grouped by lesson.
	 *
	 * @since $$next-version$$
	 *
	 * @param int[] $lesson_ids Lesson post IDs to include.
	 * @return array<int, float> Map of lesson ID to average grade.
	 */
	public function get_average_grades_by_lesson( array $lesson_ids ): array {
		if ( empty( $lesson_ids ) ) {
			return array();
		}

		$wpdb         = $this->wpdb;
		$placeholders = implode( ', ', array_fill( 0, count( $lesson_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Statuses from constants; placeholders dynamic; caching by callers.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.comment_post_ID AS lesson_id, AVG( cm.meta_value ) AS average_grade
				FROM {$wpdb->comments} c
				INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id
				WHERE c.comment_type = 'sensei_lesson_status'
					AND c.comment_approved IN " . $this->get_graded_statuses_sql() . "
					AND cm.meta_key = 'grade'
					AND c.comment_post_ID IN ( $placeholders )
				GROUP BY c.comment_post_ID",
				$lesson_ids
			)
		);
		// phpcs:enable
		Utils::log_query_error( $wpdb, 'Comments-based average grades by lesson' );

		$average_grades = array();
		foreach ( (array) $rows as $row ) {
			$average_grades[ (int) $row->lesson_id ] = (float) $row->average_grade;
		}

		return $average_grades;
	}

	/**
	 * Get an average grade for a lesson using report activity arguments.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return float|null Average grade, or null when no matching grades exist.
	 */
	public function get_average_grade_for_lesson( array $args ): ?float {
		$wpdb     = $this->wpdb;
		$post_id  = (int) ( $args['post_id'] ?? 0 );
		$type     = (string) ( $args['type'] ?? 'sensei_lesson_status' );
		$meta_key = (string) ( $args['meta_key'] ?? 'grade' );
		$statuses = isset( $args['status'] ) ? (array) $args['status'] : array();

		if ( $post_id <= 0 || empty( $statuses ) ) {
			return null;
		}

		$status_placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $status_placeholders is a list of %s; WP 6.4 changed WP_Comment_Query to use get_col(), so a comments_clauses-based aggregate is unreliable.
		$average = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(cm.meta_value)
				 FROM {$wpdb->comments} c
				 INNER JOIN {$wpdb->commentmeta} cm
				   ON cm.comment_id = c.comment_ID AND cm.meta_key = %s
				 WHERE c.comment_post_ID = %d
				   AND c.comment_type = %s
				   AND c.comment_approved IN ( {$status_placeholders} )",
				array_merge( array( $meta_key, $post_id, $type ), $statuses )
			)
		);
		// phpcs:enable
		Utils::log_query_error( $wpdb, 'Comments-based lesson average grade' );

		return null !== $average ? round( (float) $average, 2 ) : null;
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
		// Table names are trusted $wpdb properties; statuses are from constants, not user input.
		$query  =
			"SELECT AVG(course_average) AS courses_average
			FROM (
				SELECT AVG(cm.meta_value) AS course_average
				FROM `{$wpdb->comments}` c
				INNER JOIN `{$wpdb->commentmeta}` cm ON c.comment_ID = cm.comment_id
				INNER JOIN `{$wpdb->postmeta}` course ON c.comment_post_ID = course.post_id
				INNER JOIN `{$wpdb->posts}` p ON p.ID = course.meta_value
				WHERE c.comment_type = 'sensei_lesson_status'
					AND c.comment_approved IN " . $this->get_graded_statuses_sql() . "
					AND cm.meta_key = 'grade'
					AND course.meta_key = '_lesson_course'
					AND course.meta_value <> ''
					AND EXISTS (
						SELECT 1 FROM `{$wpdb->commentmeta}` cm2
						WHERE cm2.comment_id = c.comment_ID
							AND cm2.meta_key = 'quiz_answers'
					)";
		$query .= $course_filter;
		$query .= ' GROUP BY course.meta_value ) averages_by_course';

		/** Query result. @var object|null $result */
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table names trusted; value filters use $wpdb->prepare(). Caching handled by callers.
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

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Statuses from constants. Placeholders created dynamically. Caching handled by callers.
		/** Query result row. @var object|null $row */
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT COUNT( * ) AS count, SUM( cm.meta_value ) AS sum
				' . $this->get_grade_stats_from_where_sql() . "
					AND c.user_id IN ( $placeholders )",
				$user_ids
			)
		);
		// phpcs:enable
		Utils::log_query_error( $wpdb, 'Comments-based users average grade' );

		if ( ! $row || ! $row->count ) {
			return 0.0;
		}

		return (float) ( $row->sum / $row->count );
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

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
	 * Build a SQL-safe quoted status list.
	 *
	 * @since $$next-version$$
	 *
	 * @param string[] $statuses Statuses to include.
	 * @return string Comma-separated quoted status values.
	 */
	private function get_statuses_sql( array $statuses ): string {
		if ( empty( $statuses ) ) {
			return "'__none__'";
		}

		return implode(
			',',
			array_map(
				function ( $status ): string {
					return $this->wpdb->prepare( '%s', (string) $status );
				},
				$statuses
			)
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

		// Table names are trusted (built from $wpdb->prefix); statuses are from constants, not user input.
		$query =
			"SELECT COUNT(*) AS count, COALESCE( SUM( qs.final_grade ), 0 ) AS sum
			FROM `$table` q
			INNER JOIN `$submissions_table` qs ON qs.quiz_id = q.post_id AND qs.user_id = q.user_id
			INNER JOIN `{$wpdb->postmeta}` lesson_quiz ON lesson_quiz.meta_key = '_lesson_quiz' AND lesson_quiz.meta_value = q.post_id
			WHERE q.type = 'quiz'
				AND q.status IN " . $this->get_graded_statuses_sql() . '
				AND qs.final_grade IS NOT NULL';

		$query .= $this->build_user_filter( $args );
		$query .= $this->build_post_filter( $args );

		/** Query result row. @var object|null $row */
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table names trusted; value filters use $wpdb->prepare(). Caching handled by callers.
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

		$wpdb              = $this->wpdb;
		$table             = $this->get_progress_table_name();
		$submissions_table = $this->get_submissions_table_name();
		$placeholders      = implode( ', ', array_fill( 0, count( $user_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Statuses from constants; placeholders dynamic; caching by callers.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT q.user_id AS user_id, AVG( qs.final_grade ) AS average_grade
				FROM `$table` q
				INNER JOIN `$submissions_table` qs ON qs.quiz_id = q.post_id AND qs.user_id = q.user_id
				WHERE q.type = 'quiz'
					AND q.status IN " . $this->get_graded_statuses_sql() . "
					AND qs.final_grade IS NOT NULL
					AND q.user_id IN ( $placeholders )
				GROUP BY q.user_id",
				$user_ids
			)
		);
		// phpcs:enable
		Utils::log_query_error( $wpdb, 'Tables-based average grades by user' );

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

		$wpdb              = $this->wpdb;
		$table             = $this->get_progress_table_name();
		$submissions_table = $this->get_submissions_table_name();
		$placeholders      = implode( ', ', array_fill( 0, count( $lesson_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Statuses from constants; placeholders dynamic; caching by callers.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.post_id AS lesson_id, AVG( qs.final_grade ) AS average_grade
				FROM {$table} p
				INNER JOIN {$wpdb->postmeta} lesson_quiz ON lesson_quiz.post_id = p.post_id AND lesson_quiz.meta_key = '_lesson_quiz' AND lesson_quiz.meta_value > 0
				LEFT JOIN {$table} q ON q.post_id = lesson_quiz.meta_value AND q.user_id = p.user_id AND q.type = 'quiz'
				LEFT JOIN {$submissions_table} qs ON qs.quiz_id = lesson_quiz.meta_value AND qs.user_id = p.user_id
				WHERE p.type = 'lesson'
					AND ( q.status IN " . $this->get_graded_statuses_sql() . ' OR ( q.post_id IS NULL AND p.status IN ' . $this->get_graded_statuses_sql() . " ) )
					AND qs.final_grade IS NOT NULL
					AND p.post_id IN ( $placeholders )
				GROUP BY p.post_id",
				$lesson_ids
			)
		);
		// phpcs:enable
		Utils::log_query_error( $wpdb, 'Tables-based average grades by lesson' );

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
		$wpdb              = $this->wpdb;
		$table             = $this->get_progress_table_name();
		$submissions_table = $this->get_submissions_table_name();
		$post_id           = (int) ( $args['post_id'] ?? 0 );
		$status_sql        = $this->get_statuses_sql( (array) ( $args['status'] ?? array() ) );

		// Filter by the caller-provided statuses on the effective quiz status,
		// then average the grade from quiz_submissions. Table names are trusted $wpdb
		// properties and $status_sql is built from escaped args; the post_id uses a placeholder.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$average = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT AVG( qs.final_grade )'
				. " FROM `$table` p"
				. " LEFT JOIN `{$wpdb->postmeta}` pm ON pm.post_id = p.post_id AND pm.meta_key = '_lesson_quiz' AND pm.meta_value > 0"
				. " LEFT JOIN `$table` q ON q.post_id = pm.meta_value AND q.user_id = p.user_id AND q.type = 'quiz'"
				. " LEFT JOIN `$submissions_table` qs ON qs.quiz_id = pm.meta_value AND qs.user_id = p.user_id"
				. " WHERE p.post_id = %d AND p.type = 'lesson' AND qs.final_grade IS NOT NULL"
				. " AND ( q.status IN ( {$status_sql} ) OR ( q.post_id IS NULL AND p.status IN ( {$status_sql} ) ) )",
				$post_id
			)
		);
		// phpcs:enable
		Utils::log_query_error( $wpdb, 'Tables-based lesson average grade' );

		return null !== $average ? round( (float) $average, 2 ) : null;
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

		// Table names are trusted (built from $wpdb->prefix); statuses are from constants, not user input.
		$query  =
			"SELECT AVG(course_average) AS courses_average
			FROM (
				SELECT AVG(qs.final_grade) AS course_average
				FROM `$table` p
				INNER JOIN `{$wpdb->postmeta}` lesson_course ON lesson_course.post_id = p.post_id
					AND lesson_course.meta_key = '_lesson_course'
					AND lesson_course.meta_value <> ''
				INNER JOIN `{$wpdb->postmeta}` lesson_quiz ON lesson_quiz.post_id = p.post_id
					AND lesson_quiz.meta_key = '_lesson_quiz'
					AND lesson_quiz.meta_value > 0
				INNER JOIN `$table` q ON q.post_id = lesson_quiz.meta_value AND q.user_id = p.user_id AND q.type = 'quiz'
				INNER JOIN `$submissions_table` qs ON qs.quiz_id = q.post_id AND qs.user_id = p.user_id
				WHERE p.type = 'lesson'
					AND q.status IN " . $this->get_graded_statuses_sql() . '
					AND qs.final_grade IS NOT NULL';
		$query .= $course_filter;
		$query .= ' GROUP BY lesson_course.meta_value ) averages_by_course';

		/** Query result. @var object|null $result */
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table names trusted; value filters use $wpdb->prepare(). Caching handled by callers.
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
				"SELECT COUNT( * ) AS count, SUM( qs.final_grade ) AS sum
				FROM `$table` q
				INNER JOIN `$submissions_table` qs ON qs.quiz_id = q.post_id AND qs.user_id = q.user_id
				WHERE q.type = 'quiz'
					AND q.status IN " . $this->get_graded_statuses_sql() . "
					AND qs.final_grade IS NOT NULL
					AND q.user_id IN ( $placeholders )",
				$user_ids
			)
		);
		// phpcs:enable
		Utils::log_query_error( $wpdb, 'Tables-based users average grade' );

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

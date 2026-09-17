<?php
/**
 * File containing the Tables_Based_Progress_Aggregation_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Tables_Based_Progress_Aggregation_Service.
 *
 * Tables-based (HPPS) implementation of progress aggregation.
 * Queries the sensei_lms_progress table to count progress records grouped by status.
 *
 * @internal
 *
 * @since 4.26.0
 */
class Tables_Based_Progress_Aggregation_Service implements Progress_Aggregation_Service_Interface {

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
	 * Count progress records grouped by status.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args {
	 *     Arguments for the query.
	 *
	 *     @type string    $type                         'course' or 'lesson'.
	 *     @type array     $post__in                     Restrict to specific post IDs.
	 *     @type int       $post_id                      Restrict to a single post ID.
	 *     @type int|array $user_id                      Restrict to specific user IDs.
	 *     @type string[]  $exclude_user_login_prefixes  User login prefixes to exclude.
	 *     @type string[]  $include_statuses_override    Statuses that bypass user exclusion.
	 * }
	 * @return array Associative array of status => count.
	 */
	public function count_statuses( array $args ): array {
		if ( ! empty( $args['query'] ) ) {
			_doing_it_wrong(
				__METHOD__,
				'The "query" argument is not supported with tables-based progress storage. Use "exclude_user_login_prefixes" and "include_statuses_override" instead.',
				'4.26.0'
			);
		}

		if ( empty( $args['type'] ) || ! in_array( $args['type'], array( 'course', 'lesson' ), true ) ) {
			_doing_it_wrong(
				__METHOD__,
				'The "type" argument must be "course" or "lesson".',
				'4.26.0'
			);
			return array();
		}

		// Delegate to a quiz-aware method; see its docblock for rationale.
		if ( 'lesson' === $args['type'] ) {
			return $this->count_lesson_statuses_with_quiz( $args );
		}

		return $this->count_course_statuses( $args );
	}

	/**
	 * Count course progress records grouped by user and status.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args {
	 *     Query arguments.
	 *
	 *     @type string    $type    Must be 'course'.
	 *     @type int|array $user_id Restrict to specific user IDs.
	 * }
	 * @return array<int, array<string, int>> Map of user_id => [ status => count ].
	 */
	public function count_statuses_by_user( array $args ): array {
		if ( empty( $args['type'] ) || 'course' !== $args['type'] ) {
			_doing_it_wrong( __METHOD__, 'The "type" argument must be "course". Per-user lesson counts are not supported.', '$$next-version$$' );
			return array();
		}

		$wpdb  = $this->wpdb;
		$table = $this->get_progress_table_name();

		$reports_statuses = Utils::get_reports_post_status_sql();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from wpdb prefix.
		$query  = "SELECT p.user_id, p.status, COUNT(*) AS total FROM {$table} p";
		$query .= " INNER JOIN {$wpdb->posts} post ON post.ID = p.post_id AND post.post_status IN ( {$reports_statuses} )";
		$query .= $wpdb->prepare( ' WHERE p.type = %s', $args['type'] );
		$query .= $this->build_user_filter_clause( $args );
		$query .= ' GROUP BY p.user_id, p.status';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared in advance. Caching handled by callers.
		$results = (array) $wpdb->get_results( $query, ARRAY_A );
		Utils::log_query_error( $wpdb, 'Tables-based status counts by user' );

		$counts = array();
		foreach ( $results as $row ) {
			$counts[ (int) $row['user_id'] ][ $row['status'] ] = (int) $row['total'];
		}

		return $counts;
	}

	/**
	 * Count students with activity on a lesson.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Comments-API-shaped activity arguments.
	 * @return int Number of students with matching lesson activity.
	 */
	public function get_lesson_student_count( array $args ): int {
		$wpdb    = $this->wpdb;
		$table   = $this->get_progress_table_name();
		$post_id = (int) ( $args['post_id'] ?? 0 );
		$status  = $args['status'] ?? 'any';

		$where = $wpdb->prepare( ' WHERE p.post_id = %d AND p.type = \'lesson\'', $post_id );
		if ( 'any' !== $status ) {
			$status_sql = Utils::get_statuses_sql( $wpdb, $args );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $status_sql is built from escaped args.
			$where .= " AND p.status IN ( {$status_sql} )";
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is a trusted wpdb-derived name; $where is built from wpdb::prepare() calls.
		$count = $wpdb->get_var( "SELECT COUNT( DISTINCT p.user_id ) FROM `$table` p" . $where );
		Utils::log_query_error( $wpdb, 'Progress aggregation lesson student count' );

		return (int) $count;
	}

	/**
	 * Count students who completed a lesson.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Comments-API-shaped activity arguments.
	 * @return int Number of students with matching completed lesson activity.
	 */
	public function get_lesson_completion_count( array $args ): int {
		$wpdb       = $this->wpdb;
		$table      = $this->get_progress_table_name();
		$post_id    = (int) ( $args['post_id'] ?? 0 );
		$status_sql = Utils::get_statuses_sql( $wpdb, $args );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table names are trusted wpdb-derived names; status values and post ID are prepared.
		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT( DISTINCT p.user_id )'
				. " FROM `$table` p"
				. " LEFT JOIN `{$wpdb->postmeta}` pm ON pm.post_id = p.post_id AND pm.meta_key = '_lesson_quiz' AND pm.meta_value > 0"
				. " LEFT JOIN `$table` q ON q.post_id = pm.meta_value AND q.user_id = p.user_id AND q.type = 'quiz'"
				. " WHERE p.post_id = %d AND p.type = 'lesson'"
				. " AND ( q.status IN ( {$status_sql} ) OR ( q.post_id IS NULL AND p.status IN ( {$status_sql} ) ) )",
				$post_id
			)
		);
		// phpcs:enable
		Utils::log_query_error( $wpdb, 'Progress aggregation lesson completion count' );

		return (int) $count;
	}

	/**
	 * Get aggregate totals for a set of lessons.
	 *
	 * @since 4.26.0
	 *
	 * @param int[] $lesson_ids Array of lesson post IDs.
	 * @return array Associative array with keys: unique_student_count, lesson_start_count, lesson_completed_count, days_to_complete_count, days_to_complete_sum.
	 */
	public function get_lesson_totals( array $lesson_ids ): array {
		$defaults = array(
			'unique_student_count'   => 0,
			'lesson_start_count'     => 0,
			'lesson_completed_count' => 0,
			'days_to_complete_count' => 0,
			'days_to_complete_sum'   => 0,
		);

		if ( empty( $lesson_ids ) ) {
			return $defaults;
		}

		$wpdb              = $this->wpdb;
		$table             = $this->get_progress_table_name();
		$submissions_table = $wpdb->prefix . 'sensei_lms_quiz_submissions';
		$placeholders      = implode( ', ', array_fill( 0, count( $lesson_ids ), '%d' ) );
		$completed         = "('" . implode( "','", Grading_Item::COMPLETED_STATUSES ) . "')";
		$has_completion    = "('" . implode( "','", Grading_Item::STATUSES_WITH_COMPLETION_DATE ) . "')";
		$utc_offset        = Utils::get_utc_offset_string();

		$reports_statuses = Utils::get_reports_post_status_sql();

		// Plain COALESCE suffices here because quiz rows are already filtered
		// by submission existence in the JOIN condition (AND EXISTS ...).
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table names from wpdb prefix. Placeholders and status list created dynamically.
		$query = $wpdb->prepare(
			"SELECT COUNT(DISTINCT p.user_id) AS unique_student_count
			, COUNT(*) AS lesson_start_count
			, SUM(IF(COALESCE( q.status, p.status ) IN $completed, 1, 0)) AS lesson_completed_count
			, SUM(IF(COALESCE( q.status, p.status ) IN $has_completion, 1, 0)) AS days_to_complete_count
			, SUM(IF(COALESCE( q.status, p.status ) IN $has_completion, ABS( DATEDIFF( CONVERT_TZ( p.completed_at, '+00:00', '$utc_offset' ), CONVERT_TZ( p.started_at, '+00:00', '$utc_offset' ) ) ) + 1, 0)) AS days_to_complete_sum
			FROM {$table} p
			INNER JOIN {$wpdb->posts} post ON post.ID = p.post_id AND post.post_status IN ( {$reports_statuses} )
			LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.post_id AND pm.meta_key = '_lesson_quiz' AND pm.meta_value > 0
			LEFT JOIN {$table} q ON q.post_id = pm.meta_value AND q.user_id = p.user_id AND q.type = 'quiz'
				AND EXISTS ( SELECT 1 FROM {$submissions_table} qs WHERE qs.quiz_id = q.post_id AND qs.user_id = q.user_id )
			WHERE p.type = 'lesson' AND p.post_id IN ( $placeholders )",
			$lesson_ids
		);
		// phpcs:enable

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared in advance. Caching handled by callers.
		$row = $wpdb->get_row( $query );
		Utils::log_query_error( $wpdb, 'Tables-based lesson totals' );

		if ( ! $row ) {
			return $defaults;
		}

		return array(
			'unique_student_count'   => (int) $row->unique_student_count,
			'lesson_start_count'     => (int) $row->lesson_start_count,
			'lesson_completed_count' => (int) $row->lesson_completed_count,
			'days_to_complete_count' => (int) $row->days_to_complete_count,
			'days_to_complete_sum'   => (int) $row->days_to_complete_sum,
		);
	}

	/**
	 * Count ungraded quiz submissions whose lesson is publicly available.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Optional restrictions; see interface.
	 * @return int Number of ungraded quiz submissions for live (publish or private) lessons.
	 */
	public function count_ungraded_quizzes( array $args = array() ): int {
		$wpdb  = $this->wpdb;
		$table = $this->get_progress_table_name();

		$reports_statuses = Utils::get_reports_post_status_sql();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names from wpdb prefix; status/type values are constants.
		$query = "SELECT COUNT(*) FROM {$table} p
			INNER JOIN {$wpdb->postmeta} pm ON pm.meta_key = '_lesson_quiz' AND pm.meta_value = p.post_id
			INNER JOIN {$wpdb->posts} lesson_post ON lesson_post.ID = pm.post_id AND lesson_post.post_status IN ( {$reports_statuses} )
			INNER JOIN {$table} lp ON lp.post_id = pm.post_id AND lp.user_id = p.user_id AND lp.type = 'lesson'
			WHERE p.type = 'quiz' AND p.status = 'ungraded'";

		if ( ! empty( $args['post__in'] ) && is_array( $args['post__in'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['post__in'] ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			$query .= $wpdb->prepare( " AND lesson_post.ID IN ( $placeholders )", $args['post__in'] );
		}

		$query .= $this->build_user_exclusion_clause( $args );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL built from literals only.
		$count = (int) $wpdb->get_var( $query );
		Utils::log_query_error( $wpdb, 'Tables-based ungraded quizzes count' );

		return $count;
	}

	/**
	 * Count progress records grouped by post and status.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Same shape as count_statuses(); 'type' and 'post__in' honored.
	 * @return array<int, array<string, int>> Map of post_id => [ status => count ].
	 */
	public function count_statuses_by_post( array $args ): array {
		if ( empty( $args['type'] ) || ! in_array( $args['type'], array( 'course', 'lesson' ), true ) ) {
			_doing_it_wrong( __METHOD__, 'The "type" argument must be "course" or "lesson".', '$$next-version$$' );
			return array();
		}

		// WPML stores shared progress on the original post, so resolve translated IDs before querying.
		// If both post_id and post__in are supplied, filter by post_id and ignore post__in.
		$post_ids    = ! empty( $args['post_id'] ) ? array( $args['post_id'] ) : ( $args['post__in'] ?? array() );
		$post_id_map = Utils::get_progress_post_id_map( $post_ids, $args['type'] );
		if ( ! empty( $args['post_id'] ) ) {
			$args['post_id'] = $post_id_map[ (int) $args['post_id'] ];
		} elseif ( $post_id_map ) {
			$args['post__in'] = array_values( $post_id_map );
		}

		if ( 'lesson' === $args['type'] ) {
			$counts = $this->count_lesson_statuses_with_quiz_by_post( $args );
		} else {
			$counts = $this->count_course_statuses_by_post( $args );
		}

		if ( empty( $post_id_map ) ) {
			return $counts;
		}

		// Reports need results keyed by the requested IDs, including translations.
		$requested_counts = array();
		foreach ( $post_id_map as $requested_id => $stored_id ) {
			if ( isset( $counts[ $stored_id ] ) ) {
				$requested_counts[ $requested_id ] = $counts[ $stored_id ];
			}
		}

		return $requested_counts;
	}

	/**
	 * Count completed lesson progress per lesson.
	 *
	 * @since $$next-version$$
	 *
	 * @param int[] $lesson_ids Lesson post IDs.
	 * @return array<int, int> Map of lesson_id => completion count.
	 */
	public function get_lesson_completion_counts( array $lesson_ids ): array {
		if ( empty( $lesson_ids ) ) {
			return array();
		}

		// WPML translations share progress; query each original lesson only once.
		$post_id_map = Utils::get_progress_post_id_map( $lesson_ids, 'lesson' );
		$lesson_ids  = array_values( array_unique( $post_id_map ) );

		$reports_statuses  = Utils::get_reports_post_status_sql();
		$wpdb              = $this->wpdb;
		$table             = $this->get_progress_table_name();
		$submissions_table = $wpdb->prefix . 'sensei_lms_quiz_submissions';
		$placeholders      = implode( ', ', array_fill( 0, count( $lesson_ids ), '%d' ) );

		// Check parent-course eligibility once per grouped lesson, not once per student.
		// A submitted quiz supplies the lesson status; quiz rows without a submission are ignored.
		// Submitted quizzes count as completions even while awaiting grading, for published/private parent courses.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table names from wpdb prefix. Placeholders created dynamically.
		$query = $wpdb->prepare(
			"SELECT completions.lesson_id, completions.completion_count
			FROM (
				SELECT p.post_id AS lesson_id, COUNT(*) AS completion_count
				FROM {$table} p
				LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.post_id AND pm.meta_key = '_lesson_quiz' AND pm.meta_value > 0
				LEFT JOIN {$table} q ON q.post_id = pm.meta_value AND q.user_id = p.user_id AND q.type = 'quiz'
					AND EXISTS ( SELECT 1 FROM {$submissions_table} qs WHERE qs.quiz_id = q.post_id AND qs.user_id = q.user_id )
				WHERE p.type = 'lesson'
					AND p.post_id IN ( $placeholders )
					AND COALESCE( q.status, p.status ) IN ('graded', 'ungraded', 'passed', 'failed', 'complete')
				GROUP BY p.post_id
			) completions
			WHERE EXISTS (
				SELECT 1 FROM {$wpdb->postmeta} course_meta
				JOIN {$wpdb->posts} course ON course.ID = course_meta.meta_value
				WHERE course_meta.post_id = completions.lesson_id
					AND course_meta.meta_key = '_lesson_course'
					AND course.post_status IN ( {$reports_statuses} )
			)",
			$lesson_ids
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared in advance. Caching handled by callers.
		$results = (array) $wpdb->get_results( $query, ARRAY_A );
		Utils::log_query_error( $wpdb, 'Tables-based lesson completion counts' );

		$counts = array();
		foreach ( $results as $row ) {
			$counts[ (int) $row['lesson_id'] ] = (int) $row['completion_count'];
		}

		// Reports need results keyed by the requested IDs, including translations.
		$requested_counts = array();
		foreach ( $post_id_map as $requested_id => $stored_id ) {
			if ( isset( $counts[ $stored_id ] ) ) {
				$requested_counts[ $requested_id ] = $counts[ $stored_id ];
			}
		}

		return $requested_counts;
	}

	/**
	 * Get the progress table name.
	 *
	 * @since 4.26.0
	 *
	 * @return string The progress table name.
	 */
	private function get_progress_table_name(): string {
		return $this->wpdb->prefix . 'sensei_lms_progress';
	}

	/**
	 * Count lesson statuses using quiz status when a quiz exists.
	 *
	 * In HPPS, lesson progress rows only store 'in-progress' and 'complete',
	 * while quiz progress rows store the granular statuses (graded, passed, etc.).
	 * This mirrors the comments-based behavior where a single comment per lesson
	 * stores the quiz-derived status directly.
	 *
	 * Uses COALESCE(q.status, p.status) so quiz progress status takes
	 * precedence when it exists; otherwise falls back to lesson status.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Query arguments (see count_statuses).
	 * @return array Associative array of status => count.
	 */
	private function count_lesson_statuses_with_quiz( array $args ): array {
		$wpdb  = $this->wpdb;
		$table = $this->get_progress_table_name();

		$reports_statuses = Utils::get_reports_post_status_sql();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names from wpdb prefix.
		$query = "SELECT COALESCE( q.status, p.status ) AS effective_status, COUNT( * ) AS total FROM {$table} p";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from wpdb prefix.
		$query .= " INNER JOIN {$wpdb->posts} post ON post.ID = p.post_id AND post.post_status IN ( {$reports_statuses} )";
		$query .= " LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.post_id AND pm.meta_key = '_lesson_quiz' AND pm.meta_value > 0";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names from wpdb prefix.
		$query .= " LEFT JOIN {$table} q ON q.post_id = pm.meta_value AND q.user_id = p.user_id AND q.type = 'quiz'";

		$query .= $wpdb->prepare( ' WHERE p.type = %s', 'lesson' );

		$query .= $this->build_post_filter_clause( $args );
		$query .= $this->build_user_filter_clause( $args );
		$query .= $this->build_user_exclusion_clause( $args, 'COALESCE( q.status, p.status )' );

		$query .= ' GROUP BY effective_status';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared in advance. Caching handled by callers.
		$results = (array) $wpdb->get_results( $query, ARRAY_A );
		Utils::log_query_error( $wpdb, 'Tables-based lesson status counts' );

		$counts = array();
		foreach ( $results as $row ) {
			$counts[ $row['effective_status'] ] = (int) $row['total'];
		}

		return $counts;
	}

	/**
	 * Count course statuses.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Query arguments (see count_statuses).
	 * @return array Associative array of status => count.
	 */
	private function count_course_statuses( array $args ): array {
		$wpdb  = $this->wpdb;
		$table = $this->get_progress_table_name();

		$reports_statuses = Utils::get_reports_post_status_sql();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from wpdb prefix.
		$query  = "SELECT p.status, COUNT(*) AS total FROM {$table} p";
		$query .= " INNER JOIN {$wpdb->posts} post ON post.ID = p.post_id AND post.post_status IN ( {$reports_statuses} )";

		$query .= $wpdb->prepare( ' WHERE p.type = %s', $args['type'] );
		$query .= $this->build_post_filter_clause( $args );
		$query .= $this->build_user_filter_clause( $args );
		$query .= $this->build_user_exclusion_clause( $args );

		$query .= ' GROUP BY p.status';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared in advance. Caching handled by callers.
		$results = (array) $wpdb->get_results( $query, ARRAY_A );
		Utils::log_query_error( $wpdb, 'Tables-based course status counts' );

		$counts = array();
		foreach ( $results as $row ) {
			$counts[ $row['status'] ] = (int) $row['total'];
		}

		return $counts;
	}

	/**
	 * Count course progress grouped by post and status.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Query arguments (see count_statuses).
	 * @return array<int, array<string, int>> Map of post_id => [ status => count ].
	 */
	private function count_course_statuses_by_post( array $args ): array {
		$reports_statuses = Utils::get_reports_post_status_sql();
		$wpdb             = $this->wpdb;
		$table            = $this->get_progress_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from wpdb prefix.
		$query  = "SELECT p.post_id, p.status, COUNT(*) AS total FROM {$table} p";
		$query .= " INNER JOIN {$wpdb->posts} post ON post.ID = p.post_id AND post.post_status IN ( {$reports_statuses} )";

		$query .= $wpdb->prepare( ' WHERE p.type = %s', $args['type'] );
		$query .= $this->build_post_filter_clause( $args );
		$query .= $this->build_user_filter_clause( $args );
		$query .= $this->build_user_exclusion_clause( $args );

		$query .= ' GROUP BY p.post_id, p.status';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared in advance. Caching handled by callers.
		$results = (array) $wpdb->get_results( $query, ARRAY_A );
		Utils::log_query_error( $wpdb, 'Tables-based course status counts by post' );

		$counts = array();
		foreach ( $results as $row ) {
			$counts[ (int) $row['post_id'] ][ $row['status'] ] = (int) $row['total'];
		}

		return $counts;
	}

	/**
	 * Count lesson statuses grouped by post, using quiz status when a quiz exists.
	 *
	 * See count_lesson_statuses_with_quiz() for the rationale behind using
	 * COALESCE(q.status, p.status).
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Query arguments (see count_statuses).
	 * @return array<int, array<string, int>> Map of post_id => [ status => count ].
	 */
	private function count_lesson_statuses_with_quiz_by_post( array $args ): array {
		$reports_statuses = Utils::get_reports_post_status_sql();
		$wpdb             = $this->wpdb;
		$table            = $this->get_progress_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names from wpdb prefix.
		$query = "SELECT p.post_id, COALESCE( q.status, p.status ) AS effective_status, COUNT( * ) AS total FROM {$table} p";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from wpdb prefix.
		$query .= " INNER JOIN {$wpdb->posts} post ON post.ID = p.post_id AND post.post_status IN ( {$reports_statuses} )";
		$query .= " LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.post_id AND pm.meta_key = '_lesson_quiz' AND pm.meta_value > 0";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names from wpdb prefix.
		$query .= " LEFT JOIN {$table} q ON q.post_id = pm.meta_value AND q.user_id = p.user_id AND q.type = 'quiz'";

		$query .= $wpdb->prepare( ' WHERE p.type = %s', 'lesson' );

		$query .= $this->build_post_filter_clause( $args );
		$query .= $this->build_user_filter_clause( $args );
		$query .= $this->build_user_exclusion_clause( $args, 'COALESCE( q.status, p.status )' );

		$query .= ' GROUP BY p.post_id, effective_status';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared in advance. Caching handled by callers.
		$results = (array) $wpdb->get_results( $query, ARRAY_A );
		Utils::log_query_error( $wpdb, 'Tables-based lesson status counts by post' );

		$counts = array();
		foreach ( $results as $row ) {
			$counts[ (int) $row['post_id'] ][ $row['effective_status'] ] = (int) $row['total'];
		}

		return $counts;
	}

	/**
	 * Build SQL clause for filtering by post ID(s).
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Query arguments.
	 * @return string SQL clause.
	 */
	private function build_post_filter_clause( array $args ): string {
		$wpdb = $this->wpdb;

		// Prefer post_id (single lesson filter) over post__in (course lessons)
		// so that counts reflect the specific lesson when both are set.
		if ( ! empty( $args['post_id'] ) ) {
			return $wpdb->prepare( ' AND p.post_id = %d', $args['post_id'] );
		}

		if ( ! empty( $args['post__in'] ) && is_array( $args['post__in'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['post__in'] ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			return $wpdb->prepare( " AND p.post_id IN ( $placeholders )", $args['post__in'] );
		}

		return '';
	}

	/**
	 * Build SQL clause for filtering by user ID(s).
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Query arguments.
	 * @return string SQL clause.
	 */
	private function build_user_filter_clause( array $args ): string {
		$wpdb = $this->wpdb;

		if ( ! empty( $args['user_id'] ) && is_array( $args['user_id'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['user_id'] ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			return $wpdb->prepare( " AND p.user_id IN ( $placeholders )", $args['user_id'] );
		}

		if ( ! empty( $args['user_id'] ) ) {
			return $wpdb->prepare( ' AND p.user_id = %d', $args['user_id'] );
		}

		return '';
	}

	/**
	 * Build SQL clause for excluding users by login prefix.
	 *
	 * @since 4.26.0
	 *
	 * @param array  $args           Query arguments.
	 * @param string $status_column  SQL expression for the status column (default: 'p.status').
	 * @return string SQL clause.
	 */
	private function build_user_exclusion_clause( array $args, string $status_column = 'p.status' ): string {
		return Utils::build_user_exclusion_clause( $this->wpdb, $args, $status_column );
	}
}

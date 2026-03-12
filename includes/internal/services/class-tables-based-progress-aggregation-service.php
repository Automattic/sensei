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
 * @since $$next-version$$
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
	 * @return string The progress table name.
	 */
	private function get_progress_table_name(): string {
		return $this->wpdb->prefix . 'sensei_lms_progress';
	}

	/**
	 * Count progress records grouped by status.
	 *
	 * @since $$next-version$$
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
		// For lesson queries, use the quiz status when available (graded, passed, etc.)
		// since lesson progress only stores 'in-progress' and 'complete'.
		if ( 'lesson' === $args['type'] ) {
			return $this->count_lesson_statuses_with_quiz( $args );
		}

		return $this->count_progress_statuses( $args );
	}

	/**
	 * Count lesson statuses using quiz status when a quiz exists.
	 *
	 * In HPPS, lesson progress rows only store 'in-progress' and 'complete',
	 * while quiz progress rows store the granular statuses (graded, passed, etc.).
	 * This mirrors the comments-based behavior where a single comment per lesson
	 * stores the quiz-derived status directly.
	 *
	 * Uses COALESCE(quiz.status, lesson.status) so the quiz status takes
	 * precedence when it exists.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Query arguments (see count_statuses).
	 * @return array Associative array of status => count.
	 */
	private function count_lesson_statuses_with_quiz( array $args ): array {
		$wpdb  = $this->wpdb;
		$table = $this->get_progress_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names from wpdb prefix.
		$query  = "SELECT COALESCE( q.status, p.status ) AS effective_status, COUNT(*) AS total FROM {$table} p";
		$query .= " LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.post_id AND pm.meta_key = '_lesson_quiz' AND pm.meta_value > 0";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from wpdb prefix.
		$query .= " LEFT JOIN {$table} q ON q.post_id = pm.meta_value AND q.user_id = p.user_id AND q.type = 'quiz'";

		$query .= $wpdb->prepare( ' WHERE p.type = %s', 'lesson' );

		$query .= $this->build_post_filter_clause( $args );
		$query .= $this->build_user_filter_clause( $args );
		$query .= $this->build_user_exclusion_clause( $args, 'COALESCE( q.status, p.status )' );

		$query .= ' GROUP BY effective_status';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared in advance. Caching handled by callers.
		$results = (array) $wpdb->get_results( $query, ARRAY_A );

		$counts = [];
		foreach ( $results as $row ) {
			$counts[ $row['effective_status'] ] = (int) $row['total'];
		}

		return $counts;
	}

	/**
	 * Count progress records grouped by status.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Query arguments (see count_statuses).
	 * @return array Associative array of status => count.
	 */
	private function count_progress_statuses( array $args ): array {
		$wpdb  = $this->wpdb;
		$table = $this->get_progress_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from wpdb prefix.
		$query = "SELECT p.status, COUNT(*) AS total FROM {$table} p";

		$query .= $wpdb->prepare( ' WHERE p.type = %s', $args['type'] );
		$query .= $this->build_post_filter_clause( $args );
		$query .= $this->build_user_filter_clause( $args );
		$query .= $this->build_user_exclusion_clause( $args );

		$query .= ' GROUP BY p.status';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared in advance. Caching handled by callers.
		$results = (array) $wpdb->get_results( $query, ARRAY_A );

		$counts = [];
		foreach ( $results as $row ) {
			$counts[ $row['status'] ] = (int) $row['total'];
		}

		return $counts;
	}

	/**
	 * Build SQL clause for filtering by post ID(s).
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Query arguments.
	 * @return string SQL clause.
	 */
	private function build_post_filter_clause( array $args ): string {
		$wpdb = $this->wpdb;

		if ( ! empty( $args['post__in'] ) && is_array( $args['post__in'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['post__in'] ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			return $wpdb->prepare( " AND p.post_id IN ( $placeholders )", $args['post__in'] );
		}

		if ( ! empty( $args['post_id'] ) ) {
			return $wpdb->prepare( ' AND p.post_id = %d', $args['post_id'] );
		}

		return '';
	}

	/**
	 * Build SQL clause for filtering by user ID(s).
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Query arguments.
	 * @return string SQL clause.
	 */
	private function build_user_filter_clause( array $args ): string {
		$wpdb = $this->wpdb;

		if ( isset( $args['user_id'] ) && is_array( $args['user_id'] ) ) {
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
	 * @since $$next-version$$
	 *
	 * @param array  $args           Query arguments.
	 * @param string $status_column  SQL expression for the status column (default: 'p.status').
	 * @return string SQL clause.
	 */
	private function build_user_exclusion_clause( array $args, string $status_column = 'p.status' ): string {
		if ( empty( $args['exclude_user_login_prefixes'] ) ) {
			return '';
		}

		$wpdb              = $this->wpdb;
		$excluded_user_ids = $this->get_user_ids_by_login_prefixes( $args['exclude_user_login_prefixes'] );

		if ( empty( $excluded_user_ids ) ) {
			return '';
		}

		$id_placeholders = implode( ', ', array_fill( 0, count( $excluded_user_ids ), '%d' ) );

		if ( ! empty( $args['include_statuses_override'] ) ) {
			$status_placeholders = implode( ', ', array_fill( 0, count( $args['include_statuses_override'] ), '%s' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders and column expression created dynamically.
			return $wpdb->prepare( " AND ( p.user_id NOT IN ( $id_placeholders ) OR $status_column IN ( $status_placeholders ) )", array_merge( $excluded_user_ids, $args['include_statuses_override'] ) );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
		return $wpdb->prepare( " AND p.user_id NOT IN ( $id_placeholders )", $excluded_user_ids );
	}

	/**
	 * Get user IDs whose login matches any of the given prefixes.
	 *
	 * Runs as a separate query to avoid JOINing wp_users, which may
	 * be on a different database in some environments.
	 *
	 * @since $$next-version$$
	 *
	 * @param string[] $prefixes User login prefixes to match.
	 * @return int[] Matching user IDs.
	 */
	private function get_user_ids_by_login_prefixes( array $prefixes ): array {
		$wpdb = $this->wpdb;

		$like_clauses = [];
		foreach ( $prefixes as $prefix ) {
			$escaped_prefix = $wpdb->esc_like( $prefix );
			$like_clauses[] = $wpdb->prepare( 'user_login LIKE %s', $escaped_prefix . '%' );
		}

		$where = implode( ' OR ', $like_clauses );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Dynamic WHERE built from prepared clauses. Caching handled by callers.
		return array_map( 'intval', $wpdb->get_col( "SELECT ID FROM {$wpdb->users} WHERE $where" ) );
	}
}

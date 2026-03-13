<?php
/**
 * File containing the Comments_Based_Progress_Aggregation_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Comments_Based_Progress_Aggregation_Service.
 *
 * Comments-based (legacy) implementation of progress aggregation.
 * Queries wp_comments to count progress records grouped by status.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Comments_Based_Progress_Aggregation_Service implements Progress_Aggregation_Service_Interface {

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
	 *     @type string    $query                        Raw SQL to append (backward compat).
	 * }
	 * @return array Associative array of status => count.
	 */
	public function count_statuses( array $args ): array {
		if ( 'lesson' === $args['type'] ) {
			return $this->count_lesson_statuses( $args );
		}

		return $this->count_generic_statuses( $args );
	}

	/**
	 * Count lesson statuses, falling back to 'complete' for quiz statuses without a submission.
	 *
	 * Lesson comments may have quiz-derived statuses (passed, graded, failed) even when no
	 * quiz submission exists (e.g. quiz answers were lost or quiz was added after completion).
	 * This method uses CASE to reclassify those as 'complete' so that lessons without actual
	 * quiz data are not counted in the Graded bucket.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Query arguments (see count_statuses).
	 * @return array Associative array of status => count.
	 */
	private function count_lesson_statuses( array $args ): array {
		$wpdb              = $this->wpdb;
		$submissions_table = $wpdb->prefix . 'sensei_lms_quiz_submissions';

		$quiz_statuses = "'passed', 'graded', 'failed'";

		// Use CASE to reclassify quiz statuses as 'complete' when no quiz submission exists.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names from wpdb prefix. Status literals are hardcoded.
		$query = "SELECT CASE WHEN c.comment_approved IN ( {$quiz_statuses} )";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names from wpdb prefix.
		$query .= " AND NOT EXISTS ( SELECT 1 FROM {$submissions_table} qs";
		$query .= " INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = c.comment_post_ID AND pm.meta_key = '_lesson_quiz' AND pm.meta_value > 0";
		$query .= ' WHERE qs.quiz_id = pm.meta_value AND qs.user_id = c.user_id )';
		$query .= " THEN 'complete' ELSE c.comment_approved END AS effective_status, COUNT( * ) AS total";
		$query .= $wpdb->prepare( " FROM {$wpdb->comments} c WHERE c.comment_type = %s", 'sensei_lesson_status' );

		$query .= $this->build_post_filter_clause( $args );
		$query .= $this->build_user_filter_clause( $args );
		$query .= $this->build_user_exclusion_clause( $args );

		if ( isset( $args['query'] ) ) {
			$query .= $args['query'];
		}

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
	 * Count progress records grouped by status for non-lesson types.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Query arguments (see count_statuses).
	 * @return array Associative array of status => count.
	 */
	private function count_generic_statuses( array $args ): array {
		$wpdb = $this->wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from wpdb.
		$query = $wpdb->prepare( "SELECT c.comment_approved, COUNT( * ) AS total FROM {$wpdb->comments} c WHERE c.comment_type = %s", 'sensei_course_status' );

		$query .= $this->build_post_filter_clause( $args );
		$query .= $this->build_user_filter_clause( $args );
		$query .= $this->build_user_exclusion_clause( $args );

		if ( isset( $args['query'] ) ) {
			$query .= $args['query'];
		}

		$query .= ' GROUP BY c.comment_approved';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared in advance. Caching handled by callers.
		$results = (array) $wpdb->get_results( $query, ARRAY_A );

		$counts = [];
		foreach ( $results as $row ) {
			$counts[ $row['comment_approved'] ] = (int) $row['total'];
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
			return $wpdb->prepare( " AND c.comment_post_ID IN ( $placeholders )", $args['post__in'] );
		}

		if ( ! empty( $args['post_id'] ) ) {
			return $wpdb->prepare( ' AND c.comment_post_ID = %d', $args['post_id'] );
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
			return $wpdb->prepare( " AND c.user_id IN ( $placeholders )", $args['user_id'] );
		}

		if ( ! empty( $args['user_id'] ) ) {
			return $wpdb->prepare( ' AND c.user_id = %d', $args['user_id'] );
		}

		return '';
	}

	/**
	 * Build SQL clause for excluding users by login prefix.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Query arguments.
	 * @return string SQL clause.
	 */
	private function build_user_exclusion_clause( array $args ): string {
		if ( empty( $args['exclude_user_login_prefixes'] ) ) {
			return '';
		}

		$wpdb             = $this->wpdb;
		$not_like_clauses = [];
		foreach ( $args['exclude_user_login_prefixes'] as $prefix ) {
			$escaped_prefix     = $wpdb->esc_like( $prefix );
			$not_like_clauses[] = $wpdb->prepare( 'c.comment_author NOT LIKE %s', $escaped_prefix . '%' );
		}

		$exclusion_sql = '( ' . implode( ' AND ', $not_like_clauses ) . ' )';

		if ( ! empty( $args['include_statuses_override'] ) ) {
			$status_placeholders = implode( ', ', array_fill( 0, count( $args['include_statuses_override'] ), '%s' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			$override_sql = $wpdb->prepare( "c.comment_approved IN ( $status_placeholders )", $args['include_statuses_override'] );
			return " AND ( $exclusion_sql OR $override_sql )";
		}

		return " AND $exclusion_sql";
	}
}

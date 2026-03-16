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
		$wpdb         = $this->wpdb;
		$comment_type = 'course' === $args['type'] ? 'sensei_course_status' : 'sensei_lesson_status';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from wpdb.
		$query = $wpdb->prepare( "SELECT comment_approved, COUNT( * ) AS total FROM {$wpdb->comments} WHERE comment_type = %s", $comment_type );

		$query .= $this->build_post_filter_clause( $args );
		$query .= $this->build_user_filter_clause( $args );
		$query .= $this->build_user_exclusion_clause( $args );

		if ( isset( $args['query'] ) ) {
			$query .= $args['query'];
		}

		$query .= ' GROUP BY comment_approved';

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
			return $wpdb->prepare( " AND comment_post_ID IN ( $placeholders )", $args['post__in'] );
		}

		if ( ! empty( $args['post_id'] ) ) {
			return $wpdb->prepare( ' AND comment_post_ID = %d', $args['post_id'] );
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

		if ( ! empty( $args['user_id'] ) && is_array( $args['user_id'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['user_id'] ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			return $wpdb->prepare( " AND user_id IN ( $placeholders )", $args['user_id'] );
		}

		if ( ! empty( $args['user_id'] ) ) {
			return $wpdb->prepare( ' AND user_id = %d', $args['user_id'] );
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

		$prefixes = array_filter( $args['exclude_user_login_prefixes'] );
		if ( empty( $prefixes ) ) {
			return '';
		}

		$wpdb             = $this->wpdb;
		$not_like_clauses = [];
		foreach ( $prefixes as $prefix ) {
			$escaped_prefix     = $wpdb->esc_like( $prefix );
			$not_like_clauses[] = $wpdb->prepare( 'comment_author NOT LIKE %s', $escaped_prefix . '%' );
		}

		$exclusion_sql = '( ' . implode( ' AND ', $not_like_clauses ) . ' )';

		if ( ! empty( $args['include_statuses_override'] ) ) {
			$status_placeholders = implode( ', ', array_fill( 0, count( $args['include_statuses_override'] ), '%s' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			$override_sql = $wpdb->prepare( "comment_approved IN ( $status_placeholders )", $args['include_statuses_override'] );
			return " AND ( $exclusion_sql OR $override_sql )";
		}

		return " AND $exclusion_sql";
	}
}

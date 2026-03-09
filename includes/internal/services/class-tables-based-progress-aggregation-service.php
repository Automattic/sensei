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
		$wpdb  = $this->wpdb;
		$table = $this->get_progress_table_name();

		// Build SELECT and FROM.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from wpdb prefix.
		$query = "SELECT p.status, COUNT(*) AS total FROM {$table} p";

		// If we need to exclude by user login prefix, JOIN wp_users.
		$has_exclusion = ! empty( $args['exclude_user_login_prefixes'] );
		if ( $has_exclusion ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from wpdb.
			$query .= " INNER JOIN {$wpdb->users} u ON p.user_id = u.ID";
		}

		// WHERE clause: type filter.
		$query .= $wpdb->prepare( ' WHERE p.type = %s', $args['type'] );

		// Restrict to specific posts.
		if ( ! empty( $args['post__in'] ) && is_array( $args['post__in'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['post__in'] ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			$query .= $wpdb->prepare( " AND p.post_id IN ( $placeholders )", $args['post__in'] );
		} elseif ( ! empty( $args['post_id'] ) ) {
			$query .= $wpdb->prepare( ' AND p.post_id = %d', $args['post_id'] );
		}

		// Restrict to specific users.
		if ( isset( $args['user_id'] ) && is_array( $args['user_id'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['user_id'] ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			$query .= $wpdb->prepare( " AND p.user_id IN ( $placeholders )", $args['user_id'] );
		} elseif ( ! empty( $args['user_id'] ) ) {
			$query .= $wpdb->prepare( ' AND p.user_id = %d', $args['user_id'] );
		}

		// Exclude users by login prefix, with optional status override.
		if ( $has_exclusion ) {
			$not_like_clauses = [];
			foreach ( $args['exclude_user_login_prefixes'] as $prefix ) {
				$escaped_prefix     = $wpdb->esc_like( $prefix );
				$not_like_clauses[] = $wpdb->prepare( 'u.user_login NOT LIKE %s', $escaped_prefix . '%' );
			}

			$exclusion_sql = '( ' . implode( ' AND ', $not_like_clauses ) . ' )';

			if ( ! empty( $args['include_statuses_override'] ) ) {
				$status_placeholders = implode( ', ', array_fill( 0, count( $args['include_statuses_override'] ), '%s' ) );
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
				$override_sql = $wpdb->prepare( "p.status IN ( $status_placeholders )", $args['include_statuses_override'] );
				$query       .= " AND ( $exclusion_sql OR $override_sql )";
			} else {
				$query .= " AND $exclusion_sql";
			}
		}

		$query .= ' GROUP BY p.status';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared in advance. Caching handled by callers.
		$results = (array) $wpdb->get_results( $query, ARRAY_A );

		$counts = [];
		foreach ( $results as $row ) {
			$counts[ $row['status'] ] = (int) $row['total'];
		}

		return $counts;
	}
}

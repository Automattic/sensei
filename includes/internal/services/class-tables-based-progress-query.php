<?php
/**
 * File containing the Tables_Based_Progress_Query class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shared query helpers for tables-based progress listings.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Tables_Based_Progress_Query {

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
	 * @return string
	 */
	private function get_progress_table_name(): string {
		return $this->wpdb->prefix . 'sensei_lms_progress';
	}

	/**
	 * Run the count query, snap the offset if it exceeds the total, and build
	 * ORDER BY / LIMIT clauses. Shared by all paginated query methods.
	 *
	 * @since $$next-version$$
	 *
	 * @param string $where SQL WHERE clause (already prepared).
	 * @param array  $args  Activity args containing number, offset, orderby, order.
	 * @return array{ total_count: int, order_clause: string, limit_clause: string }
	 */
	public function build_pagination( string $where, array $args ): array {
		$wpdb  = $this->wpdb;
		$table = $this->get_progress_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is a trusted $wpdb property; $where is built from $wpdb->prepare() calls.
		$total_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table` p" . $where );
		Utils::log_query_error( $wpdb, 'Tables-based reports pagination count' );

		$number = (int) ( $args['number'] ?? 0 );
		$offset = (int) ( $args['offset'] ?? 0 );
		if ( $number > 0 && $total_count > 0 && $offset >= $total_count ) {
			$last_page = max( 0, (int) ceil( $total_count / $number ) - 1 );
			$offset    = $last_page * $number;
		}

		return array(
			'total_count'  => $total_count,
			'order_clause' => $this->build_order_clause( $args ),
			'limit_clause' => $number > 0 ? (string) $wpdb->prepare( ' LIMIT %d OFFSET %d', $number, $offset ) : '',
		);
	}

	/**
	 * Translate the activity-args shape into shared SQL WHERE clauses.
	 *
	 * Applies post_id, user_id, status, and the meta_query entry with key 'start'
	 * (used by the course list table for start-date range filtering).
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Activity args.
	 * @return string SQL clauses, each prefixed with ' AND '.
	 */
	public function build_filters( array $args ): string {
		$wpdb = $this->wpdb;
		$sql  = '';

		if ( ! empty( $args['post_id'] ) ) {
			$sql .= $wpdb->prepare( ' AND p.post_id = %d', (int) $args['post_id'] );
		}

		if ( isset( $args['user_id'] ) && '' !== $args['user_id'] && array() !== $args['user_id'] ) {
			if ( is_array( $args['user_id'] ) ) {
				$ids          = array_map( 'intval', $args['user_id'] );
				$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$sql .= $wpdb->prepare( " AND p.user_id IN ( $placeholders )", $ids );
			} else {
				$sql .= $wpdb->prepare( ' AND p.user_id = %d', (int) $args['user_id'] );
			}
		}

		if ( ! empty( $args['status'] ) && 'any' !== $args['status'] ) {
			$statuses = array_map( 'strval', (array) $args['status'] );
			if ( ! empty( $statuses ) ) {
				$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$sql .= $wpdb->prepare( " AND p.status IN ( $placeholders )", $statuses );
			}
		}

		$sql .= $this->build_start_date_filter( $args );

		return $sql;
	}

	/**
	 * Translate a meta_query entry with key 'start' into started_at SQL.
	 *
	 * The course list table passes start-date range filters via meta_query
	 * (to remain compatible with the comments-based path). For tables-based
	 * storage the equivalent column is p.started_at.
	 *
	 * @param array $args Activity args.
	 * @return string SQL clause.
	 */
	private function build_start_date_filter( array $args ): string {
		if ( empty( $args['meta_query'] ) || ! is_array( $args['meta_query'] ) ) {
			return '';
		}

		$wpdb = $this->wpdb;
		$sql  = '';

		foreach ( $args['meta_query'] as $outer ) {
			if ( ! is_array( $outer ) ) {
				continue;
			}
			foreach ( $outer as $clause ) {
				if ( ! is_array( $clause ) || ( $clause['key'] ?? '' ) !== 'start' ) {
					continue;
				}
				$compare = $clause['compare'] ?? '=';
				$value   = $clause['value'] ?? '';
				if ( in_array( $compare, array( '>=', '<=', '>', '<', '=' ), true ) && '' !== $value ) {
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $compare whitelisted above.
					$sql .= $wpdb->prepare( " AND p.started_at {$compare} %s", $value );
				}
			}
		}

		return $sql;
	}

	/**
	 * Build ORDER BY clause.
	 *
	 * @param array $args Query arguments.
	 * @return string SQL ORDER BY clause.
	 */
	private function build_order_clause( array $args ): string {
		$order   = isset( $args['order'] ) && 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$orderby = $args['orderby'] ?? '';

		$orderby_map = array(
			'comment_date' => 'p.completed_at',
			'started'      => 'p.started_at',
			'completed'    => 'p.completed_at',
		);

		/**
		 * Sanitized column name.
		 *
		 * @var string $column
		 */
		$column = esc_sql( $orderby_map[ $orderby ] ?? 'p.started_at' );

		return " ORDER BY {$column} {$order}";
	}
}

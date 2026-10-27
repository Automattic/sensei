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
 * Prepared HPPS progress selections, independent of listing item formats.
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
	 * Prepare a progress relation and its filters.
	 *
	 * Criteria accept type, post_id, user_id (scalar or list), status (list),
	 * started_at (value/compare clauses), post_author, and user_exclusions
	 * (Utils::build_user_exclusion_clause arguments). Dates use stored UTC values.
	 * with_quiz_status adds lesson quiz progress as q via lesson metadata pm.
	 * status_source accepts 'progress' (default) or 'quiz' (quiz-aware status).
	 * Returned fields/from/where can be used with listing-specific metric joins.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $criteria Storage criteria.
	 * @return array{fields: string, from: string, where: string, count_from: string} Prepared selection.
	 */
	public function build_selection( array $criteria ): array {
		$wpdb            = $this->wpdb;
		$table           = $wpdb->prefix . 'sensei_lms_progress';
		$base_from       = " FROM `$table` p";
		$from            = $base_from;
		$fields          = 'p.post_id, p.user_id, p.status, p.started_at, p.completed_at';
		$use_quiz_status = 'quiz' === ( $criteria['status_source'] ?? 'progress' );
		$status_column   = $use_quiz_status ? 'COALESCE( q.status, p.status )' : 'p.status';

		if ( ! empty( $criteria['with_quiz_status'] ) || $use_quiz_status ) {
			$from   .= " LEFT JOIN `{$wpdb->postmeta}` pm ON pm.post_id = p.post_id AND pm.meta_key = '_lesson_quiz' AND pm.meta_value > 0";
			$from   .= " LEFT JOIN `$table` q ON q.post_id = pm.meta_value AND q.user_id = p.user_id AND q.type = 'quiz'";
			$fields .= ', COALESCE( q.status, p.status ) AS effective_status';
		}

		$where = $wpdb->prepare( ' WHERE p.type = %s', $criteria['type'] );
		if ( isset( $criteria['post_id'] ) ) {
			$where .= $wpdb->prepare( ' AND p.post_id = %d', (int) $criteria['post_id'] );
		}
		if ( isset( $criteria['user_id'] ) ) {
			$ids = array_map( 'intval', (array) $criteria['user_id'] );
			if ( empty( $ids ) ) {
				$where .= ' AND 1 = 0';
			} else {
				$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$where .= $wpdb->prepare( " AND p.user_id IN ( $placeholders )", $ids );
			}
		}
		if ( ! empty( $criteria['status'] ) ) {
			$statuses     = array_map( 'strval', (array) $criteria['status'] );
			$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Status expression is selected above.
			$where .= $wpdb->prepare( " AND $status_column IN ( $placeholders )", $statuses );
		}
		foreach ( $criteria['started_at'] ?? array() as $clause ) {
			$compare = $clause['compare'] ?? '=';
			$value   = $clause['value'] ?? '';
			if ( in_array( $compare, array( '>=', '<=', '>', '<', '=' ), true ) && '' !== $value ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Comparison is whitelisted above.
				$where .= $wpdb->prepare( " AND p.started_at {$compare} %s", $value );
			}
		}
		if ( ! empty( $criteria['post_author'] ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is a trusted wpdb property.
			$where .= $wpdb->prepare( " AND p.post_id IN ( SELECT ID FROM `{$wpdb->posts}` WHERE post_author = %d )", (int) $criteria['post_author'] );
		}
		$where .= Utils::build_user_exclusion_clause( $wpdb, $criteria['user_exclusions'] ?? array(), $status_column );

		return array(
			'fields'     => $fields,
			'from'       => $from,
			'where'      => $where,
			// Display-only quiz joins must not multiply raw progress counts.
			'count_from' => $use_quiz_status ? $from : $base_from,
		);
	}

	/**
	 * Count a prepared selection and build its ordering and page limits.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $selection  Selection returned by build_selection().
	 * @param array $pagination Number, offset, order, and orderby (started_at or completed_at).
	 * @return array{total_count: int, order_clause: string, limit_clause: string} Pagination clauses.
	 */
	public function build_pagination( array $selection, array $pagination ): array {
		$wpdb = $this->wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Selection is prepared by build_selection().
		$total_count = (int) $wpdb->get_var( 'SELECT COUNT(*)' . $selection['count_from'] . $selection['where'] );
		Utils::log_query_error( $wpdb, 'Tables-based progress pagination count' );

		$number = (int) ( $pagination['number'] ?? 0 );
		$offset = (int) ( $pagination['offset'] ?? 0 );
		if ( $number > 0 && $total_count > 0 && $offset >= $total_count ) {
			$last_page = max( 0, (int) ceil( $total_count / $number ) - 1 );
			$offset    = $last_page * $number;
		}

		$order  = isset( $pagination['order'] ) && 'ASC' === strtoupper( $pagination['order'] ) ? 'ASC' : 'DESC';
		$column = 'completed_at' === ( $pagination['orderby'] ?? '' ) ? 'p.completed_at' : 'p.started_at';

		return array(
			'total_count'  => $total_count,
			'order_clause' => " ORDER BY $column $order",
			'limit_clause' => $number > 0 ? (string) $wpdb->prepare( ' LIMIT %d OFFSET %d', $number, $offset ) : '',
		);
	}
}

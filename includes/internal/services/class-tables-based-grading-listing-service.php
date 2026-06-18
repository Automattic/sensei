<?php
/**
 * File containing the Tables_Based_Grading_Listing_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Tables_Based_Grading_Listing_Service.
 *
 * Tables-based (HPPS) implementation of the Grading_Listing_Service_Interface.
 *
 * @internal
 *
 * @since 4.26.0
 */
class Tables_Based_Grading_Listing_Service implements Grading_Listing_Service_Interface {

	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private \wpdb $wpdb;

	/**
	 * Cached per-status counts from the most recent query.
	 *
	 * @var array<string, int>|null
	 */
	private ?array $status_counts = null;

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
	 * Get lesson progress items for the grading listing.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{ items: Grading_Item[], total_count: int }
	 */
	public function get_lesson_progress_items( array $args ): array {
		$wpdb              = $this->wpdb;
		$table             = $this->get_progress_table_name();
		$submissions_table = $wpdb->prefix . 'sensei_lms_quiz_submissions';

		// Count all statuses in a single query for the All/Ungraded/Graded/In Progress tabs.
		$count_args           = $args;
		$count_args['status'] = 'any';
		// Get per-status counts from a single GROUP BY query.
		$status_count_query = $this->build_count_query( $table, $submissions_table, $count_args );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared via build_count_query. Caching handled by callers.
		$status_rows = (array) $wpdb->get_results( $status_count_query, ARRAY_A );
		Utils::log_query_error( $wpdb, 'Grading listing status counts' );

		$this->status_counts = [];
		foreach ( $status_rows as $row ) {
			$this->status_counts[ $row['effective_status'] ] = (int) $row['total'];
		}

		// Derive total_count from status counts.
		if ( empty( $args['status'] ) || 'any' === $args['status'] ) {
			$total_count = array_sum( $this->status_counts );
		} else {
			$total_count = 0;
			foreach ( (array) $args['status'] as $s ) {
				$total_count += $this->status_counts[ $s ] ?? 0;
			}
		}

		// Build the full base query WITH status filter for the paginated listing.
		$base_query = $this->build_base_query( $table, $submissions_table, $args );

		// If the requested offset is beyond the total (e.g. in case a search
		// threw off the pagination), snap back to the last valid page.
		$offset = $args['offset'] ?? 0;
		$number = $args['number'] ?? 10;
		if ( $number > 0 && $total_count > 0 && $offset >= $total_count ) {
			$last_page = max( 0, ceil( $total_count / $number ) - 1 );
			$offset    = (int) ( $last_page * $number );
		}

		// Append ordering and pagination to the base query for the items fetch.
		$items_query  = $base_query;
		$items_query .= $this->build_order_clause( $args );
		$items_query .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $number, $offset );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared via build_base_query. Caching handled by callers.
		$rows = (array) $wpdb->get_results( $items_query );
		Utils::log_query_error( $wpdb, 'Grading listing items' );

		$items = [];
		foreach ( $rows as $row ) {
			$items[] = new Grading_Item(
				$row->effective_status,
				(int) $row->user_id,
				(int) $row->post_id,
				get_date_from_gmt( $row->updated_at ),
				null !== $row->final_grade ? (float) $row->final_grade : null
			);
		}

		return [
			'items'       => $items,
			'total_count' => $total_count,
		];
	}

	/**
	 * Build the base SELECT query.
	 *
	 * @since 4.26.0
	 *
	 * @param string $table             Progress table name.
	 * @param string $submissions_table Quiz submissions table name.
	 * @param array  $args              Query arguments.
	 * @return string SQL query.
	 */
	private function build_base_query( string $table, string $submissions_table, array $args ): string {
		$wpdb = $this->wpdb;

		$query  = 'SELECT p.post_id, p.user_id, p.updated_at, COALESCE( q.status, p.status ) AS effective_status, qs.final_grade';
		$query .= " FROM {$table} p";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from wpdb prefix.
		$query .= " INNER JOIN {$wpdb->posts} post ON post.ID = p.post_id AND post.post_status IN ( 'publish', 'private' )";
		$query .= " LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.post_id AND pm.meta_key = '_lesson_quiz' AND pm.meta_value > 0";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names from wpdb prefix.
		$query .= " LEFT JOIN {$submissions_table} qs ON qs.quiz_id = pm.meta_value AND qs.user_id = p.user_id";
		// Quiz progress is joined without requiring a submission to exist,
		// so that the effective_status reflects the quiz result even when
		// the quiz_submissions row is missing (e.g. migrated data).
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names from wpdb prefix.
		$query .= " LEFT JOIN {$table} q ON q.post_id = pm.meta_value AND q.user_id = p.user_id AND q.type = 'quiz'";
		$query .= " WHERE p.type = 'lesson'";

		$query .= $this->build_post_filter( $args );
		$query .= $this->build_user_filter( $args );
		$query .= $this->build_user_exclusion_filter( $args );
		$query .= $this->build_status_filter( $args );

		return $query;
	}

	/**
	 * Build a per-status count query using the same JOINs/filters as the base query.
	 *
	 * @since 4.26.0
	 *
	 * @param string $table             Progress table name.
	 * @param string $submissions_table Quiz submissions table name.
	 * @param array  $args              Query arguments.
	 * @return string SQL query.
	 */
	private function build_count_query( string $table, string $submissions_table, array $args ): string {
		$wpdb = $this->wpdb;

		$query  = 'SELECT COALESCE( q.status, p.status ) AS effective_status, COUNT(*) AS total';
		$query .= " FROM {$table} p";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from wpdb prefix.
		$query .= " INNER JOIN {$wpdb->posts} post ON post.ID = p.post_id AND post.post_status IN ( 'publish', 'private' )";
		$query .= " LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.post_id AND pm.meta_key = '_lesson_quiz' AND pm.meta_value > 0";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names from wpdb prefix.
		$query .= " LEFT JOIN {$submissions_table} qs ON qs.quiz_id = pm.meta_value AND qs.user_id = p.user_id";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names from wpdb prefix.
		$query .= " LEFT JOIN {$table} q ON q.post_id = pm.meta_value AND q.user_id = p.user_id AND q.type = 'quiz'";
		$query .= " WHERE p.type = 'lesson'";

		$query .= $this->build_post_filter( $args );
		$query .= $this->build_user_filter( $args );
		$query .= $this->build_user_exclusion_filter( $args );
		$query .= $this->build_status_filter( $args );

		$query .= ' GROUP BY effective_status';

		return $query;
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

		if ( ! empty( $args['post__in'] ) && is_array( $args['post__in'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['post__in'] ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
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
	 * @since 4.26.0
	 *
	 * @param array $args Query arguments.
	 * @return string SQL clause.
	 */
	private function build_user_filter( array $args ): string {
		$wpdb = $this->wpdb;

		if ( ! empty( $args['user_id'] ) && is_array( $args['user_id'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['user_id'] ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
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
	 * @param array $args Query arguments.
	 * @return string SQL clause.
	 */
	private function build_user_exclusion_filter( array $args ): string {
		$status_column = 'COALESCE( q.status, p.status )';
		return Utils::build_user_exclusion_clause( $this->wpdb, $args, $status_column );
	}

	/**
	 * Build SQL clause for filtering by status.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Query arguments.
	 * @return string SQL clause.
	 */
	private function build_status_filter( array $args ): string {
		if ( empty( $args['status'] ) || 'any' === $args['status'] ) {
			return '';
		}

		$wpdb     = $this->wpdb;
		$statuses = (array) $args['status'];

		$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		return $wpdb->prepare( " AND COALESCE( q.status, p.status ) IN ( $placeholders )", $statuses );
	}

	/**
	 * Get cached per-status counts from the most recent query.
	 *
	 * Returns null if counts are not available (e.g. if
	 * get_lesson_progress_items has not been called yet).
	 *
	 * @since 4.26.0
	 *
	 * @return array<string, int>|null Associative array of status => count, or null.
	 */
	public function get_status_counts(): ?array {
		return $this->status_counts;
	}

	/**
	 * Build ORDER BY clause.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Query arguments.
	 * @return string SQL ORDER BY clause.
	 */
	private function build_order_clause( array $args ): string {
		$order   = isset( $args['order'] ) && 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$orderby = $args['orderby'] ?? '';

		// Title, course, and lesson columns map to post_id as a simplified
		// approximation — actual title/course name ordering would require
		// additional JOINs that are not worth the performance cost here.
		// This means rows are sorted by numeric ID rather than alphabetically.
		$orderby_map = [
			'title'       => 'p.post_id',
			'course'      => 'p.post_id',
			'lesson'      => 'p.post_id',
			'updated'     => 'p.updated_at',
			'user_status' => 'effective_status',
			'user_grade'  => 'qs.final_grade',
		];

		$column = esc_sql( $orderby_map[ $orderby ] ?? 'p.updated_at' );

		return " ORDER BY $column $order";
	}
}

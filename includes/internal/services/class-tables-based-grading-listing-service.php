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
 * @since $$next-version$$
 */
class Tables_Based_Grading_Listing_Service implements Grading_Listing_Service_Interface {

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
	 * @return string
	 */
	private function get_progress_table_name(): string {
		return $this->wpdb->prefix . 'sensei_lms_progress';
	}

	/**
	 * Get lesson progress items for the grading listing.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{ items: Grading_Item[], total_count: int }
	 */
	public function get_lesson_progress_items( array $args ): array {
		$wpdb              = $this->wpdb;
		$table             = $this->get_progress_table_name();
		$submissions_table = $wpdb->prefix . 'sensei_lms_quiz_submissions';

		// Build the base query with JOINs for quiz status coalescing and
		// grade retrieval, plus any post/user/status filters.
		$base_query = $this->build_base_query( $table, $submissions_table, $args );

		// Get total count by wrapping the filtered base query in a COUNT(*).
		$count_query = "SELECT COUNT(*) FROM ( $base_query ) AS counted";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared via build_base_query. Caching handled by callers.
		$total_count = (int) $wpdb->get_var( $count_query );

		// If the requested offset is beyond the total (e.g. in case a search
		// threw off the pagination), snap back to the last valid page.
		$offset = $args['offset'] ?? 0;
		$number = $args['number'] ?? 10;
		if ( $number > 0 && $total_count < $offset ) {
			$new_paged = floor( $total_count / $number );
			$offset    = $new_paged * $number;
		}

		// Append ordering and pagination to the base query for the items fetch.
		$items_query  = $base_query;
		$items_query .= $this->build_order_clause( $args );
		$items_query .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $number, $offset );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared via build_base_query. Caching handled by callers.
		$rows = (array) $wpdb->get_results( $items_query );

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
	 * @since $$next-version$$
	 *
	 * @param string $table             Progress table name.
	 * @param string $submissions_table Quiz submissions table name.
	 * @param array  $args              Query arguments.
	 * @return string SQL query.
	 */
	private function build_base_query( string $table, string $submissions_table, array $args ): string {
		$wpdb = $this->wpdb;

		$query  = 'SELECT p.post_id, p.user_id, p.updated_at, COALESCE( CASE WHEN qs.id IS NOT NULL THEN q.status END, p.status ) AS effective_status, qs.final_grade';
		$query .= " FROM {$table} p";
		$query .= " LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.post_id AND pm.meta_key = '_lesson_quiz' AND pm.meta_value > 0";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names from wpdb prefix.
		$query .= " LEFT JOIN {$submissions_table} qs ON qs.quiz_id = pm.meta_value AND qs.user_id = p.user_id";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names from wpdb prefix.
		$query .= " LEFT JOIN {$table} q ON q.post_id = pm.meta_value AND q.user_id = p.user_id AND q.type = 'quiz'";
		$query .= " WHERE p.type = 'lesson'";
		// Exclude lessons where a quiz exists but the student never submitted it
		// and the lesson is already complete — there is nothing to grade.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names from wpdb prefix.
		$query .= " AND NOT ( pm.meta_value IS NOT NULL AND qs.id IS NULL AND p.status = 'complete' )";

		$query .= $this->build_post_filter( $args );
		$query .= $this->build_user_filter( $args );
		$query .= $this->build_status_filter( $args );

		return $query;
	}

	/**
	 * Build SQL clause for filtering by post ID(s).
	 *
	 * @since $$next-version$$
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
	 * @since $$next-version$$
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
	 * Build SQL clause for filtering by status.
	 *
	 * @since $$next-version$$
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
		return $wpdb->prepare( " AND COALESCE( CASE WHEN qs.id IS NOT NULL THEN q.status END, p.status ) IN ( $placeholders )", $statuses );
	}

	/**
	 * Build ORDER BY clause.
	 *
	 * @since $$next-version$$
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

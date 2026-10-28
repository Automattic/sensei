<?php
/**
 * Comments-backed student management listings.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads student management activity from comments.
 *
 * @internal
 * @since $$next-version$$
 */
class Comments_Based_Student_Management_Listing_Service implements Student_Management_Listing_Service_Interface {

	/**
	 * Get student activity and its pagination total.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{items: Student_Management_Item[], total_count: int|false} Activity rows and total.
	 */
	public function get_students( array $args ): array {
		// WP_Comment_Query doesn't support SQL_CALC_FOUND_ROWS, so instead do this twice.
		$total_students = \Sensei_Utils::sensei_check_for_activity(
			array_merge(
				$args,
				array(
					'count'  => true,
					'offset' => 0,
					'number' => 0,
				)
			)
		);

		// Ensure we change our range to fit (in case a search threw off the pagination).
		if ( $total_students < $args['offset'] ) {
			$new_paged      = floor( $total_students / $args['number'] );
			$args['offset'] = $new_paged * $args['number'];
		}

		$students = $this->query_students( $args );

		// Need to always return an array, even with only 1 item.
		if ( ! is_array( $students ) ) {
			$students = array( $students );
		}

		$items = array();

		foreach ( $students as $comment ) {
			if ( ! $comment instanceof \WP_Comment ) {
				continue;
			}

			$items[] = new Student_Management_Item(
				(int) $args['post_id'],
				(int) $comment->user_id,
				$comment->comment_approved,
				'complete' === $comment->comment_approved ? $comment->comment_date : null,
				$comment->comment_date
			);
		}

		return array(
			'items'       => $items,
			'total_count' => $total_students,
		);
	}

	/**
	 * Count student activity.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return int|false Activity count, or false for an invalid user ID.
	 */
	public function count_students( array $args ) {
		return \Sensei_Utils::sensei_check_for_activity( $args );
	}

	/**
	 * Apply name ordering to the requested student query before pagination.
	 *
	 * @param array $args Student activity arguments.
	 * @return array|\WP_Comment|false Activity rows, one row, or an invalid-user result.
	 */
	private function query_students( array $args ) {
		if ( 'title' !== ( $args['orderby'] ?? '' ) ) {
			return \Sensei_Utils::sensei_check_for_activity( $args, true );
		}

		global $wpdb;

		$order = 'ASC' === strtoupper( $args['order'] ?? '' ) ? 'ASC' : 'DESC';
		$name  = Utils::get_student_name_sql( $wpdb, "{$wpdb->comments}.user_id" );
		$sort  = static function ( array $clauses, \WP_Comment_Query $query ) use ( $args, $wpdb, $order, $name ): array {
			if ( 'title' === $query->query_vars['orderby']
				&& $args['type'] === $query->query_vars['type'] ) {
				$clauses['orderby'] = "$name $order, {$wpdb->comments}.comment_ID $order";
			}

			return $clauses;
		};
		add_filter( 'comments_clauses', $sort, 10, 2 );

		try {
			return \Sensei_Utils::sensei_check_for_activity( $args, true );
		} finally {
			remove_filter( 'comments_clauses', $sort, 10 );
		}
	}
}

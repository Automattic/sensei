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

		$students = \Sensei_Utils::sensei_check_for_activity( $args, true );

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
				'complete' === $comment->comment_approved ? $comment->comment_date : null
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
}

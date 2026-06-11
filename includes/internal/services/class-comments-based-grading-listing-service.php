<?php
/**
 * File containing the Comments_Based_Grading_Listing_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Comments_Based_Grading_Listing_Service.
 *
 * Comments-based implementation of the Grading_Listing_Service_Interface.
 *
 * @internal
 *
 * @since 4.26.0
 */
class Comments_Based_Grading_Listing_Service implements Grading_Listing_Service_Interface {

	/**
	 * Get lesson progress items for the grading listing.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{ items: Grading_Item[], total_count: int }
	 */
	public function get_lesson_progress_items( array $args ): array {
		$args['post_status'] = array( 'publish', 'private' );

		// WP_Comment_Query doesn't support SQL_CALC_FOUND_ROWS, so run
		// a separate count query first with no limit/offset.
		$total_count = \Sensei_Utils::sensei_check_for_activity(
			array_merge(
				$args,
				[
					'count'  => true,
					'offset' => 0,
					'number' => 0,
				]
			)
		);

		// If the requested offset is beyond the total (e.g. in case a search
		// threw off the pagination), snap back to the last valid page.
		$offset = $args['offset'] ?? 0;
		$number = $args['number'] ?? 10;
		if ( $number > 0 && $total_count > 0 && $offset >= $total_count ) {
			$last_page      = max( 0, (int) ceil( $total_count / $number ) - 1 );
			$args['offset'] = $last_page * $number;
		}

		$statuses = \Sensei_Utils::sensei_check_for_activity( $args, true );

		// sensei_check_for_activity returns a single object when there is
		// exactly one result — normalize to an array.
		if ( ! is_array( $statuses ) ) {
			$statuses = [ $statuses ];
		}

		$items = [];
		foreach ( $statuses as $comment ) {
			// sensei_check_for_activity can return false when no results are
			// found; skip anything that isn't a real comment.
			if ( ! $comment instanceof \WP_Comment ) {
				continue;
			}

			$grade_value = get_comment_meta( $comment->comment_ID, 'grade', true );

			$items[] = new Grading_Item(
				$comment->comment_approved,
				(int) $comment->user_id,
				(int) $comment->comment_post_ID,
				$comment->comment_date,
				'' !== $grade_value ? (float) $grade_value : null
			);
		}

		return [
			'items'       => $items,
			'total_count' => (int) $total_count,
		];
	}

	/**
	 * Get cached per-status counts.
	 *
	 * Not supported by comments-based implementation.
	 *
	 * @since 4.26.0
	 *
	 * @return array<string, int>|null Always null for comments-based storage.
	 */
	public function get_status_counts(): ?array {
		return null;
	}
}

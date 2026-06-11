<?php
/**
 * File containing the Grading_Listing_Service_Interface interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Interface Grading_Listing_Service_Interface.
 *
 * @internal
 *
 * @since 4.26.0
 */
interface Grading_Listing_Service_Interface {

	/**
	 * Get lesson progress items for the grading listing.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args {
	 *     Arguments for the query.
	 *
	 *     @type string       $type     Progress type identifier (e.g. 'sensei_lesson_status'). Passed through from the caller.
	 *     @type int          $number   Number of items to return.
	 *     @type int          $offset   Offset for pagination.
	 *     @type string       $orderby  Column to order by.
	 *     @type string       $order    'ASC' or 'DESC'.
	 *     @type string|array $status   Status filter ('any' for all).
	 *     @type int          $post_id  Restrict to a single post ID.
	 *     @type int[]        $post__in Restrict to specific post IDs.
	 *     @type int|int[]    $user_id  Restrict to specific user(s).
	 *     @type string[]     $exclude_user_login_prefixes User login prefixes to exclude.
	 *     @type string[]     $include_statuses_override Statuses that bypass user exclusion.
	 * }
	 * @return array{ items: Grading_Item[], total_count: int }
	 */
	public function get_lesson_progress_items( array $args ): array;

	/**
	 * Get cached per-status counts from the most recent query.
	 *
	 * Returns null if counts are not available (e.g. comments-based
	 * implementation, or if get_lesson_progress_items has not been called yet).
	 *
	 * @since 4.26.0
	 *
	 * @return array<string, int>|null Associative array of status => count, or null.
	 */
	public function get_status_counts(): ?array;
}

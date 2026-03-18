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
 * @since $$next-version$$
 */
interface Grading_Listing_Service_Interface {

	/**
	 * Get lesson progress items for the grading listing.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args {
	 *     Arguments for the query.
	 *
	 *     @type string       $type     Comment type, e.g. 'sensei_lesson_status'.
	 *     @type int          $number   Number of items to return.
	 *     @type int          $offset   Offset for pagination.
	 *     @type string       $orderby  Column to order by.
	 *     @type string       $order    'ASC' or 'DESC'.
	 *     @type string|array $status   Status filter ('any' for all).
	 *     @type int          $post_id  Restrict to a single post ID.
	 *     @type int[]        $post__in Restrict to specific post IDs.
	 *     @type int|int[]    $user_id  Restrict to specific user(s).
	 * }
	 * @return array{ items: Grading_Item[], total_count: int }
	 */
	public function get_lesson_progress_items( array $args ): array;
}

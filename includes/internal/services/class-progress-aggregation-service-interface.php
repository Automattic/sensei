<?php
/**
 * File containing the Progress_Aggregation_Service_Interface interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Interface Progress_Aggregation_Service_Interface.
 *
 * Provides methods to aggregate progress data such as counting
 * progress records grouped by status.
 *
 * @internal
 *
 * @since $$next-version$$
 */
interface Progress_Aggregation_Service_Interface {

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
	 *     @type string[]  $exclude_user_login_prefixes           User login prefixes to exclude.
	 *     @type string[]  $include_statuses_override             Statuses that bypass user exclusion.
	 * }
	 * @return array Associative array of status => count.
	 */
	public function count_statuses( array $args ): array;

	/**
	 * Get aggregate totals for a set of lessons.
	 *
	 * @since $$next-version$$
	 *
	 * @param int[] $lesson_ids Array of lesson post IDs.
	 * @return array {
	 *     @type int $unique_student_count   Number of distinct students.
	 *     @type int $lesson_start_count     Number of lesson starts.
	 *     @type int $lesson_completed_count Number of lessons with a finished status (complete, graded, passed, failed, or ungraded).
	 *     @type int $days_to_complete_count Number of lessons with a valid completion date.
	 *     @type int $days_to_complete_sum   Sum of days to complete.
	 * }
	 */
	public function get_lesson_totals( array $lesson_ids ): array;
}

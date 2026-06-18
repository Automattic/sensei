<?php
/**
 * File containing the Grading_Stats_Service_Interface interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Interface Grading_Stats_Service_Interface.
 *
 * Provides methods to retrieve grade statistics such as
 * totals, averages per course, and averages per user.
 *
 * @internal
 *
 * @since 4.26.0
 */
interface Grading_Stats_Service_Interface {

	/**
	 * Get grade count and sum, with optional filters.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args {
	 *     Optional filters.
	 *
	 *     @type int   $user_id   Filter by user.
	 *     @type int   $lesson_id Filter by lesson (post_id).
	 *     @type int[] $post__in  Filter by lesson IDs.
	 * }
	 * @return array{count: int, sum: float}
	 */
	public function get_grade_totals( array $args = array() ): array;

	/**
	 * Average grade across courses (AVG of per-course AVGs).
	 * Only includes student attempts where the quiz was actually submitted.
	 *
	 * @since 4.26.0
	 *
	 * @param int[] $course_ids Optional. Filter by courses. Empty = all.
	 * @return float
	 */
	public function get_courses_average_grade( array $course_ids = array() ): float;

	/**
	 * Average grade filtered by user IDs.
	 *
	 * @since 4.26.0
	 *
	 * @param int[] $user_ids User IDs to include.
	 * @return float
	 */
	public function get_users_average_grade( array $user_ids ): float;
}

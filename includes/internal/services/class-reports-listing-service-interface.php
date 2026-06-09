<?php
/**
 * File containing the Reports_Listing_Service_Interface interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Interface Reports_Listing_Service_Interface.
 *
 * The paginated methods accept comments-API-shaped activity arguments so that
 * existing `sensei_analysis_*` filters can continue to modify the query in the
 * same way they do for the legacy comments-based path.
 *
 * @internal
 *
 * @since 4.26.0
 */
interface Reports_Listing_Service_Interface {

	/**
	 * Get paginated users' progress on a specific lesson.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args {
	 *     Comments-API-shaped activity arguments.
	 *
	 *     @type int          $post_id Lesson post ID.
	 *     @type string       $type    Activity type (expected: 'sensei_lesson_status').
	 *     @type int          $number  Items per page.
	 *     @type int          $offset  Pagination offset.
	 *     @type string       $orderby Order by field.
	 *     @type string       $order   ASC or DESC.
	 *     @type string|array $status  Status filter ('any' for all).
	 *     @type int|int[]    $user_id Restrict to specific user(s).
	 * }
	 * @return array{ items: Reports_Item[], total_count: int }
	 */
	public function get_lesson_students( array $args ): array;

	/**
	 * Get paginated users' progress on a specific course.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args {
	 *     Comments-API-shaped activity arguments.
	 *
	 *     @type int          $post_id    Course post ID.
	 *     @type string       $type       Activity type (expected: 'sensei_course_status').
	 *     @type int          $number     Items per page.
	 *     @type int          $offset     Pagination offset.
	 *     @type string       $orderby    Order by field.
	 *     @type string       $order      ASC or DESC.
	 *     @type string|array $status     Status filter ('any' for all).
	 *     @type int|int[]    $user_id    Restrict to specific user(s).
	 *     @type array        $meta_query Meta query (used for start-date range filter with key 'start').
	 * }
	 * @return array{ items: Reports_Item[], total_count: int }
	 */
	public function get_course_students( array $args ): array;

	/**
	 * Get a single lesson's progress for one user.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Comments-API-shaped activity arguments (post_id, user_id, type, status).
	 * @return Reports_Item|null Null when the user has no progress on this lesson.
	 */
	public function get_user_lesson_progress( array $args ): ?Reports_Item;

	/**
	 * Get paginated course progress for a specific user.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args {
	 *     Comments-API-shaped activity arguments.
	 *
	 *     @type int          $user_id     User ID.
	 *     @type string       $type        Activity type (expected: 'sensei_course_status').
	 *     @type int          $number      Items per page.
	 *     @type int          $offset      Pagination offset.
	 *     @type string       $orderby     Order by field.
	 *     @type string       $order       ASC or DESC.
	 *     @type string|array $status      Status filter ('any' for all).
	 *     @type int          $post_author Restrict to courses authored by this user ID.
	 * }
	 * @return array{ items: Reports_Item[], total_count: int }
	 */
	public function get_user_courses( array $args ): array;

	/**
	 * Count students with activity on a lesson.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Comments-API-shaped activity arguments.
	 * @return int
	 */
	public function get_lesson_student_count( array $args ): int;

	/**
	 * Count students who completed a lesson.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Comments-API-shaped activity arguments.
	 * @return int
	 */
	public function get_lesson_completion_count( array $args ): int;

	/**
	 * Get the average quiz grade for a lesson.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Comments-API-shaped activity arguments (post_id, type, status, meta_key).
	 * @return float|null Null when no graded submissions exist.
	 */
	public function get_lesson_average_grade( array $args ): ?float;
}

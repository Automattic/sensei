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
 * @since $$next-version$$
 */
interface Reports_Listing_Service_Interface {

	/**
	 * Get paginated users' progress on a specific lesson.
	 *
	 * @since $$next-version$$
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
	 * @since $$next-version$$
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
	 * Get lesson progress for one user in a course.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $course_id Course post ID.
	 * @param int $user_id   User ID.
	 * @return array<int, Reports_Item|null> One item per lesson, keyed by lesson ID. Null for lessons with no progress.
	 */
	public function get_user_lesson_progress( int $course_id, int $user_id ): array;

	/**
	 * Get paginated course progress for a specific user.
	 *
	 * @since $$next-version$$
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
	 * Get per-lesson aggregate stats for a course overview.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $course_id Course post ID.
	 * @return array[] Array of associative arrays with keys: lesson_id, student_count, completion_count, average_grade.
	 */
	public function get_lesson_aggregates( int $course_id ): array;
}

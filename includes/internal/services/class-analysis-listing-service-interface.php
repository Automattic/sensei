<?php
/**
 * File containing the Analysis_Listing_Service_Interface interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Interface Analysis_Listing_Service_Interface.
 *
 * @internal
 *
 * @since $$next-version$$
 */
interface Analysis_Listing_Service_Interface {

	/**
	 * Get paginated users' progress on a specific lesson.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args {
	 *     Arguments for the query.
	 *
	 *     @type int    $lesson_id Lesson post ID. Required.
	 *     @type int    $per_page  Items per page.
	 *     @type int    $offset    Pagination offset.
	 *     @type string $orderby   Order by field.
	 *     @type string $order     ASC or DESC.
	 *     @type string $search    Search term for user name.
	 * }
	 * @return array{ items: Analysis_Item[], total_count: int }
	 */
	public function get_lesson_students( array $args ): array;

	/**
	 * Get paginated users' progress on a specific course.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args {
	 *     Arguments for the query.
	 *
	 *     @type int    $course_id Course post ID. Required.
	 *     @type int    $per_page  Items per page.
	 *     @type int    $offset    Pagination offset.
	 *     @type string $orderby   Order by field.
	 *     @type string $order     ASC or DESC.
	 *     @type string $search    Search term for user name.
	 * }
	 * @return array{ items: Analysis_Item[], total_count: int }
	 */
	public function get_course_students( array $args ): array;

	/**
	 * Get lesson progress for one user in a course.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $course_id Course post ID.
	 * @param int $user_id   User ID.
	 * @return Analysis_Item[] One item per lesson.
	 */
	public function get_user_lesson_progress( int $course_id, int $user_id ): array;

	/**
	 * Get paginated course progress for a specific user.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args {
	 *     Arguments for the query.
	 *
	 *     @type int    $user_id   User ID. Required.
	 *     @type int    $per_page  Items per page.
	 *     @type int    $offset    Pagination offset.
	 *     @type string $orderby   Order by field.
	 *     @type string $order     ASC or DESC.
	 * }
	 * @return array{ items: Analysis_Item[], total_count: int }
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

<?php
/**
 * File containing the Grading_Queries_Interface interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Grading;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Grading_Queries_Interface.
 *
 * @internal
 *
 * @since $$next-version$$
 */
interface Grading_Queries_Interface {

	/**
	 * Count statuses for a given type.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Arguments: type (course|lesson), post__in, post_id, user_id, query.
	 * @return array Associative array of status => count.
	 */
	public function count_statuses( array $args ): array;

	/**
	 * Get the sum of all user grades for a course.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $course_id Course ID.
	 * @return int Sum of grades.
	 */
	public function get_course_users_grades_sum( int $course_id ): int;

	/**
	 * Get average grade across all courses.
	 *
	 * @since $$next-version$$
	 *
	 * @return float Average grade.
	 */
	public function get_courses_average_grade(): float;

	/**
	 * Get grading items (lesson statuses) for the grading list table.
	 *
	 * Returns objects with properties compatible with WP_Comment:
	 * comment_ID, comment_approved, comment_date, user_id, comment_post_ID.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Query arguments: type, number, offset, orderby, order, status, post_id, post__in, user_id.
	 * @return array Array of objects.
	 */
	public function get_grading_items( array $args ): array;

	/**
	 * Count grading items matching the given arguments.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Query arguments (same as get_grading_items).
	 * @return int Count of matching items.
	 */
	public function count_grading_items( array $args ): int;

	/**
	 * Get the grade for a grading item.
	 *
	 * @since $$next-version$$
	 *
	 * @param object $item A grading item (from get_grading_items).
	 * @return string|null The grade value, or null if not available.
	 */
	public function get_item_grade( object $item ): ?string;
}

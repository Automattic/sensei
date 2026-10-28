<?php
/**
 * Student management listing contract.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads student activity for course and lesson management.
 *
 * @internal
 * @since $$next-version$$
 */
interface Student_Management_Listing_Service_Interface {

	/**
	 * Get student activity and its pagination total.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Activity arguments. post_id is a course or lesson ID; type is
	 *                    sensei_course_status or sensei_lesson_status, respectively.
	 *                    Also accepts user_id, status, number, offset, orderby, and order.
	 * @return array{items: Student_Management_Item[], total_count: int|false} Activity rows and total.
	 */
	public function get_students( array $args ): array;

	/**
	 * Count student activity.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Student activity arguments. post_id is a course or lesson ID;
	 *                    type is sensei_course_status or sensei_lesson_status, respectively.
	 * @return int|false Activity count, or false for an invalid user ID.
	 */
	public function count_students( array $args );
}

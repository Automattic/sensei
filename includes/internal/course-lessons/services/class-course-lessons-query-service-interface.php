<?php
/**
 * File containing the Course_Lessons_Query_Service_Interface interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Course_Lessons\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Course_Lessons_Query_Service_Interface.
 *
 * Resolves the course structure conditions a lesson WP_Query asks for in domain terms,
 * so the query does not depend on where the structure is stored.
 *
 * @internal
 *
 * @since 4.27.0
 */
interface Course_Lessons_Query_Service_Interface {
	/**
	 * Limit a lesson query to the lessons of the course given in its `sensei_course` query var.
	 *
	 * @internal
	 *
	 * @param \WP_Query $query The query being prepared.
	 */
	public function filter_lessons_by_course( \WP_Query $query ): void;

	/**
	 * Limit a lesson query to the lessons without a course when its `sensei_without_course` query var is set.
	 *
	 * @internal
	 *
	 * @param \WP_Query $query The query being prepared.
	 */
	public function filter_lessons_without_course( \WP_Query $query ): void;

	/**
	 * Limit a lesson query to the lessons with a prerequisite when its `sensei_has_prerequisite` query var is set.
	 *
	 * @internal
	 *
	 * @param \WP_Query $query The query being prepared.
	 */
	public function filter_lessons_with_prerequisite( \WP_Query $query ): void;

	/**
	 * Limit a lesson query to the lessons of the module set in the `sensei_module` query var.
	 *
	 * @internal
	 *
	 * @param \WP_Query $query The query being prepared.
	 */
	public function filter_lessons_by_module( \WP_Query $query ): void;

	/**
	 * Limit a lesson query to the lessons outside the modules set in the `sensei_module__not_in` query var.
	 *
	 * @internal
	 *
	 * @param \WP_Query $query The query being prepared.
	 */
	public function filter_lessons_outside_modules( \WP_Query $query ): void;

	/**
	 * Limit a lesson query to the lessons in a module when its `sensei_has_module` query var is set.
	 *
	 * @internal
	 *
	 * @param \WP_Query $query The query being prepared.
	 */
	public function filter_lessons_with_module( \WP_Query $query ): void;

	/**
	 * Sort the lessons of a query by their order in the course set in the `sensei_lesson_order_course` query var.
	 *
	 * Lessons without an order in the course are not returned.
	 *
	 * @internal
	 *
	 * @param \WP_Query $query The query being prepared.
	 */
	public function order_lessons_in_course( \WP_Query $query ): void;

	/**
	 * Sort the lessons of a query by their order in the module set in the `sensei_lesson_order_module` query var.
	 *
	 * Lessons without an order in the module are not returned.
	 *
	 * @internal
	 *
	 * @param \WP_Query $query The query being prepared.
	 */
	public function order_lessons_in_module( \WP_Query $query ): void;
}

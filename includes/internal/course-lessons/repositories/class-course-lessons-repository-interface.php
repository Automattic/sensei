<?php
/**
 * File containing the Course_Lessons_Repository_Interface interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Course_Lessons\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Course_Lessons_Repository_Interface.
 *
 * @internal
 *
 * @since $$next-version$$
 */
interface Course_Lessons_Repository_Interface {
	/**
	 * Get the course a lesson is attached to.
	 *
	 * @internal
	 *
	 * @param int $lesson_id Lesson ID.
	 * @return int|null Course ID, or null when the lesson has no course.
	 */
	public function get_course_id( int $lesson_id ): ?int;

	/**
	 * Get the prerequisite lesson ID set on a lesson.
	 *
	 * @internal
	 *
	 * @param int $lesson_id Lesson ID.
	 * @return int|null Prerequisite lesson ID, or null when none is set.
	 */
	public function get_lesson_prerequisite_id( int $lesson_id ): ?int;

	/**
	 * Get the course of each of the given lessons.
	 *
	 * @internal
	 *
	 * @param array $lesson_ids Lesson IDs.
	 * @return array<int, int|false> Course ID keyed by lesson ID, or false when the lesson has no existing course.
	 */
	public function get_course_ids( array $lesson_ids ): array;

	/**
	 * Get the IDs of the lessons attached to a course.
	 *
	 * @internal
	 *
	 * @param int             $course_id   Course ID.
	 * @param string|string[] $post_status Lesson post status or statuses. Default 'publish'.
	 * @return int[] Lesson IDs, in no particular order.
	 */
	public function get_lesson_ids( int $course_id, $post_status = 'publish' ): array;

	/**
	 * Get the order of a lesson in a course.
	 *
	 * @internal
	 *
	 * @param int $lesson_id Lesson ID.
	 * @param int $course_id Course ID.
	 * @return int|null Order, or null when none is stored for that course.
	 */
	public function get_lesson_order( int $lesson_id, int $course_id ): ?int;

	/**
	 * Get the module of a lesson.
	 *
	 * @internal
	 *
	 * @param int $lesson_id Lesson ID.
	 * @return \WP_Term|null The module term, or null when the lesson has none or a filter returns no term.
	 */
	public function get_lesson_module( int $lesson_id ): ?\WP_Term;

	/**
	 * Whether a lesson is in the given module.
	 *
	 * @internal
	 *
	 * @param int $lesson_id Lesson ID.
	 * @param int $term_id   Module term ID.
	 * @return bool
	 */
	public function has_lesson_module( int $lesson_id, int $term_id ): bool;

	/**
	 * Set the prerequisite of a lesson.
	 *
	 * @internal
	 *
	 * @param int $lesson_id              Lesson ID.
	 * @param int $prerequisite_lesson_id Prerequisite lesson ID.
	 */
	public function set_lesson_prerequisite( int $lesson_id, int $prerequisite_lesson_id ): void;

	/**
	 * Delete the prerequisite of a lesson.
	 *
	 * @internal
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	public function delete_lesson_prerequisite( int $lesson_id ): void;

	/**
	 * Get the IDs of the lessons that have the given lesson as their prerequisite.
	 *
	 * @internal
	 *
	 * @param int $prerequisite_lesson_id Prerequisite lesson ID.
	 * @return int[] Lesson IDs, in no particular order. Trashed lessons are left out.
	 */
	public function get_lesson_ids_with_prerequisite( int $prerequisite_lesson_id ): array;

	/**
	 * Set the order of a lesson in a course.
	 *
	 * @internal
	 *
	 * @param int $lesson_id Lesson ID.
	 * @param int $course_id Course ID.
	 * @param int $order     Order.
	 */
	public function set_lesson_order( int $lesson_id, int $course_id, int $order ): void;

	/**
	 * Delete the order of a lesson in a course.
	 *
	 * @internal
	 *
	 * @param int $lesson_id Lesson ID.
	 * @param int $course_id Course ID.
	 */
	public function delete_lesson_order( int $lesson_id, int $course_id ): void;

	/**
	 * Set the order of the lessons that are not in a module of a course.
	 *
	 * @internal
	 *
	 * @param int   $course_id  Course ID.
	 * @param int[] $lesson_ids Lesson IDs, in order.
	 */
	public function set_root_lesson_order( int $course_id, array $lesson_ids ): void;

	/**
	 * Get the order of the lessons that are not in a module of a course.
	 *
	 * @internal
	 *
	 * @param int $course_id Course ID.
	 * @return int[] Lesson IDs, in order. Empty when none is stored.
	 */
	public function get_root_lesson_order( int $course_id ): array;

	/**
	 * Set the module of a lesson, replacing any module it had.
	 *
	 * @internal
	 *
	 * @param int $lesson_id Lesson ID.
	 * @param int $term_id   Module term ID.
	 */
	public function set_lesson_module( int $lesson_id, int $term_id ): void;

	/**
	 * Remove a lesson from its module.
	 *
	 * Fires the term relationship deletion hooks only; see `unset_lesson_module()`.
	 *
	 * @internal
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	public function delete_lesson_module( int $lesson_id ): void;

	/**
	 * Remove a lesson from its module by setting its modules to none.
	 *
	 * Same result as `delete_lesson_module()`, but the `set_object_terms` action fires as well.
	 *
	 * @internal
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	public function unset_lesson_module( int $lesson_id ): void;

	/**
	 * Get the order of a lesson in a module.
	 *
	 * @internal
	 *
	 * @param int $lesson_id Lesson ID.
	 * @param int $term_id   Module term ID.
	 * @return int|null Order, or null when none is stored for that module.
	 */
	public function get_lesson_module_order( int $lesson_id, int $term_id ): ?int;

	/**
	 * Set the order of a lesson in a module.
	 *
	 * @internal
	 *
	 * @param int $lesson_id Lesson ID.
	 * @param int $term_id   Module term ID.
	 * @param int $order     Order.
	 */
	public function set_lesson_module_order( int $lesson_id, int $term_id, int $order ): void;

	/**
	 * Delete the order of a lesson in a module.
	 *
	 * @internal
	 *
	 * @param int $lesson_id Lesson ID.
	 * @param int $term_id   Module term ID.
	 */
	public function delete_lesson_module_order( int $lesson_id, int $term_id ): void;

	/**
	 * Delete the order of a lesson in each module it is in.
	 *
	 * @internal
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	public function delete_all_lesson_module_orders( int $lesson_id ): void;
}

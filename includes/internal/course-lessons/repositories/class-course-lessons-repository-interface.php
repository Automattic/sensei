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
	 * Get the position of a lesson in a course.
	 *
	 * @internal
	 *
	 * @param int $lesson_id Lesson ID.
	 * @param int $course_id Course ID.
	 * @return int|null Position, or null when none is stored for that course.
	 */
	public function get_lesson_position( int $lesson_id, int $course_id ): ?int;

	/**
	 * Get the module of a lesson.
	 *
	 * @internal
	 *
	 * @param int $lesson_id Lesson ID.
	 * @return \WP_Term|null The module term, or null when the lesson has none or a filter returns no term.
	 */
	public function get_lesson_module( int $lesson_id ): ?\WP_Term;
}

<?php
/**
 * File containing the Metas_Based_Course_Lessons_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Course_Lessons\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Metas_Based_Course_Lessons_Repository.
 *
 * Reads the course structure of a lesson from post metas: the course it is attached to
 * (`_lesson_course`) and its prerequisite (`_lesson_prerequisite`).
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Metas_Based_Course_Lessons_Repository implements Course_Lessons_Repository_Interface {
	/**
	 * Get the course a lesson is attached to.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param int $lesson_id Lesson ID.
	 * @return int|null Course ID, or null when the lesson has no course.
	 */
	public function get_course_id( int $lesson_id ): ?int {
		$course_id = get_post_meta( $lesson_id, '_lesson_course', true );

		return is_numeric( $course_id ) && (int) $course_id > 0 ? (int) $course_id : null;
	}

	/**
	 * Get the prerequisite lesson ID stored on a lesson.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param int $lesson_id Lesson ID.
	 * @return int|null Prerequisite lesson ID, or null when none is stored.
	 */
	public function get_lesson_prerequisite_id( int $lesson_id ): ?int {
		$prerequisite_lesson_id = get_post_meta( $lesson_id, '_lesson_prerequisite', true );

		return is_numeric( $prerequisite_lesson_id ) && (int) $prerequisite_lesson_id > 0 ? (int) $prerequisite_lesson_id : null;
	}
}

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
 * Reads the course structure of a lesson: the course it is attached to and its prerequisite.
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
}

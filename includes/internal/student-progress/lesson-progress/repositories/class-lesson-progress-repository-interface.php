<?php
/**
 * File containing the Lesson_Progress_Repository_Interface class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Lesson_Progress\Repositories;

use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Lesson_Progress_Repository_Interface.
 *
 * @internal
 *
 * @since 4.7.2
 */
interface Lesson_Progress_Repository_Interface {
	/**
	 * Creates a new lesson progress.
	 *
	 * @internal
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 * @return Lesson_Progress The lesson progress.
	 */
	public function create( int $lesson_id, int $user_id ): Lesson_Progress;

	/**
	 * Finds a lesson progress by lesson and user.
	 *
	 * @internal
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 * @return Lesson_Progress|null The lesson progress or null if not found.
	 */
	public function get( int $lesson_id, int $user_id ): ?Lesson_Progress;

	/**
	 * Check if a lesson progress exists.
	 *
	 * @internal
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 * @return bool
	 */
	public function has( int $lesson_id, int $user_id ): bool;

	/**
	 * Save the lesson progress.
	 *
	 * @internal
	 *
	 * @param Lesson_Progress $lesson_progress The lesson progress.
	 */
	public function save( Lesson_Progress $lesson_progress ): void;

	/**
	 * Delete the lesson progress.
	 *
	 * @internal
	 *
	 * @param Lesson_Progress $lesson_progress The lesson progress.
	 */
	public function delete( Lesson_Progress $lesson_progress ): void;

	/**
	 * Returns the number of started lessons for a user in a course.
	 * The number of started lessons is the same as the number of lessons that have a progress record.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return int
	 */
	public function count( int $course_id, int $user_id ): int;
}

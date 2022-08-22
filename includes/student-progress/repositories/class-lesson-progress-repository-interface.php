<?php
/**
 * File containing the Sensei_Lesson_Progress_Repository_Interface class.
 *
 * @package sensei
 */

namespace Sensei\StudentProgress\Repositories;

use Sensei\StudentProgress\Models\Lesson_Progress_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Sensei_Lesson_Progress_Repository_Interface.
 *
 * @since $$next-version$$
 */
interface Lesson_Progress_Repository_Interface {
	/**
	 * Creates a new lesson progress.
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 * @return Lesson_Progress_Interface The lesson progress.
	 */
	public function create( int $lesson_id, int $user_id ): Lesson_Progress_Interface;

	/**
	 * Finds a lesson progress by lesson and user.
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 * @return Lesson_Progress_Interface|null The lesson progress or null if not found.
	 */
	public function get( int $lesson_id, int $user_id ): ?Lesson_Progress_Interface;

	/**
	 * Check if a lesson progress exists.
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 * @return bool
	 */
	public function has( int $lesson_id, int $user_id ): bool;

	/**
	 * Save the lesson progress.
	 * @param Lesson_Progress_Interface $lesson_progress
	 */
	public function save( Lesson_Progress_Interface $lesson_progress ): void;

	/**
	 * Returns the number of started lessons for a user in a course.
	 * The number of started lessons is the same as the number of lessons that have a progress record.
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	public function count( int $course_id, int $user_id ): int;
}

<?php
/**
 * File containing the Sensei_Lesson_Progress_Repository_Interface class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Sensei_Lesson_Progress_Repository_Interface.
 *
 * @since $$next-version$$
 */
interface Sensei_Lesson_Progress_Repository_Interface {
	/**
	 * Creates a new lesson progress.
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 * @return Sensei_Lesson_Progress The lesson progress.
	 */
	public function create( int $lesson_id, int $user_id ): Sensei_Lesson_Progress;

	/**
	 * Finds a lesson progress by lesson and user.
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 * @return Sensei_Lesson_Progress|null The lesson progress or null if not found.
	 */
	public function get( int $lesson_id, int $user_id ): ?Sensei_Lesson_Progress;

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
	 *
	 * @param Sensei_Lesson_Progress $lesson_progress
	 */
	public function save( Sensei_Lesson_Progress $lesson_progress ): void;
}

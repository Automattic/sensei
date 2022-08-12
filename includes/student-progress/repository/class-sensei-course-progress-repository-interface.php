<?php
/**
 * File containing the Sensei_Course_Progress_Repository_Interface.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Sensei_Course_Progress_Repository_Interface.
 *
 * @since $$next-version$$
 */
interface Sensei_Course_Progress_Repository_Interface {
	/**
	 * Creates a new course progress.
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return Sensei_Course_Progress_Interface The course progress.
	 */
	public function create( int $course_id, int $user_id ): Sensei_Course_Progress_Interface;

	/**
	 * Gets a course progress.
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return Sensei_Course_Progress_Interface|null The course progress.
	 */
	public function get( int $course_id, int $user_id ): ?Sensei_Course_Progress_Interface;

	/**
	 * Checks if a course progress exists.
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return bool Whether the course progress exists.
	 */
	public function has( int $course_id, int $user_id ): bool;

	/**
	 * Save course progress.
	 *
	 * @param Sensei_Course_Progress_Interface $course_progress The course progress.
	 */
	public function save( Sensei_Course_Progress_Interface $course_progress ): void;
}

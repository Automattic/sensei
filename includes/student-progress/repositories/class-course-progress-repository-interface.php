<?php
/**
 * File containing the Sensei_Course_Progress_Repository_Interface.
 *
 * @package sensei
 */

namespace Sensei\Student_Progress\Repositories;

use Sensei\Student_Progress\Models\Course_Progress_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Sensei_Course_Progress_Repository_Interface.
 *
 * @since $$next-version$$
 */
interface Course_Progress_Repository_Interface {
	/**
	 * Creates a new course progress.
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return Course_Progress_Interface The course progress.
	 */
	public function create( int $course_id, int $user_id ): Course_Progress_Interface;

	/**
	 * Gets a course progress.
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return Course_Progress_Interface|null The course progress.
	 */
	public function get( int $course_id, int $user_id ): ?Course_Progress_Interface;

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
	 * @param Course_Progress_Interface $course_progress The course progress.
	 */
	public function save( Course_Progress_Interface $course_progress ): void;
}

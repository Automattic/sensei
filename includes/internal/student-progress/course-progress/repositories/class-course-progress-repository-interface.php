<?php
/**
 * File containing the Course_Progress_Repository_Interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Course_Progress\Repositories;

use Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Course_Progress_Repository_Interface.
 *
 * @internal
 *
 * @since 4.7.2
 */
interface Course_Progress_Repository_Interface {
	/**
	 * Creates a new course progress.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return Course_Progress_Interface The course progress.
	 */
	public function create( int $course_id, int $user_id ): Course_Progress_Interface;

	/**
	 * Gets a course progress.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return Course_Progress_Interface|null The course progress.
	 */
	public function get( int $course_id, int $user_id ): ?Course_Progress_Interface;

	/**
	 * Checks if a course progress exists.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return bool Whether the course progress exists.
	 */
	public function has( int $course_id, int $user_id ): bool;

	/**
	 * Save course progress.
	 *
	 * @internal
	 *
	 * @param Course_Progress_Interface $course_progress The course progress.
	 */
	public function save( Course_Progress_Interface $course_progress ): void;

	/**
	 * Delete course progress.
	 *
	 * @internal
	 *
	 * @param Course_Progress_Interface $course_progress The course progress.
	 */
	public function delete( Course_Progress_Interface $course_progress ): void;

	/**
	 * Delete course progress for a given course.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 */
	public function delete_for_course( int $course_id ): void;

	/**
	 * Delete course progress for a given user.
	 *
	 * @internal
	 *
	 * @param int $user_id The user ID.
	 */
	public function delete_for_user( int $user_id ): void;

	/**
	 * Find course progress.
	 *
	 * @internal
	 *
	 * @param array $args The arguments.
	 * @return Course_Progress_Interface[] The course progress.
	 */
	public function find( array $args ): array;
}

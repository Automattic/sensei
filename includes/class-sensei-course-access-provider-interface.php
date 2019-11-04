<?php
/**
 * File containing the interface Sensei_Course_Access_Provider_Interface.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for course access providers.
 */
interface Sensei_Course_Access_Provider_Interface {
	/**
	 * Gets the unique identifier of this access provider.
	 *
	 * @return int
	 */
	public static function get_id();

	/**
	 * Check if this course access provider is granting access for a user to a course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return bool|null  `true` if access is granted;
	 *                    `false` if access should be restricted unless provided elsewhere;
	 *                    `null` if provider doesn't manage this course's access.
	 */
	public function has_access( $user_id, $course_id );

	/**
	 * Gets the version of the access provider logic. If this changes, access will be recalculated.
	 *
	 * @return int
	 */
	public static function get_version();
}

<?php
/**
 * File containing the interface Sensei_Course_Enrolment_Provider_Debug_Interface.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for course enrolment providers who wish to provide debugging information.
 */
interface Sensei_Course_Enrolment_Provider_Debug_Interface {
	/**
	 * Provide debugging information about a user's enrolment in a course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return string[] Array of human readable debug messages. Allowed HTML tags: a[href]; strong; em; span[style,class]
	 */
	public function debug( $user_id, $course_id );
}

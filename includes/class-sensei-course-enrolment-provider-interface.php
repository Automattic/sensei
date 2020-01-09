<?php
/**
 * File containing the interface Sensei_Course_Enrolment_Provider_Interface.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for course enrolment providers.
 */
interface Sensei_Course_Enrolment_Provider_Interface {
	/**
	 * Gets the unique identifier of this enrolment provider.
	 *
	 * @return int
	 */
	public static function get_id();

	/**
	 * Check if this course enrolment provider manages enrolment for a particular course.
	 *
	 * @param int $course_id Course post ID.
	 *
	 * @return bool
	 */
	public function handles_enrolment( $course_id );

	/**
	 * Check if this course enrolment provider is enrolment a user to a course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return bool  `true` if this provider enrols the student and `false` if not.
	 */
	public function is_enroled( $user_id, $course_id );

	/**
	 * Gets the version of the enrolment provider logic. If this changes, enrolment will be recalculated.
	 *
	 * @return int
	 */
	public static function get_version();
}

<?php
/**
 * File with trait Sensei_Course_Enrolment_Manual_Test_Helpers.
 *
 * @package sensei-tests
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Using PHPUnit conventions.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helpers for course enrolment related tests.
 *
 * @since 3.0.0
 */
trait Sensei_Course_Enrolment_Manual_Test_Helpers {
	/**
	 * Manually enrol student in course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return bool
	 */
	private function manuallyEnrolStudentInCourse( $user_id, $course_id ) {
		/**
		 * Manual provider.
		 *
		 * @var \Sensei_Course_Manual_Enrolment_Provider $manual_provider
		 */
		$manual_provider = \Sensei_Course_Enrolment_Manager::get_enrolment_provider_by_id( \Sensei_Course_Manual_Enrolment_Provider::get_id() );
		if ( ! $manual_provider ) {
			return false;
		}

		return $manual_provider->enrol_student( $user_id, $course_id );
	}
}

<?php
/**
 * File containing the class Sensei_Test_Enrolment_Provider_Denies_Crooks.
 *
 * @package sensei-tests
 */

/**
 * Class Sensei_Test_Enrolment_Provider_Denies_Crooks.
 *
 * Used in testing. Denies enrolment for crooks.
 */
class Sensei_Test_Enrolment_Provider_Denies_Crooks implements Sensei_Course_Enrolment_Provider_Interface {
	public static function get_id() {
		return 'denies-crooks';
	}

	public function handles_enrolment( $course_id ) {
		return true;
	}

	public function is_enroled( $user_id, $course_id ) {
		$user = get_user_by( 'ID', $user_id );
		if ( $user && 1 === preg_match( '/crook/', $user->description ) ) {
			return false;
		}

		return true;
	}

	public static function get_version() {
		return 1;
	}

}

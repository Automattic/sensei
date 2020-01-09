<?php
/**
 * File containing the class Sensei_Test_Access_Provider_Always_Provides.
 *
 * @package sensei-tests
 */

/**
 * Class Sensei_Test_Enrolment_Provider_Always_Provides.
 *
 * Used in testing. Always provides enrolment.
 */
class Sensei_Test_Enrolment_Provider_Always_Provides implements Sensei_Course_Enrolment_Provider_Interface {
	public static function get_id() {
		return 'always-provides';
	}

	public function handles_enrolment( $course_id ) {
		return true;
	}

	public function is_enroled( $user_id, $course_id ) {
		return true;
	}

	public static function get_version() {
		return 1;
	}

}

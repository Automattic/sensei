<?php
/**
 * File containing the class Sensei_Test_Enrolment_Provider_Never_Handles.
 *
 * @package sensei-tests
 */

/**
 * Class Sensei_Test_Enrolment_Provider_Never_Handles.
 *
 * Used in testing. Never handles a particular course's enrolment.
 */
class Sensei_Test_Enrolment_Provider_Never_Handles implements Sensei_Course_Enrolment_Provider_Interface {
	public static function get_id() {
		return 'never-handles';
	}

	public function handles_enrolment( $course_id ) {
		return false;
	}

	public function is_enrolled( $user_id, $course_id ) {
		return true;
	}

	public static function get_version() {
		return 1;
	}

}

<?php
/**
 * File containing the class Sensei_Test_Enrolment_Provider_Never_Provides.
 *
 * @package sensei-tests
 */

/**
 * Class Sensei_Test_Enrolment_Provider_Never_Provides.
 *
 * Used in testing. Never provides enrolment.
 */
class Sensei_Test_Enrolment_Provider_Never_Provides implements Sensei_Course_Enrolment_Provider_Interface {
	const ID = 'never-provides';

	public function get_id() {
		return self::ID;
	}

	public function get_name() {
		return 'Never Provides';
	}

	public function handles_enrolment( $course_id ) {
		return true;
	}

	public function is_enrolled( $user_id, $course_id ) {
		return false;
	}

	public function get_version() {
		return 1;
	}

}

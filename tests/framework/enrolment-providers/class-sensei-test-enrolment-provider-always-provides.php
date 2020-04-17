<?php
/**
 * File containing the class Sensei_Test_Enrolment_Provider_Always_Provides.
 *
 * @package sensei-tests
 */

/**
 * Class Sensei_Test_Enrolment_Provider_Always_Provides.
 *
 * Used in testing. Always provides enrolment.
 */
class Sensei_Test_Enrolment_Provider_Always_Provides implements Sensei_Course_Enrolment_Provider_Interface {
	const ID = 'always-provides';

	public function get_id() {
		return self::ID;
	}

	public function get_name() {
		return 'Always Provides';
	}

	public function handles_enrolment( $course_id ) {
		return true;
	}

	public function is_enrolled( $user_id, $course_id ) {
		return true;
	}

	public function get_version() {
		return 1;
	}

}

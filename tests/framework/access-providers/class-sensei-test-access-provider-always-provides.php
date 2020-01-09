<?php
/**
 * File containing the class Sensei_Test_Access_Provider_Always_Provides.
 *
 * @package sensei-tests
 */

/**
 * Class Sensei_Test_Access_Provider_Always_Provides.
 *
 * Used in testing. Always provides access.
 */
class Sensei_Test_Access_Provider_Always_Provides implements Sensei_Course_Access_Provider_Interface {
	public static function get_id() {
		return 'always-provides';
	}

	public function handles_access( $course_id ) {
		return true;
	}

	public function has_access( $user_id, $course_id ) {
		return true;
	}

	public static function get_version() {
		return 1;
	}

}

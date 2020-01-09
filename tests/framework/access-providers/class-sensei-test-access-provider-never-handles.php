<?php
/**
 * File containing the class Sensei_Test_Access_Provider_Never_Handles.
 *
 * @package sensei-tests
 */

/**
 * Class Sensei_Test_Access_Provider_Never_Handles.
 *
 * Used in testing. Never handles a particular course's access.
 */
class Sensei_Test_Access_Provider_Never_Handles implements Sensei_Course_Access_Provider_Interface {
	public static function get_id() {
		return 'never-handles';
	}

	public function handles_access( $course_id ) {
		return false;
	}

	public function has_access( $user_id, $course_id ) {
		return false;
	}

	public static function get_version() {
		return 1;
	}

}

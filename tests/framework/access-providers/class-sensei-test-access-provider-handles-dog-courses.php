<?php
/**
 * File containing the class Sensei_Test_Access_Provider_Handles_Dog_Courses.
 *
 * @package sensei-tests
 */

/**
 * Class Sensei_Test_Access_Provider_Handles_Dog_Courses.
 *
 * Used in testing. Handles access for courses with "dog" in their title.
 */
class Sensei_Test_Access_Provider_Handles_Dog_Courses implements Sensei_Course_Access_Provider_Interface {
	public static function get_id() {
		return 'handles-dog-courses';
	}

	public function handles_access( $course_id ) {
		if ( 1 === preg_match( '/dog/', get_the_title( $course_id ) ) ) {
			return true;
		}

		return false;
	}

	public function has_access( $user_id, $course_id ) {
		return true;
	}

	public static function get_version() {
		return 1;
	}

}

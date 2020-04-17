<?php
/**
 * File containing the class Sensei_Test_Enrolment_Provider_Handles_Dog_Courses.
 *
 * @package sensei-tests
 */

/**
 * Class Sensei_Test_Enrolment_Provider_Handles_Dog_Courses.
 *
 * Used in testing. Handles enrolment for courses with "dog" in their title.
 */
class Sensei_Test_Enrolment_Provider_Handles_Dog_Courses implements Sensei_Course_Enrolment_Provider_Interface {
	const ID = 'handles-dog-courses';

	public function get_id() {
		return self::ID;
	}

	public function get_name() {
		return 'Handles Dog Courses';
	}

	public function handles_enrolment( $course_id ) {
		if ( 1 === preg_match( '/dog/i', get_the_title( $course_id ) ) ) {
			return true;
		}

		return false;
	}

	public function is_enrolled( $user_id, $course_id ) {
		return true;
	}

	public function get_version() {
		return 1;
	}

}

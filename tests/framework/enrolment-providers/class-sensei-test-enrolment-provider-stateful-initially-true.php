<?php
/**
 * File containing the class Sensei_Test_Enrolment_Provider_Stateful_Initially_True.
 *
 * @package sensei-tests
 */

/**
 * Class Sensei_Test_Enrolment_Provider_Stateful_Initially_True.
 *
 * Used in testing. Uses the stateful provider handling and is initially true.
 */
class Sensei_Test_Enrolment_Provider_Stateful_Initially_True
	extends Sensei_Course_Enrolment_Stored_Status_Provider
	implements Sensei_Course_Enrolment_Provider_Interface {

	const ID = 'stateful-initially-true';

	public function get_id() {
		return self::ID;
	}

	public function get_name() {
		return 'Stateful Initially True';
	}

	public function handles_enrolment( $course_id ) {
		return true;
	}

	public function get_initial_enrolment_status( $user_id, $course_id ) {
		return true;
	}

	public function get_version() {
		return 1;
	}

}

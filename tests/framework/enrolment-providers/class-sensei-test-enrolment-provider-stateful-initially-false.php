<?php
/**
 * File containing the class Sensei_Test_Enrolment_Provider_Stateful_Initially_False.
 *
 * @package sensei-tests
 */

/**
 * Class Sensei_Test_Enrolment_Provider_Stateful_Initially_False.
 *
 * Used in testing. Uses the stateful provider handling and is initially false.
 */
class Sensei_Test_Enrolment_Provider_Stateful_Initially_False
	extends Sensei_Course_Enrolment_Stored_Status_Provider
	implements Sensei_Course_Enrolment_Provider_Interface {

	const ID = 'stateful-initially-false';

	public function get_id() {
		return self::ID;
	}

	public function get_name() {
		return 'Stateful Initially False';
	}

	public function handles_enrolment( $course_id ) {
		return true;
	}

	public function get_initial_enrolment_status( $user_id, $course_id ) {
		return false;
	}

	public function get_version() {
		return 1;
	}

	public function proxy_has_enrolment_status( $user_id, $course_id ) {
		return $this->has_enrolment_status( $user_id, $course_id );
	}

	public function proxy_clear_enrolment_status( $user_id, $course_id ) {
		return $this->clear_enrolment_status( $user_id, $course_id );
	}

	public function proxy_set_enrolment_status( $user_id, $course_id, $is_enrolled ) {
		return $this->set_enrolment_status( $user_id, $course_id, $is_enrolled );
	}

	public function proxy_get_enrolment_status( $user_id, $course_id ) {
		return $this->get_enrolment_status( $user_id, $course_id );
	}
}

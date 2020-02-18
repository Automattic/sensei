<?php
/**
 * File containing the abstract class Sensei_Course_Enrolment_Stored_Status_Provider.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract class for course enrolment providers that have a stored state.
 */
abstract class Sensei_Course_Enrolment_Stored_Status_Provider implements Sensei_Course_Enrolment_Provider_Interface {
	const DATA_KEY_ENROLMENT_STATUS = 'enrolment_status';

	/**
	 * Get the initial enrolment status of a user's enrolment in a course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return bool
	 */
	abstract protected function get_initial_enrolment_status( $user_id, $course_id );

	/**
	 * Check if this course enrolment provider is enrolling a user to a course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return bool  `true` if this provider enrols the student and `false` if not.
	 */
	public function is_enrolled( $user_id, $course_id ) {
		return $this->get_enrolment_status( $user_id, $course_id );
	}

	/**
	 * Set the enrolment state for the current provider.
	 *
	 * @param int  $user_id     User ID.
	 * @param int  $course_id   Course post ID.
	 * @param bool $is_enrolled Enrolment state to set for the user and course.
	 */
	final protected function set_enrolment_status( $user_id, $course_id, $is_enrolled ) {
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$provider_state   = $course_enrolment->get_provider_state( $this, $user_id );

		$provider_state->set_stored_value( self::DATA_KEY_ENROLMENT_STATUS, $is_enrolled );
		$provider_state->save();
	}

	/**
	 * Clears the enrolment status for the current provider.
	 *
	 * @param int $user_id     User ID.
	 * @param int $course_id   Course post ID.
	 */
	final protected function clear_enrolment_status( $user_id, $course_id ) {
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$provider_state   = $course_enrolment->get_provider_state( $this, $user_id );

		$provider_state->set_stored_value( self::DATA_KEY_ENROLMENT_STATUS, null );
		$provider_state->save();
	}

	/**
	 * Get the enrolment status for the current provider.
	 *
	 * @param int $user_id     User ID.
	 * @param int $course_id   Course post ID.
	 *
	 * @return bool
	 */
	final protected function get_enrolment_status( $user_id, $course_id ) {
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$provider_state   = $course_enrolment->get_provider_state( $this, $user_id );

		$is_enrolled = $provider_state->get_stored_value( self::DATA_KEY_ENROLMENT_STATUS );

		// Check if the initial enrolment state hasn't been set yet.
		if ( null === $is_enrolled ) {
			$is_enrolled = $this->get_initial_enrolment_status( $user_id, $course_id );
			$this->set_enrolment_status( $user_id, $course_id, $is_enrolled );
		}

		return $is_enrolled;
	}

	/**
	 * Check the existence of enrolment status for the current provider.
	 *
	 * @param int $user_id     User ID.
	 * @param int $course_id   Course post ID.
	 *
	 * @return bool
	 */
	final protected function has_enrolment_status( $user_id, $course_id ) {
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$provider_state   = $course_enrolment->get_provider_state( $this, $user_id );

		$is_enrolled = $provider_state->get_stored_value( self::DATA_KEY_ENROLMENT_STATUS );

		return null !== $is_enrolled;
	}
}

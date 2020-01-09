<?php
/**
 * File containing the class Sensei_Test_Enrolment_Provider_Version_Morph.
 *
 * @package sensei-tests
 */

/**
 * Class Sensei_Test_Enrolment_Provider_Version_Morph.
 *
 * Used in testing. Version can be increased. Provides enrolment when version is even.
 */
class Sensei_Test_Enrolment_Provider_Version_Morph implements Sensei_Course_Enrolment_Provider_Interface {
	public static $version = 1;

	public static function get_id() {
		return 'version-morph';
	}

	public function handles_enrolment( $course_id ) {
		return true;
	}

	public function is_enroled( $user_id, $course_id ) {
		return 0 === self::$version % 2;
	}

	public static function get_version() {
		return self::$version;
	}

}

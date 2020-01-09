<?php
/**
 * File containing the class Sensei_Test_Access_Provider_Version_Morph.
 *
 * @package sensei-tests
 */

/**
 * Class Sensei_Test_Access_Provider_Version_Morph.
 *
 * Used in testing. Version can be increased. Provides access when version is even.
 */
class Sensei_Test_Access_Provider_Version_Morph implements Sensei_Course_Access_Provider_Interface {
	public static $version = 1;

	public static function get_id() {
		return 'version-morph';
	}

	public function handles_access( $course_id ) {
		return true;
	}

	public function has_access( $user_id, $course_id ) {
		return 0 === self::$version % 2;
	}

	public static function get_version() {
		return self::$version;
	}

}

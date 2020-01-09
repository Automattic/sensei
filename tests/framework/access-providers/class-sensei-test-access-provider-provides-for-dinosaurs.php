<?php
/**
 * File containing the class Sensei_Test_Access_Provider_Provides_For_Dinosaurs.
 *
 * @package sensei-tests
 */

/**
 * Class Sensei_Test_Access_Provider_Provides_For_Dinosaurs.
 *
 * Used in testing. Provides access for dinosaurs only.
 */
class Sensei_Test_Access_Provider_Provides_For_Dinosaurs implements Sensei_Course_Access_Provider_Interface {
	public static function get_id() {
		return 'provides-for-dinosaurs';
	}

	public function handles_access( $course_id ) {
		return true;
	}

	public function has_access( $user_id, $course_id ) {
		$user = get_user_by( 'ID', $user_id );
		if ( $user && 1 === preg_match( '/dinosaur/', $user->display_name ) ) {
			return true;
		}

		return false;
	}

	public static function get_version() {
		return 1;
	}

}

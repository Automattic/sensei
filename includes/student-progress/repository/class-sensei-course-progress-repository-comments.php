<?php

class Sensei_Course_Progress_Repository_Comments implements Sensei_Course_Progress_Repository_Interface {
	public function create( int $course_id, int $user_id ): Sensei_Course_Progress_Interface {
		// doesn't exist yet
		return new Sensei_Course_Progress( $course_id, $user_id );
	}
	public function get( int $course_id, int $user_id ): ?Sensei_Course_Progress_Interface {
		// find and return
		return null;
	}
	public function has( int $course_id, int $user_id ): bool {
		// check
		return false;
	}
	public function save( Sensei_Course_Progress_Interface $course_progress ): void {
		// save
	}
}

<?php

interface Sensei_Course_Progress_Repository_Interface {
	public function create( int $course_id, int $user_id ): Sensei_Course_Progress_Interface;
	public function get( int $course_id, int $user_id ): ?Sensei_Course_Progress_Interface;
	public function has( int $course_id, int $user_id ): bool;
	public function save( Sensei_Course_Progress_Interface $course_progress ): void;
}

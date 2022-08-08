<?php

interface Sensei_Lesson_Progress_Repository_Interface {
	public function create( int $lesson_id, int $user_id ): Sensei_Lesson_Progress_Interface;
	public function get( int $lesson_id, int $user_id ): Sensei_Lesson_Progress_Interface;
	public function has( int $lesson_id, int $user_id ): bool;
	public function save( Sensei_Lesson_Progress_Interface $lesson_progress ): void;
}

<?php

interface Sensei_Quiz_Progress_Repository_Interface {
	public function create( int $quiz_id, int $user_id ): Sensei_Quiz_Progress_Interface;
	public function get( int $quiz_id, int $user_id ): Sensei_Quiz_Progress_Interface;
	public function has( int $quiz_id, int $user_id ): bool;
	public function save( Sensei_Quiz_Progress_Interface $quiz_progress ): void;
}

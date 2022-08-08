<?php

class Sensei_Course_Progress_Repository_Aggregate implements Sensei_Course_Progress_Repository_Interface {

	private Sensei_Course_Progress_Repository_Interface $repository_tables;
	private Sensei_Course_Progress_Repository_Interface $repository_comments;
	private bool $use_tables;
	public function __construct(
		Sensei_Course_Progress_Repository_Tables $tables,
		Sensei_Course_Progress_Repository_Comments $comments,
		bool $use_tables = true
	) {
		$this->repository_tables = $tables;
		$this->repository_comments = $comments;
	}

	public function create( int $course_id, int $user_id ): Sensei_Course_Progress_Interface {
		// doesn't exist yet
		return new Sensei_Course_Progress( $course_id, $user_id );
	}
	public function get( int $course_id, int $user_id ): ?Sensei_Course_Progress_Interface {
		if ($this->use_tables) {
			return $this->repository_tables->get($course_id, $user_id);
		} else {
			return $this->repository_comments->get($course_id, $user_id);
		}
	}
	public function has( int $course_id, int $user_id ): bool {
		if ($this->use_tables) {
			return $this->repository_tables->has($course_id, $user_id);
		} else {
			return $this->repository_comments->has($course_id, $user_id);
		}
	}
	public function save( Sensei_Course_Progress_Interface $course_progress ): void {
		$this->repository_comments->save($course_progress);
		if ($this->use_tables) {
			$this->repository_tables->save($course_progress);
		}
	}
}

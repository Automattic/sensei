<?php

class Sensei_Course_Progress_Repository_Aggregate implements Sensei_Course_Progress_Repository_Interface {

	/**
	 * Repository for course progress in custom tables.
	 * @var Sensei_Course_Progress_Tables_Repository
	 */
	private $repository_tables;

	/**
	 * Repository for course progress in comments.
	 *
	 * @var Sensei_Course_Progress_Comments_Repository
	 */
	private $repository_comments;

	/**
	 * Whether to use the custom tables or the comments.
	 *
	 * @var bool
	 */
	private $use_tables;

	/**
	 * Sensei_Course_Progress_Repository_Aggregate constructor.
	 * @param Sensei_Course_Progress_Tables_Repository   $tables Repository for course progress in custom tables.
	 * @param Sensei_Course_Progress_Comments_Repository $comments Repository for course progress in comments.
	 * @param bool                                       $use_tables Whether to use the custom tables repository.
	 */
	public function __construct(
		Sensei_Course_Progress_Tables_Repository $tables,
		Sensei_Course_Progress_Comments_Repository $comments,
		bool $use_tables = true
	) {
		$this->repository_tables   = $tables;
		$this->repository_comments = $comments;
		$this->use_tables          = $use_tables;
	}

	/**
	 * Creates a new course progress.
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return Sensei_Course_Progress The course progress.
	 */
	public function create( int $course_id, int $user_id ): Sensei_Course_Progress {
		if ( $this->use_tables ) {
			$this->repository_tables->create( $course_id, $user_id );
		}

		return $this->repository_comments->create( $course_id, $user_id );
	}

	/**
	 * Gets a course progress.
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return Sensei_Course_Progress The course progress.
	 */
	public function get( int $course_id, int $user_id ): ?Sensei_Course_Progress {
		if ( $this->use_tables ) {
			return $this->repository_tables->get( $course_id, $user_id );
		}
		return $this->repository_comments->get( $course_id, $user_id );
	}

	/**
	 * Checks if a course progress exists.
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return bool True if the course progress exists.
	 */
	public function has( int $course_id, int $user_id ): bool {
		if ( $this->use_tables ) {
			return $this->repository_tables->has( $course_id, $user_id );
		}
		return $this->repository_comments->has( $course_id, $user_id );
	}

	/**
	 * Saves a course progress.
	 *
	 * @param Sensei_Course_Progress $course_progress The course progress.
	 */
	public function save( Sensei_Course_Progress $course_progress ): void {
		$this->repository_comments->save( $course_progress );
		if ( $this->use_tables ) {
			$this->repository_tables->save( $course_progress );
		}
	}
}

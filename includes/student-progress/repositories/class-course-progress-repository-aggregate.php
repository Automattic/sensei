<?php
/**
 *  File containing the Course_Progress_Repository_Aggregate class.
 *
 * @package sensei
 */

namespace Sensei\StudentProgress\Repositories;

use Sensei\StudentProgress\Models\Course_Progress_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Course_Progress_Repository_Aggregate.
 *
 * @since $$next-version$$
 */
class Course_Progress_Repository_Aggregate implements Course_Progress_Repository_Interface {

	/**
	 * Repository for course progress in custom tables.
	 * @var Course_Progress_Tables_Repository
	 */
	private $repository_tables;

	/**
	 * Repository for course progress in comments.
	 * @var Course_Progress_Comments_Repository
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
	 * @param Course_Progress_Tables_Repository   $tables Repository for course progress in custom tables.
	 * @param Course_Progress_Comments_Repository $comments Repository for course progress in comments.
	 * @param bool                                $use_tables Whether to use the custom tables repository.
	 */
	public function __construct(
		Course_Progress_Tables_Repository $tables,
		Course_Progress_Comments_Repository $comments,
		bool $use_tables = true
	) {
		$this->repository_tables   = $tables;
		$this->repository_comments = $comments;
		$this->use_tables          = $use_tables;
	}

	/**
	 * Creates a new course progress.
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return Course_Progress_Interface The course progress.
	 */
	public function create( int $course_id, int $user_id ): Course_Progress_Interface {
		if ( $this->use_tables ) {
			$this->repository_tables->create( $course_id, $user_id );
		}

		return $this->repository_comments->create( $course_id, $user_id );
	}

	/**
	 * Gets a course progress.
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return Course_Progress_Interface The course progress.
	 */
	public function get( int $course_id, int $user_id ): ?Course_Progress_Interface {
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
	 * @param Course_Progress_Interface $course_progress The course progress.
	 */
	public function save( Course_Progress_Interface $course_progress ): void {
		$this->repository_comments->save( $course_progress );
		if ( $this->use_tables ) {
			$this->repository_tables->save( $course_progress );
		}
	}
}

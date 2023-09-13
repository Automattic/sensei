<?php
/**
 * File containing the Table_Reading_Aggregate_Lesson_Progress_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Lesson_Progress\Repositories;

use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Comments_Based_Lesson_Progress;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress_Interface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Tables_Based_Lesson_Progress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Table_Reading_Aggregate_Lesson_Progress_Repository.
 *
 * @since $$next-version$$
 */
class Table_Reading_Aggregate_Lesson_Progress_Repository implements Lesson_Progress_Repository_Interface {
	/**
	 * Comments based lesson progress repository implementation.
	 *
	 * @var Comments_Based_Lesson_Progress_Repository
	 */
	private $comments_based_repository;

	/**
	 * Tables based lesson progress repository implementation.
	 *
	 * @var Tables_Based_Lesson_Progress_Repository
	 */
	private $tables_based_repository;

	/**
	 * Table_Reading_Aggregate_Lesson_Progress_Repository constructor.
	 *
	 * @param Comments_Based_Lesson_Progress_Repository $comments_based_repository Comments based lesson progress repository implementation.
	 * @param Tables_Based_Lesson_Progress_Repository   $tables_based_repository  Tables based lesson progress repository implementation.
	 */
	public function __construct( Comments_Based_Lesson_Progress_Repository $comments_based_repository, Tables_Based_Lesson_Progress_Repository $tables_based_repository ) {
		$this->comments_based_repository = $comments_based_repository;
		$this->tables_based_repository   = $tables_based_repository;
	}

	/**
	 * Creates a new lesson progress.
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id   The user ID.
	 * @return Tables_Based_Lesson_Progress The lesson progress.
	 */
	public function create( int $lesson_id, int $user_id ): Tables_Based_Lesson_Progress {
		$this->comments_based_repository->create( $lesson_id, $user_id );
		return $this->tables_based_repository->create( $lesson_id, $user_id );
	}

	/**
	 * Get a lesson progress.
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id   The user ID.
	 * @return Lesson_Progress|null The lesson progress.
	 */
	public function get( int $lesson_id, int $user_id ): ?Tables_Based_Lesson_Progress {
		return $this->tables_based_repository->get( $lesson_id, $user_id );
	}

	/**
	 * Checks if a lesson progress exists.
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id   The user ID.
	 * @return bool Whether the lesson progress exists.
	 */
	public function has( int $lesson_id, int $user_id ): bool {
		return $this->tables_based_repository->has( $lesson_id, $user_id );
	}

	/**
	 * Save a lesson progress.
	 *
	 * @param Lesson_Progress_Interface $lesson_progress The lesson progress.
	 */
	public function save( Lesson_Progress_Interface $lesson_progress ): void {
		$this->tables_based_repository->save( $lesson_progress );
		$comments_based_progress = $this->comments_based_repository->get(
			$lesson_progress->get_lesson_id(),
			$lesson_progress->get_user_id()
		);
		if ( ! $comments_based_progress ) {
			$comments_based_progress = $this->comments_based_repository->create(
				$lesson_progress->get_lesson_id(),
				$lesson_progress->get_user_id()
			);
		}
		$updated_comments_based_progress = new Comments_Based_Lesson_Progress(
			$comments_based_progress->get_id(),
			$lesson_progress->get_lesson_id(),
			$lesson_progress->get_user_id(),
			$lesson_progress->get_status(),
			$lesson_progress->get_started_at(),
			$lesson_progress->get_completed_at(),
			$lesson_progress->get_created_at(),
			$lesson_progress->get_updated_at()
		);
		$this->comments_based_repository->save( $updated_comments_based_progress );
	}

	/**
	 * Deletes a lesson progress.
	 *
	 * @param Lesson_Progress_Interface $lesson_progress The lesson progress.
	 */
	public function delete( Lesson_Progress_Interface $lesson_progress ): void {
		$this->tables_based_repository->delete( $lesson_progress );
		$comments_based_progress = $this->comments_based_repository->get(
			$lesson_progress->get_lesson_id(),
			$lesson_progress->get_user_id()
		);
		if ( $comments_based_progress ) {
			$this->comments_based_repository->delete( $comments_based_progress );
		}
	}

	/**
	 * Deletes all lesson progress for a given lesson.
	 *
	 * @param int $lesson_id The lesson ID.
	 */
	public function delete_for_lesson( int $lesson_id ): void {
		$this->comments_based_repository->delete_for_lesson( $lesson_id );
		$this->tables_based_repository->delete_for_lesson( $lesson_id );
	}

	/**
	 * Deletes all lesson progress for a given user.
	 *
	 * @param int $user_id The user ID.
	 */
	public function delete_for_user( int $user_id ): void {
		$this->comments_based_repository->delete_for_user( $user_id );
		$this->tables_based_repository->delete_for_user( $user_id );
	}

	/**
	 * Count the number of lesson progress records in a given course for a given user.
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id   The user ID.
	 * @return int
	 */
	public function count( int $course_id, int $user_id ): int {
		return $this->tables_based_repository->count( $course_id, $user_id );
	}
}

<?php
/**
 * File containing the class Aggregate_Lesson_Progress_Repository.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Lesson_Progress\Repositories;

use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress_Interface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Tables_Based_Lesson_Progress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Aggregate_Lesson_Progress_Repository.
 *
 * Aggregate repository is an intermediate repository that delegates the calls to the appropriate repository implementation.
 *
 * @internal
 *
 * @since 4.16.1
 */
class Comment_Reading_Aggregate_Lesson_Progress_Repository implements Lesson_Progress_Repository_Interface {
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
	 * Aggregate_Lesson_Progress_Repository constructor.
	 *
	 * @internal
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
	 * @internal
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 * @return Lesson_Progress_Interface The lesson progress.
	 */
	public function create( int $lesson_id, int $user_id ): Lesson_Progress_Interface {
		$progress = $this->comments_based_repository->create( $lesson_id, $user_id );
		$this->tables_based_repository->create( $lesson_id, $user_id );
		return $progress;
	}

	/**
	 * Gets a lesson progress.
	 *
	 * @internal
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 * @return Lesson_Progress_Interface|null The lesson progress or null if it does not exist.
	 */
	public function get( int $lesson_id, int $user_id ): ?Lesson_Progress_Interface {
		return $this->comments_based_repository->get( $lesson_id, $user_id );
	}

	/**
	 * Checks if a lesson progress exists.
	 *
	 * @intenal
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 * @return bool Whether the lesson progress exists.
	 */
	public function has( int $lesson_id, int $user_id ): bool {
		return $this->comments_based_repository->has( $lesson_id, $user_id );
	}

	/**
	 * Save lesson progress.
	 *
	 * @internal
	 *
	 * @param Lesson_Progress_Interface $lesson_progress The lesson progress.
	 */
	public function save( Lesson_Progress_Interface $lesson_progress ): void {
		$this->comments_based_repository->save( $lesson_progress );

		$tables_based_progress = $this->tables_based_repository->get( $lesson_progress->get_lesson_id(), $lesson_progress->get_user_id() );
		if ( ! $tables_based_progress ) {
			$tables_based_progress = $this->tables_based_repository->create(
				$lesson_progress->get_lesson_id(),
				$lesson_progress->get_user_id()
			);
		}

		$started_at = null;
		if ( $lesson_progress->get_started_at() ) {
			$started_at = new \DateTimeImmutable( '@' . $lesson_progress->get_started_at()->getTimestamp() );
		}

		$completed_at = null;
		if ( $lesson_progress->get_completed_at() ) {
			$completed_at = new \DateTimeImmutable( '@' . $lesson_progress->get_completed_at()->getTimestamp() );
		}

		$progress_to_save = new Tables_Based_Lesson_Progress(
			$tables_based_progress->get_id(),
			$tables_based_progress->get_lesson_id(),
			$tables_based_progress->get_user_id(),
			$lesson_progress->get_status(),
			$started_at,
			$completed_at,
			$tables_based_progress->get_created_at(),
			$tables_based_progress->get_updated_at()
		);
		$this->tables_based_repository->save( $progress_to_save );
	}

	/**
	 * Deletes a lesson progress.
	 *
	 * @intenal
	 *
	 * @param Lesson_Progress_Interface $lesson_progress The lesson progress.
	 */
	public function delete( Lesson_Progress_Interface $lesson_progress ): void {
		$this->comments_based_repository->delete( $lesson_progress );
		$this->tables_based_repository->delete( $lesson_progress );
	}

	/**
	 * Deletes all lesson progress for a lesson.
	 *
	 * @internal
	 *
	 * @param int $lesson_id The lesson ID.
	 */
	public function delete_for_lesson( int $lesson_id ): void {
		$this->comments_based_repository->delete_for_lesson( $lesson_id );
		$this->tables_based_repository->delete_for_lesson( $lesson_id );
	}

	/**
	 * Deletes all lesson progress for a user.
	 *
	 * @internal
	 *
	 * @param int $user_id The user ID.
	 */
	public function delete_for_user( int $user_id ): void {
		$this->comments_based_repository->delete_for_user( $user_id );
		$this->tables_based_repository->delete_for_user( $user_id );
	}

	/**
	 * Returns the number of started lessons for a user in a course.
	 * The number of started lessons is the same as the number of lessons that have a progress record.
	 *
	 * @intenal
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return int
	 */
	public function count( int $course_id, int $user_id ): int {
		return $this->comments_based_repository->count( $course_id, $user_id );
	}

	/**
	 * Find lesson progress.
	 *
	 * @internal
	 *
	 * @param array $args The arguments.
	 * @return Lesson_Progress_Interface[]
	 */
	public function find( array $args ): array {
		return $this->comments_based_repository->find( $args );
	}
}

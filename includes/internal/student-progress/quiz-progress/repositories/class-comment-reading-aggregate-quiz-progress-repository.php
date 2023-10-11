<?php
/**
 * File containing the class Comment_Readng_Aggregate_Quiz_Progress_Repository.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Quiz_Progress\Repositories;

use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress_Interface;
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Tables_Based_Quiz_Progress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comment_Reading_Aggregate_Quiz_Progress_Repository.
 *
 * Aggregate repository is an intermediate repository that delegates the calls to the appropriate repository implementation.
 *
 * @internal
 *
 * @since 4.16.1
 */
class Comment_Reading_Aggregate_Quiz_Progress_Repository implements Quiz_Progress_Repository_Interface {
	/**
	 * Comments based quiz progress repository implementation.
	 *
	 * @var Comments_Based_Quiz_Progress_Repository
	 */
	private $comments_based_repository;

	/**
	 * Tables based quiz progress repository implementation.
	 *
	 * @var Tables_Based_Quiz_Progress_Repository
	 */
	private $tables_based_repository;

	/**
	 * Aggregate_Quiz_Progress_Repository constructor.
	 *
	 * @internal
	 *
	 * @param Comments_Based_Quiz_Progress_Repository $comments_based_repository Comments based quiz progress repository implementation.
	 * @param Tables_Based_Quiz_Progress_Repository   $tables_based_repository  Tables based quiz progress repository implementation.
	 */
	public function __construct( Comments_Based_Quiz_Progress_Repository $comments_based_repository, Tables_Based_Quiz_Progress_Repository $tables_based_repository ) {
		$this->comments_based_repository = $comments_based_repository;
		$this->tables_based_repository   = $tables_based_repository;
	}

	/**
	 * Creates a new quiz progress.
	 *
	 * @internal
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 * @return Quiz_Progress_Interface The quiz progress.
	 */
	public function create( int $quiz_id, int $user_id ): Quiz_Progress_Interface {
		$this->tables_based_repository->create( $quiz_id, $user_id );
		return $this->comments_based_repository->create( $quiz_id, $user_id );
	}

	/**
	 * Gets a quiz progress.
	 *
	 * @internal
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 * @return Quiz_Progress_Interface|null The quiz progress or null if it does not exist.
	 */
	public function get( int $quiz_id, int $user_id ): ?Quiz_Progress_Interface {
		return $this->comments_based_repository->get( $quiz_id, $user_id );
	}

	/**
	 * Checks if a quiz progress exists.
	 *
	 * @internal
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 * @return bool Whether the quiz progress exists.
	 */
	public function has( int $quiz_id, int $user_id ): bool {
		return $this->comments_based_repository->has( $quiz_id, $user_id );
	}

	/**
	 * Save quiz progress.
	 *
	 * @internal
	 *
	 * @param Quiz_Progress_Interface $quiz_progress The quiz progress.
	 */
	public function save( Quiz_Progress_Interface $quiz_progress ): void {
		$this->comments_based_repository->save( $quiz_progress );

		$tables_based_progress = $this->tables_based_repository->get( $quiz_progress->get_quiz_id(), $quiz_progress->get_user_id() );
		if ( ! $tables_based_progress ) {
			$tables_based_progress = $this->tables_based_repository->create(
				$quiz_progress->get_quiz_id(),
				$quiz_progress->get_user_id()
			);
		}

		$started_at = null;
		if ( $quiz_progress->get_started_at() ) {
			$started_at = new \DateTimeImmutable( '@' . $quiz_progress->get_started_at()->getTimestamp() );
		}

		$completed_at = null;
		if ( $quiz_progress->get_completed_at() ) {
			$completed_at = new \DateTimeImmutable( '@' . $quiz_progress->get_completed_at()->getTimestamp() );
		}

		$progress_to_save = new Tables_Based_Quiz_Progress(
			$tables_based_progress->get_id(),
			$tables_based_progress->get_quiz_id(),
			$tables_based_progress->get_user_id(),
			$quiz_progress->get_status(),
			$started_at,
			$completed_at,
			$tables_based_progress->get_created_at(),
			$tables_based_progress->get_updated_at()
		);
		$this->tables_based_repository->save( $progress_to_save );
	}

	/**
	 * Deletes a quiz progress.
	 *
	 * @internal
	 *
	 * @param Quiz_Progress_Interface $quiz_progress The quiz progress.
	 */
	public function delete( Quiz_Progress_Interface $quiz_progress ): void {
		$this->comments_based_repository->delete( $quiz_progress );
		$this->tables_based_repository->delete( $quiz_progress );
	}

	/**
	 * Deletes all quiz progresses for the given quiz.
	 *
	 * @internal
	 *
	 * @param int $quiz_id The quiz ID.
	 */
	public function delete_for_quiz( int $quiz_id ): void {
		$this->comments_based_repository->delete_for_quiz( $quiz_id );
		$this->tables_based_repository->delete_for_quiz( $quiz_id );
	}

	/**
	 * Deletes all quiz progresses for the given user.
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
	 * Find quiz progress.
	 *
	 * @internal
	 *
	 * @param array $args The arguments.
	 * @return Quiz_Progress_Interface[] The quiz progress.
	 */
	public function find( array $args ): array {
		return $this->comments_based_repository->find( $args );
	}
}

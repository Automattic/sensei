<?php
/**
 * File containing the class Table_Readng_Aggregate_Quiz_Progress_Repository.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Quiz_Progress\Repositories;

use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Table_Reading_Aggregate_Quiz_Progress_Repository.
 *
 * @internal
 *
 * @since $$next_version$$
 */
class Table_Reading_Aggregate_Quiz_Progress_Repository implements Quiz_Progress_Repository_Interface {
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
	 * Constructor for the Table_Reading_Aggregate_Quiz_Progress_Repository class.
	 *
	 * @param Comments_Based_Quiz_Progress_Repository $comments_based_repository Comments based quiz progress repository implementation.
	 * @param Tables_Based_Quiz_Progress_Repository   $tables_based_repository  Tables based quiz progress repository implementation.
	 */
	public function __construct(
		Comments_Based_Quiz_Progress_Repository $comments_based_repository,
		Tables_Based_Quiz_Progress_Repository $tables_based_repository
	) {
		$this->comments_based_repository = $comments_based_repository;
		$this->tables_based_repository   = $tables_based_repository;
	}

	/**
	 * Creates a new quiz progress.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 * @return Quiz_Progress The quiz progress.
	 */
	public function create(int $quiz_id, int $user_id): Quiz_Progress {
		$this->comments_based_repository->create( $quiz_id, $user_id );
		return $this->tables_based_repository->create( $quiz_id, $user_id );
	}

	/**
	 * Gets the quiz progress for a given quiz and user.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 * @return Quiz_Progress|null The quiz progress or null if it does not exist.
	 */
	public function get(int $quiz_id, int $user_id): ?Quiz_Progress {
		return $this->tables_based_repository->get( $quiz_id, $user_id );
	}

	/**
	 * Checks if a quiz progress exists for a given quiz and user.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 * @return bool True if the quiz progress exists, false otherwise.
	 */
	public function has(int $quiz_id, int $user_id): bool {
		return $this->tables_based_repository->has( $quiz_id, $user_id );
	}

	/**
	 * Save quiz progress.
	 *
	 * @param Quiz_Progress $quiz_progress The quiz progress.
	 */
	public function save(Quiz_Progress $quiz_progress): void {
		$this->tables_based_repository->save( $quiz_progress );
		$comments_based_quiz_progress = $this->comments_based_repository->get( $quiz_progress->get_quiz_id(), $quiz_progress->get_user_id() );
		if ( ! $comments_based_quiz_progress ) {
			$comments_based_quiz_progress = $this->comments_based_repository->create(
				$quiz_progress->get_quiz_id(),
				$quiz_progress->get_user_id()
			);
		}
		$updated_comments_based_quiz_progress = new Quiz_Progress(
			$comments_based_quiz_progress->get_id(),
			$quiz_progress->get_quiz_id(),
			$quiz_progress->get_user_id(),
			$quiz_progress->get_status(),
			$quiz_progress->get_started_at(),
			$quiz_progress->get_completed_at(),
			$quiz_progress->get_created_at(),
			$quiz_progress->get_updated_at()
		);
		$this->comments_based_repository->save( $updated_comments_based_quiz_progress );
	}

	/**
	 * Deletes a quiz progress.
	 *
	 * @param Quiz_Progress $quiz_progress The quiz progress.
	 */
	public function delete(Quiz_Progress $quiz_progress): void {
		$this->tables_based_repository->delete( $quiz_progress );
		$coments_based_quiz_progress = $this->comments_based_repository->get( $quiz_progress->get_quiz_id(), $quiz_progress->get_user_id() );
		if ( $coments_based_quiz_progress ) {
			$this->comments_based_repository->delete( $coments_based_quiz_progress );
		}
	}

	/**
	 * Deletes all quiz progress for a given quiz.
	 *
	 * @param int $quiz_id The quiz ID.
	 */
	public function delete_for_quiz(int $quiz_id): void {
		$this->comments_based_repository->delete_for_quiz( $quiz_id );
		$this->tables_based_repository->delete_for_quiz( $quiz_id );
	}

	/**
	 * Deletes all quiz progress for a given user.
	 *
	 * @param int $user_id The user ID.
	 */
	public function delete_for_user(int $user_id): void {
		$this->comments_based_repository->delete_for_user( $user_id );
		$this->tables_based_repository->delete_for_user( $user_id );
	}

}

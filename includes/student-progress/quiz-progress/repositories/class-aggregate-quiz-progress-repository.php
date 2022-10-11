<?php
/**
 * File containing the class Aggregate_Quiz_Progress_Repository.
 *
 * @package sensei
 */

namespace Sensei\Student_Progress\Quiz_Progress\Repositories;

use Sensei\Student_Progress\Quiz_Progress\Models\Quiz_Progress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Aggregate_Quiz_Progress_Repository.
 *
 * Aggregate repository is an intermediate repository that delegates the calls to the appropriate repository implementation.
 *
 * @since $$next-version$$
 */
class Aggregate_Quiz_Progress_Repository implements Quiz_Progress_Repository_Interface {
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
	 * The flag if the tables based implementation is available for use.
	 *
	 * @var bool
	 */
	private $use_tables;

	/**
	 * Aggregate_Quiz_Progress_Repository constructor.
	 *
	 * @param Comments_Based_Quiz_Progress_Repository $comments_based_repository Comments based quiz progress repository implementation.
	 * @param Tables_Based_Quiz_Progress_Repository   $tables_based_repository  Tables based quiz progress repository implementation.
	 * @param bool                                    $use_tables  The flag if the tables based implementation is available for use.
	 */
	public function __construct( Comments_Based_Quiz_Progress_Repository $comments_based_repository, Tables_Based_Quiz_Progress_Repository $tables_based_repository, bool $use_tables ) {
		$this->comments_based_repository = $comments_based_repository;
		$this->tables_based_repository   = $tables_based_repository;
		$this->use_tables                = $use_tables;
	}

	/**
	 * Creates a new quiz progress.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 * @return Quiz_Progress The quiz progress.
	 */
	public function create( int $quiz_id, int $user_id ): Quiz_Progress {
		$progress = $this->comments_based_repository->create( $quiz_id, $user_id );
		if ( $this->use_tables ) {
			$this->tables_based_repository->create( $quiz_id, $user_id );
		}
		return $progress;
	}

	/**
	 * Gets a quiz progress.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 * @return Quiz_Progress|null The quiz progress or null if it does not exist.
	 */
	public function get( int $quiz_id, int $user_id ): ?Quiz_Progress {
		return $this->comments_based_repository->get( $quiz_id, $user_id );
	}

	/**
	 * Checks if a quiz progress exists.
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
	 * @param Quiz_Progress $quiz_progress The quiz progress.
	 */
	public function save( Quiz_Progress $quiz_progress ): void {
		$this->comments_based_repository->save( $quiz_progress );
		if ( $this->use_tables ) {
			$tables_based_progress = $this->tables_based_repository->get( $quiz_progress->get_quiz_id(), $quiz_progress->get_user_id() );
			if ( $tables_based_progress ) {

				$progress_to_save = new Quiz_Progress(
					$tables_based_progress->get_id(),
					$tables_based_progress->get_quiz_id(),
					$tables_based_progress->get_user_id(),
					$quiz_progress->get_status(),
					$quiz_progress->get_started_at(),
					$quiz_progress->get_completed_at(),
					$tables_based_progress->get_created_at(),
					$tables_based_progress->get_updated_at()
				);
				$this->tables_based_repository->save( $progress_to_save );
			}
		}
	}
}

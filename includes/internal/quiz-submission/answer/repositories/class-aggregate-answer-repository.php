<?php
/**
 * File containing the Aggregate_Answer_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Answer\Repositories;

use Sensei\Internal\Quiz_Submission\Answer\Models\Answer;
use Sensei\Internal\Quiz_Submission\Submission\Models\Submission;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Tables_Based_Submission_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Aggregate_Answer_Repository.
 *
 * @internal
 *
 * @since 4.16.1
 */
class Aggregate_Answer_Repository implements Answer_Repository_Interface {
	/**
	 * Comments based quiz answer repository implementation.
	 *
	 * @var Comments_Based_Answer_Repository
	 */
	private $comments_based_repository;

	/**
	 * Tables based quiz answer repository implementation.
	 *
	 * @var Tables_Based_Answer_Repository
	 */
	private $tables_based_repository;


	/**
	 * Tables based quiz submission repository.
	 *
	 * @var Tables_Based_Submission_Repository
	 */
	private $tables_based_submission_repository;

	/**
	 * The flag if the tables based implementation is available for use.
	 *
	 * @var bool
	 */
	private $use_tables;

	/**
	 * Constructor.
	 *
	 * @internal
	 *
	 * @param Comments_Based_Answer_Repository   $comments_based_repository Comments based quiz answer repository implementation.
	 * @param Tables_Based_Answer_Repository     $tables_based_repository  Tables based quiz answer repository implementation.
	 * @param Tables_Based_Submission_Repository $tables_based_submission_repository Tables based quiz submission repository.
	 * @param bool                               $use_tables  The flag if the tables based implementation is available for use.
	 */
	public function __construct(
		Comments_Based_Answer_Repository $comments_based_repository,
		Tables_Based_Answer_Repository $tables_based_repository,
		Tables_Based_Submission_Repository $tables_based_submission_repository,
		bool $use_tables
	) {
		$this->comments_based_repository          = $comments_based_repository;
		$this->tables_based_repository            = $tables_based_repository;
		$this->tables_based_submission_repository = $tables_based_submission_repository;
		$this->use_tables                         = $use_tables;
	}

	/**
	 * Create a new answer.
	 *
	 * @internal
	 *
	 * @param Submission $submission  The submission.
	 * @param int        $question_id The question ID.
	 * @param string     $value       The answer value.
	 *
	 * @return Answer The answer model.
	 */
	public function create( Submission $submission, int $question_id, string $value ): Answer {
		$answer = $this->comments_based_repository->create( $submission, $question_id, $value );

		if ( $this->use_tables ) {
			$tables_based_submission = $this->get_tables_based_submission( $submission );
			if ( $tables_based_submission ) {
				$this->tables_based_repository->create( $tables_based_submission, $question_id, $value );
			}
		}

		return $answer;
	}

	/**
	 * Get all answers for a quiz submission.
	 *
	 * @internal
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Answer[] An array of answers.
	 */
	public function get_all( int $submission_id ): array {
		return $this->comments_based_repository->get_all( $submission_id );
	}

	/**
	 * Delete all answers for a submission.
	 *
	 * @internal
	 *
	 * @param Submission $submission The submission.
	 */
	public function delete_all( Submission $submission ): void {
		$this->comments_based_repository->delete_all( $submission );

		if ( $this->use_tables ) {
			$tables_based_submission = $this->get_tables_based_submission( $submission );
			if ( $tables_based_submission ) {
				$this->tables_based_repository->delete_all( $tables_based_submission );
			}
		}
	}

	/**
	 * Get the tables based submission for a given submission.
	 *
	 * @param Submission $submission The submission.
	 *
	 * @return Submission|null The tables based submission or null if it does not exist.
	 */
	private function get_tables_based_submission( Submission $submission ): ?Submission {
		return $this->tables_based_submission_repository->get( $submission->get_quiz_id(), $submission->get_user_id() );
	}
}

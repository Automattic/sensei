<?php
/**
 * File containing the Table_Reading_Aggregate_Answer_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Answer\Repositories;

use Sensei\Internal\Quiz_Submission\Answer\Models\Answer_Interface;
use Sensei\Internal\Quiz_Submission\Submission\Models\Submission_Interface;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Comments_Based_Submission_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Table_Reading_Aggregate_Answer_Repository.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Table_Reading_Aggregate_Answer_Repository implements Answer_Repository_Interface {
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
	 * Comments based quiz submission repository.
	 *
	 * @var Comments_Based_Submission_Repository
	 */
	private $comments_based_submission_repository;

	/**
	 * Constructor.
	 *
	 * @internal
	 *
	 * @param Comments_Based_Answer_Repository     $comments_based_repository Comments based quiz answer repository implementation.
	 * @param Tables_Based_Answer_Repository       $tables_based_repository  Tables based quiz answer repository implementation.
	 * @param Comments_Based_Submission_Repository $comments_based_submission_repository Tables based quiz submission repository.
	 */
	public function __construct(
		Comments_Based_Answer_Repository $comments_based_repository,
		Tables_Based_Answer_Repository $tables_based_repository,
		Comments_Based_Submission_Repository $comments_based_submission_repository
	) {
		$this->comments_based_repository            = $comments_based_repository;
		$this->tables_based_repository              = $tables_based_repository;
		$this->comments_based_submission_repository = $comments_based_submission_repository;
	}

	/**
	 * Create a new answer.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission  The submission.
	 * @param int                  $question_id The question ID.
	 * @param string               $value       The answer value.
	 *
	 * @return Answer_Interface The answer model.
	 */
	public function create( Submission_Interface $submission, int $question_id, string $value ): Answer_Interface {
		$answer = $this->tables_based_repository->create( $submission, $question_id, $value );

		$comments_based_submission = $this->get_or_create_comments_based_submission( $submission );
		$this->comments_based_repository->create( $comments_based_submission, $question_id, $value );

		return $answer;
	}

	/**
	 * Get all answers for a quiz submission.
	 *
	 * @internal
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Answer_Interface[] An array of answers.
	 */
	public function get_all( int $submission_id ): array {
		return $this->tables_based_repository->get_all( $submission_id );
	}

	/**
	 * Delete all answers for a submission.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission The submission.
	 */
	public function delete_all( Submission_Interface $submission ): void {
		$this->tables_based_repository->delete_all( $submission );

		$comments_based_submission = $this->get_or_create_comments_based_submission( $submission );
		$this->comments_based_repository->delete_all( $comments_based_submission );
	}

	/**
	 * Get the comments-based submission for a given tables-based submission or create if not exists.
	 *
	 * @param Submission_Interface $submission The submission.
	 * @return Submission_Interface The comments-based submission or null if it does not exist.
	 */
	private function get_or_create_comments_based_submission( Submission_Interface $submission ): Submission_Interface {
		return $this->comments_based_submission_repository->get_or_create(
			$submission->get_quiz_id(),
			$submission->get_user_id(),
			$submission->get_final_grade()
		);
	}
}

<?php
/**
 * File containing the Aggregate_Answer_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Answer\Repositories;

use Sensei\Internal\Quiz_Submission\Answer\Models\Answer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Aggregate_Answer_Repository.
 *
 * @internal
 *
 * @since $$next-version$$
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
	 * @param Comments_Based_Answer_Repository $comments_based_repository Comments based quiz answer repository implementation.
	 * @param Tables_Based_Answer_Repository   $tables_based_repository  Tables based quiz answer repository implementation.
	 * @param bool                             $use_tables  The flag if the tables based implementation is available for use.
	 */
	public function __construct( Comments_Based_Answer_Repository $comments_based_repository, Tables_Based_Answer_Repository $tables_based_repository, bool $use_tables ) {
		$this->comments_based_repository = $comments_based_repository;
		$this->tables_based_repository   = $tables_based_repository;
		$this->use_tables                = $use_tables;
	}

	/**
	 * Create a new answer.
	 *
	 * @internal
	 *
	 * @param int    $submission_id The submission ID.
	 * @param int    $question_id   The question ID.
	 * @param string $value         The answer value.
	 *
	 * @return Answer The answer model.
	 */
	public function create( int $submission_id, int $question_id, string $value ): Answer {
		$answer = $this->comments_based_repository->create( $submission_id, $question_id, $value );

		if ( $this->use_tables ) {
			$this->tables_based_repository->create( $submission_id, $question_id, $value );
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
	 * @param int $submission_id The submission ID.
	 */
	public function delete_all( int $submission_id ): void {
		$this->comments_based_repository->delete_all( $submission_id );

		if ( $this->use_tables ) {
			$this->tables_based_repository->delete_all( $submission_id );
		}
	}
}

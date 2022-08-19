<?php
/**
 * File containing the Answer_Repository_Aggregate class.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Repositories;

use Sensei\Quiz_Submission\Models\Answer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Answer_Repository_Aggregate.
 *
 * @since $$next-version$$
 */
class Answer_Repository_Aggregate implements Answer_Repository_Interface {
	/**
	 * Repository for answer in custom tables.
	 *
	 * @var Answer_Tables_Repository
	 */
	private $tables_repository;

	/**
	 * Repository for answer in comments.
	 *
	 * @var Answer_Comments_Repository
	 */
	private $comments_repository;

	/**
	 * Whether to use the custom tables or the comments.
	 *
	 * @var bool
	 */
	private $use_tables;

	/**
	 * Constructor.
	 *
	 * @param Answer_Tables_Repository   $tables_repository   Repository for answer in custom tables.
	 * @param Answer_Comments_Repository $comments_repository Repository for answer in comments.
	 * @param bool                       $use_tables          Whether to use the custom tables repository.
	 */
	public function __construct(
		Answer_Tables_Repository $tables_repository,
		Answer_Comments_Repository $comments_repository,
		bool $use_tables = true
	) {
		$this->tables_repository   = $tables_repository;
		$this->comments_repository = $comments_repository;
		$this->use_tables          = $use_tables;
	}

	/**
	 * Create a new answer.
	 *
	 * @param int    $submission_id The submission ID.
	 * @param int    $question_id   The question ID.
	 * @param string $value         The answer string.
	 *
	 * @return Answer The answer model.
	 */
	public function create( int $submission_id, int $question_id, string $value ): Answer {
		if ( $this->use_tables ) {
			$this->tables_repository->create( $submission_id, $question_id, $value );
		}

		return $this->comments_repository->create( $submission_id, $question_id, $value );
	}

	/**
	 * Get an answer.
	 *
	 * @param int $submission_id The submission ID.
	 * @param int $question_id   The question ID.
	 *
	 * @return Answer|null The answer model.
	 */
	public function get( int $submission_id, int $question_id ): ?Answer {
		if ( $this->use_tables ) {
			return $this->tables_repository->get( $submission_id, $question_id );
		}

		return $this->comments_repository->get( $submission_id, $question_id );
	}

	/**
	 * Get all answers for a submission.
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Answer[] An array of answers.
	 */
	public function get_all_for_submission( int $submission_id ): array {
		if ( $this->use_tables ) {
			return $this->tables_repository->get_all_for_submission( $submission_id );
		}

		return $this->comments_repository->get_all_for_submission( $submission_id );
	}

	/**
	 * Save the answer.
	 *
	 * @param Answer $answer The answer model.
	 */
	public function save( Answer $answer ): void {
		$this->comments_repository->save( $answer );

		if ( $this->use_tables ) {
			$this->tables_repository->save( $answer );
		}
	}

	/**
	 * Delete all answers, including their grades.
	 *
	 * @param int $submission_id The submission ID.
	 */
	public function delete_all_answers_and_grades_for_submission( int $submission_id ): void {
		if ( $this->use_tables ) {
			$this->tables_repository->delete_all_answers_and_grades_for_submission( $submission_id );
		}

		$this->comments_repository->delete_all_answers_and_grades_for_submission( $submission_id );
	}
}

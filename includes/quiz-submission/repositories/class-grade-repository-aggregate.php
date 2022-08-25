<?php
/**
 * File containing the Grade_Repository_Aggregate class.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Repositories;

use Sensei\Quiz_Submission\Models\Grade;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Grade_Repository_Aggregate.
 *
 * @since $$next-version$$
 */
class Grade_Repository_Aggregate implements Grade_Repository_Interface {
	/**
	 * Repository for grade in custom tables.
	 *
	 * @var Grade_Tables_Repository
	 */
	private $tables_repository;

	/**
	 * Repository for grade in comments.
	 *
	 * @var Grade_Comments_Repository
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
	 * @param Grade_Tables_Repository   $tables_repository   Repository for grade in custom tables.
	 * @param Grade_Comments_Repository $comments_repository Repository for grade in comments.
	 * @param bool                      $use_tables          Whether to use the custom tables repository.
	 */
	public function __construct(
		Grade_Tables_Repository $tables_repository,
		Grade_Comments_Repository $comments_repository,
		bool $use_tables = true
	) {
		$this->tables_repository   = $tables_repository;
		$this->comments_repository = $comments_repository;
		$this->use_tables          = $use_tables;
	}

	/**
	 * Create a new grade.
	 *
	 * @param int         $answer_id   The answer ID.
	 * @param int         $question_id The question ID.
	 * @param int         $points      The points.
	 * @param string|null $feedback    The feedback.
	 *
	 * @return Grade The grade model.
	 */
	public function create( int $answer_id, int $question_id, int $points, string $feedback = null ): Grade {
		if ( $this->use_tables ) {
			$this->tables_repository->create( $answer_id, $question_id, $points, $feedback );
		}

		return $this->comments_repository->create( $answer_id, $question_id, $points, $feedback );
	}

	/**
	 * Get a grade.
	 *
	 * @param int $answer_id The answer ID.
	 *
	 * @return Grade|null The grade model.
	 */
	public function get( int $answer_id ): ?Grade {
		if ( $this->use_tables ) {
			return $this->tables_repository->get( $answer_id );
		}

		return $this->comments_repository->get( $answer_id );
	}

	/**
	 * Get all grades for a quiz submission.
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Grade[] An array of grades.
	 */
	public function get_all( int $submission_id ): array {
		if ( $this->use_tables ) {
			return $this->tables_repository->get_all( $submission_id );
		}

		return $this->comments_repository->get_all( $submission_id );
	}

	/**
	 * Delete all grades for a submission.
	 *
	 * @param int $submission_id The submission ID.
	 */
	public function delete_all( int $submission_id ): void {
		if ( $this->use_tables ) {
			$this->tables_repository->delete_all( $submission_id );
		}

		$this->comments_repository->delete_all( $submission_id );
	}
}

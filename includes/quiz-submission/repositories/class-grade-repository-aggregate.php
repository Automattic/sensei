<?php
/**
 * File containing the Grade_Repository_Aggregate class.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Repositories;

use Sensei\Quiz_Submission\Models\Grade_Interface;

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
	 * Creates a new grade.
	 *
	 * @param int         $submission_id The submission ID.
	 * @param int         $answer_id     The answer ID.
	 * @param int         $question_id   The question ID.
	 * @param int         $points        The points.
	 * @param string|null $feedback      The feedback.
	 *
	 * @return Grade_Interface The grade.
	 */
	public function create( int $submission_id, int $answer_id, int $question_id, int $points, string $feedback = null ): Grade_Interface {
		if ( $this->use_tables ) {
			return $this->tables_repository->create( $submission_id, $answer_id, $question_id, $points, $feedback );
		}

		return $this->comments_repository->create( $submission_id, $answer_id, $question_id, $points, $feedback );
	}

	/**
	 * Get all grades for a quiz submission.
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Grade_Interface[] An array of grades.
	 */
	public function get_all( int $submission_id ): array {
		if ( $this->use_tables ) {
			return $this->tables_repository->get_all( $submission_id );
		}

		return $this->comments_repository->get_all( $submission_id );
	}

	/**
	 * Save multiple grades.
	 *
	 * @param Grade_Interface[] $grades        An array of grades.
	 * @param int               $submission_id The submission ID.
	 */
	public function save_many( array $grades, int $submission_id ): void {
		if ( $this->use_tables ) {
			$this->tables_repository->save_many( $grades, $submission_id );
		}

		$this->comments_repository->save_many( $grades, $submission_id );
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

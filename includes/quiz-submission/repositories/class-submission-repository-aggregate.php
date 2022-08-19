<?php
/**
 * File containing the Submission_Repository_Aggregate class.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Repositories;

use Sensei\Quiz_Submission\Models\Submission;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Submission_Repository_Aggregate.
 *
 * @since $$next-version$$
 */
class Submission_Repository_Aggregate implements Submission_Repository_Interface {
	/**
	 * Repository for quiz submission in custom tables.
	 *
	 * @var Submission_Tables_Repository
	 */
	private $tables_repository;

	/**
	 * Repository for quiz submission in comments.
	 *
	 * @var Submission_Comments_Repository
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
	 * @param Submission_Tables_Repository   $tables_repository   Repository for quiz submission in custom tables.
	 * @param Submission_Comments_Repository $comments_repository Repository for quiz submission in comments.
	 * @param bool                           $use_tables          Whether to use the custom tables repository.
	 */
	public function __construct(
		Submission_Tables_Repository $tables_repository,
		Submission_Comments_Repository $comments_repository,
		bool $use_tables = true
	) {
		$this->tables_repository   = $tables_repository;
		$this->comments_repository = $comments_repository;
		$this->use_tables          = $use_tables;
	}

	/**
	 * Creates a new quiz submission.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Submission The quiz submission.
	 */
	public function create( int $quiz_id, int $user_id ): Submission {
		if ( $this->use_tables ) {
			$this->tables_repository->create( $quiz_id, $user_id );
		}

		return $this->comments_repository->create( $quiz_id, $user_id );
	}

	/**
	 * Get or create a new quiz submission if it doesn't exist.
	 *
	 * @param int        $quiz_id     The quiz ID.
	 * @param int        $user_id     The user ID.
	 * @param float|null $final_grade The final grade.
	 *
	 * @return Submission The quiz submission.
	 */
	public function get_or_create( int $quiz_id, int $user_id, float $final_grade = null ): Submission {
		if ( $this->use_tables ) {
			$submission = $this->tables_repository->get( $quiz_id, $user_id );

			if ( $submission ) {
				return $submission;
			}

			$this->comments_repository->create( $quiz_id, $user_id, $final_grade );
			return $this->tables_repository->create( $quiz_id, $user_id, $final_grade );
		}

		return $this->comments_repository->get_or_create( $quiz_id, $user_id );
	}

	/**
	 * Gets a quiz submission.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Submission|null The quiz submission.
	 */
	public function get( int $quiz_id, int $user_id ): ?Submission {
		if ( $this->use_tables ) {
			return $this->tables_repository->get( $quiz_id, $user_id );
		}

		return $this->comments_repository->get( $quiz_id, $user_id );
	}

	/**
	 * Save quiz submission.
	 *
	 * @param Submission $submission The quiz submission.
	 */
	public function save( Submission $submission ): void {
		$this->comments_repository->save( $submission );

		if ( $this->use_tables ) {
			$this->tables_repository->save( $submission );
		}
	}
}

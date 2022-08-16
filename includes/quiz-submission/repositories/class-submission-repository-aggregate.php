<?php
/**
 * File containing the Repository_Aggregate class.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Repositories;

use Sensei\Quiz_Submission\Models\Submission;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Repository_Aggregate.
 *
 * @since $$next-version$$
 */
class Submission_Repository_Aggregate implements Submission_Repository_Interface {
	/**
	 * Repository for quiz submission in custom tables.
	 *
	 * @var Submission_Tables_Repository
	 */
	private $repository_tables;

	/**
	 * Repository for quiz submission in comments.
	 *
	 * @var Submission_Comments_Repository
	 */
	private $repository_comments;

	/**
	 * Whether to use the custom tables or the comments.
	 *
	 * @var bool
	 */
	private $use_tables;

	/**
	 * Constructor.
	 *
	 * @param Submission_Tables_Repository   $tables     Repository for quiz submission in custom tables.
	 * @param Submission_Comments_Repository $comments   Repository for quiz submission in comments.
	 * @param bool                           $use_tables Whether to use the custom tables repository.
	 */
	public function __construct(
		Submission_Tables_Repository $tables,
		Submission_Comments_Repository $comments,
		bool $use_tables = true
	) {
		$this->repository_tables   = $tables;
		$this->repository_comments = $comments;
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
			$this->repository_tables->create( $quiz_id, $user_id, $quiz_answers );
		}

		return $this->repository_comments->create( $quiz_id, $user_id );
	}

	/**
	 * Get or create a new quiz submission if it doesn't exist.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Submission The quiz submission.
	 */
	public function get_or_create( int $quiz_id, int $user_id ): Submission {
		// TODO: Implement get_or_create() method.
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
			return $this->repository_tables->get( $quiz_id, $user_id );
		}

		return $this->repository_comments->get( $quiz_id, $user_id );
	}

	/**
	 * Checks if a quiz submission exists.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return bool Whether the quiz submission exists.
	 */
	public function has( int $quiz_id, int $user_id ): bool {
		if ( $this->use_tables ) {
			return $this->repository_tables->has( $quiz_id, $user_id );
		}

		return $this->repository_comments->has( $quiz_id, $user_id );
	}

	/**
	 * Save quiz submission.
	 *
	 * @param Submission $submission The quiz submission.
	 */
	public function save( Submission $submission ): void {
		$this->repository_comments->save( $submission );

		if ( $this->use_tables ) {
			$this->repository_tables->save( $submission );
		}
	}
}

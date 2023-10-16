<?php
/**
 * File containing the Comment_Reading_Aggregate_Submission_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Submission\Repositories;

use DateTimeImmutable;
use Sensei\Internal\Quiz_Submission\Submission\Models\Submission_Interface;
use Sensei\Internal\Quiz_Submission\Submission\Models\Tables_Based_Submission;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comment_Reading_Aggregate_Submission_Repository.
 *
 * @internal
 *
 * @since 4.16.1
 */
class Comment_Reading_Aggregate_Submission_Repository implements Submission_Repository_Interface {
	/**
	 * Comments based quiz submission repository implementation.
	 *
	 * @var Comments_Based_Submission_Repository
	 */
	private $comments_based_repository;

	/**
	 * Tables based quiz submission repository implementation.
	 *
	 * @var Tables_Based_Submission_Repository
	 */
	private $tables_based_repository;

	/**
	 * Constructor.
	 *
	 * @internal
	 *
	 * @param Comments_Based_Submission_Repository $comments_based_repository Comments based quiz submission repository implementation.
	 * @param Tables_Based_Submission_Repository   $tables_based_repository  Tables based quiz submission repository implementation.
	 */
	public function __construct( Comments_Based_Submission_Repository $comments_based_repository, Tables_Based_Submission_Repository $tables_based_repository ) {
		$this->comments_based_repository = $comments_based_repository;
		$this->tables_based_repository   = $tables_based_repository;
	}

	/**
	 * Creates a new quiz submission.
	 *
	 * @internal
	 *
	 * @param int        $quiz_id     The quiz ID.
	 * @param int        $user_id     The user ID.
	 * @param float|null $final_grade The final grade.
	 *
	 * @return Submission_Interface The quiz submission.
	 */
	public function create( int $quiz_id, int $user_id, float $final_grade = null ): Submission_Interface {
		$submission = $this->comments_based_repository->create( $quiz_id, $user_id, $final_grade );
		$this->tables_based_repository->create( $quiz_id, $user_id, $final_grade );

		return $submission;
	}

	/**
	 * Get or create a new quiz submission if it doesn't exist.
	 *
	 * @internal
	 *
	 * @param int        $quiz_id     The quiz ID.
	 * @param int        $user_id     The user ID.
	 * @param float|null $final_grade The final grade.
	 *
	 * @return Submission_Interface The quiz submission.
	 */
	public function get_or_create( int $quiz_id, int $user_id, float $final_grade = null ): Submission_Interface {
		return $this->comments_based_repository->get_or_create( $quiz_id, $user_id, $final_grade );
	}

	/**
	 * Gets a quiz submission.
	 *
	 * @internal
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Submission_Interface|null The quiz submission.
	 */
	public function get( int $quiz_id, int $user_id ): ?Submission_Interface {
		return $this->comments_based_repository->get( $quiz_id, $user_id );
	}

	/**
	 * Get the questions related to the quiz submission.
	 *
	 * @internal
	 *
	 * @param int $submission_id The quiz submission ID.
	 *
	 * @return array An array of question post IDs.
	 */
	public function get_question_ids( int $submission_id ): array {
		return $this->comments_based_repository->get_question_ids( $submission_id );
	}

	/**
	 * Save quiz submission.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission The quiz submission.
	 */
	public function save( Submission_Interface $submission ): void {
		$this->comments_based_repository->save( $submission );

		$tables_based_submission = $this->tables_based_repository->get_or_create(
			$submission->get_quiz_id(),
			$submission->get_user_id(),
			$submission->get_final_grade()
		);

		// Make sure the dates are in UTC.
		$created_at = new DateTimeImmutable( '@' . $submission->get_created_at()->getTimestamp() );
		$updated_at = new DateTimeImmutable( '@' . $submission->get_updated_at()->getTimestamp() );

		$submission_to_save = new Tables_Based_Submission(
			$tables_based_submission->get_id(),
			$submission->get_quiz_id(),
			$submission->get_user_id(),
			$submission->get_final_grade(),
			$created_at,
			$updated_at
		);

		$this->tables_based_repository->save( $submission_to_save );
	}

	/**
	 * Delete the quiz submission.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission The quiz submission.
	 */
	public function delete( Submission_Interface $submission ): void {
		$this->comments_based_repository->delete( $submission );
		$this->tables_based_repository->delete( $submission );
	}
}

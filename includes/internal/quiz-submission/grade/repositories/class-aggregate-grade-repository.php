<?php
/**
 * File containing the class Aggregate_Grade_Repository.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Grade\Repositories;

use DateTimeImmutable;
use Sensei\Internal\Quiz_Submission\Answer\Models\Answer;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Tables_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Grade\Models\Grade;
use Sensei\Internal\Quiz_Submission\Submission\Models\Submission;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Comments_Based_Submission_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Tables_Based_Submission_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Aggregate_Grade_Repository
 *
 * @internal
 *
 * @since 4.16.1
 */
class Aggregate_Grade_Repository implements Grade_Repository_Interface {
	/**
	 * Comments based quiz grade repository implementation.
	 *
	 * @var Comments_Based_Grade_Repository
	 */
	private $comments_based_repository;

	/**
	 * Tables based quiz grade repository implementation.
	 *
	 * @var Tables_Based_Grade_Repository
	 */
	private $tables_based_repository;


	/**
	 * Tables based quiz submission repository implementation.
	 *
	 * @var Tables_Based_Submission_Repository
	 */
	private $tables_based_submission_repository;

	/**
	 * Tables baesd answer repository.
	 *
	 * @var Tables_Based_Answer_Repository
	 */
	private $tables_based_answer_repository;

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
	 * @param Comments_Based_Grade_Repository    $comments_based_repository Comments based quiz answer repository implementation.
	 * @param Tables_Based_Grade_Repository      $tables_based_repository  Tables based quiz answer repository implementation.
	 * @param Tables_Based_Submission_Repository $tables_based_submission_repository Tables based quiz submission repository implementation.
	 * @param Tables_Based_Answer_Repository     $tables_based_answer_repository Tables based quiz answer repository implementation.
	 * @param bool                               $use_tables  The flag if the tables based implementation is available for use.
	 */
	public function __construct(
		Comments_Based_Grade_Repository $comments_based_repository,
		Tables_Based_Grade_Repository $tables_based_repository,
		Tables_Based_Submission_Repository $tables_based_submission_repository,
		Tables_Based_Answer_Repository $tables_based_answer_repository,
		bool $use_tables
	) {
		$this->comments_based_repository          = $comments_based_repository;
		$this->tables_based_repository            = $tables_based_repository;
		$this->tables_based_submission_repository = $tables_based_submission_repository;
		$this->tables_based_answer_repository     = $tables_based_answer_repository;
		$this->use_tables                         = $use_tables;
	}

	/**
	 * Creates a new grade.
	 *
	 * @internal
	 *
	 * @param Submission  $submission  The submission ID.
	 * @param int         $answer_id   The answer ID.
	 * @param int         $question_id The question ID.
	 * @param int         $points      The points.
	 * @param string|null $feedback    The feedback.
	 *
	 * @return Grade The grade.
	 */
	public function create( Submission $submission, int $answer_id, int $question_id, int $points, ?string $feedback = null ): Grade {
		$grade = $this->comments_based_repository->create( $submission, $answer_id, $question_id, $points, $feedback );

		if ( $this->use_tables ) {
			$tables_based_submission = $this->tables_based_submission_repository->get( $submission->get_quiz_id(), $submission->get_user_id() );
			if ( $tables_based_submission ) {
				$answers  = $this->tables_based_answer_repository->get_all( $tables_based_submission->get_id() );
				$filtered = array_filter(
					$answers,
					function( Answer $answer ) use ( $question_id ) {
						return $answer->get_question_id() === $question_id;
					}
				);
				if ( count( $filtered ) === 1 ) {
					$answer = array_shift( $filtered );
					$this->tables_based_repository->create( $tables_based_submission, $answer->get_id(), $question_id, $points, $feedback );
				}
			}
		}

		return $grade;
	}

	/**
	 * Get all grades for a quiz submission.
	 *
	 * @internal
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Grade[] An array of grades.
	 */
	public function get_all( int $submission_id ): array {
		return $this->comments_based_repository->get_all( $submission_id );
	}

	/**
	 * Save multiple grades.
	 *
	 * @internal
	 *
	 * @param Submission $submission The submission.
	 * @param Grade[]    $grades     An array of grades.
	 */
	public function save_many( Submission $submission, array $grades ): void {
		$this->comments_based_repository->save_many( $submission, $grades );

		if ( $this->use_tables ) {
			$grades_to_save          = [];
			$tables_based_submission = $this->tables_based_submission_repository->get( $submission->get_quiz_id(), $submission->get_user_id() );
			if ( $tables_based_submission ) {
				$tables_based_grades = $this->tables_based_repository->get_all( $tables_based_submission->get_id() );
				foreach ( $grades as $grade ) {
					$filtered = array_filter(
						$tables_based_grades,
						function( Grade $tables_based_grade ) use ( $grade ) {
							return $tables_based_grade->get_question_id() === $grade->get_question_id();
						}
					);
					if ( count( $filtered ) !== 1 ) {
						continue;
					}
					$tables_based_grade = array_shift( $filtered );

					$created_at = new DateTimeImmutable( '@' . $grade->get_created_at()->getTimestamp() );
					$updated_at = new DateTimeImmutable( '@' . $grade->get_updated_at()->getTimestamp() );

					$grades_to_save[] = new Grade(
						$tables_based_grade->get_id(),
						$tables_based_grade->get_answer_id(),
						$tables_based_grade->get_question_id(),
						$grade->get_points(),
						$grade->get_feedback(),
						$created_at,
						$updated_at
					);
				}

				$this->tables_based_repository->save_many( $tables_based_submission, $grades_to_save );
			}
		}
	}

	/**
	 * Delete all grades for a submission.
	 *
	 * @internal
	 *
	 * @param Submission $submission The submission.
	 */
	public function delete_all( Submission $submission ): void {
		$this->comments_based_repository->delete_all( $submission );

		if ( $this->use_tables ) {
			$tables_based_submission = $this->tables_based_submission_repository->get( $submission->get_quiz_id(), $submission->get_user_id() );
			if ( $tables_based_submission ) {
				$this->tables_based_repository->delete_all( $tables_based_submission );
			}
		}
	}
}

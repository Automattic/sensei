<?php
/**
 * File containing the class Aggregate_Grade_Repository.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Grade\Repositories;

use DateTimeImmutable;
use Sensei\Internal\Quiz_Submission\Answer\Models\Answer;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Comments_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Tables_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Grade\Models\Grade;
use Sensei\Internal\Quiz_Submission\Submission\Models\Submission;
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
	 * Comments based answer repository.
	 *
	 * @var Comments_Based_Answer_Repository
	 */
	private $comments_based_answer_repository;

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
	 * @param Comments_Based_Answer_Repository   $comments_based_answer_repository Comments based quiz answer repository implementation.
	 * @param bool                               $use_tables  The flag if the tables based implementation is available for use.
	 */
	public function __construct(
		Comments_Based_Grade_Repository $comments_based_repository,
		Tables_Based_Grade_Repository $tables_based_repository,
		Tables_Based_Submission_Repository $tables_based_submission_repository,
		Tables_Based_Answer_Repository $tables_based_answer_repository,
		Comments_Based_Answer_Repository $comments_based_answer_repository,
		bool $use_tables
	) {
		$this->comments_based_repository          = $comments_based_repository;
		$this->tables_based_repository            = $tables_based_repository;
		$this->tables_based_submission_repository = $tables_based_submission_repository;
		$this->tables_based_answer_repository     = $tables_based_answer_repository;
		$this->comments_based_answer_repository   = $comments_based_answer_repository;
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
			$tables_based_submission = $this->get_or_create_tables_based_submission( $submission );

			$answers = $this->get_or_create_tables_based_answers( $submission, $tables_based_submission );
			$answer  = $answers[ $question_id ] ?? null;

			if ( $answer ) {
				$this->tables_based_repository->create( $tables_based_submission, $answer->get_id(), $question_id, $points, $feedback );
			}
		}

		return $grade;
	}

	/**
	 * Get or create all answers for the table based submission.
	 *
	 * @param Submission $comments_based_submission The comments based submission.
	 * @param Submission $tables_based_submission   The tables based submission.
	 * @return Answer[] The answers.
	 */
	public function get_or_create_tables_based_answers( $comments_based_submission, $tables_based_submission ): array {
		$comments_based_answers = $this->comments_based_answer_repository->get_all( $comments_based_submission->get_id() );
		$tables_based_answers   = $this->tables_based_answer_repository->get_all( $tables_based_submission->get_id() );
		$result                 = array();
		foreach ( $comments_based_answers as $comments_based_answer ) {
			$filtered = array_filter(
				$tables_based_answers,
				function( Answer $answer ) use ( $comments_based_answer ) {
					return $answer->get_question_id() === $comments_based_answer->get_question_id();
				}
			);
			if ( count( $filtered ) === 1 ) {
				$answer                               = array_shift( $filtered );
				$result[ $answer->get_question_id() ] = $answer;
			} else {
				$result[ $comments_based_answer->get_question_id() ] = $this->tables_based_answer_repository->create(
					$tables_based_submission,
					$comments_based_answer->get_question_id(),
					$comments_based_answer->get_value()
				);
			}
		}

		return $result;
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
			$tables_based_submission = $this->get_or_create_tables_based_submission( $submission );
			$tables_based_grades     = $this->get_or_create_tables_based_grades_for_save(
				$submission,
				$tables_based_submission,
				$grades
			);

			foreach ( $grades as $grade ) {
				$tables_based_grade = $tables_based_grades[ $grade->get_id() ] ?? null;
				if ( null === $tables_based_grade ) {
					continue;
				}

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

	/**
	 * Get or create all grades for the table based submission.
	 *
	 * @param Submission $comments_based_submission The comments based submission.
	 * @param Submission $tables_based_submission   The tables based submission.
	 * @param Grade[]    $comments_based_grades     The comments based grades.
	 * @return Grade[] The tables based grades.
	 */
	private function get_or_create_tables_based_grades_for_save(
		Submission $comments_based_submission,
		Submission $tables_based_submission,
		array $comments_based_grades
	): array {
		$tables_based_answers = $this->get_or_create_tables_based_answers(
			$comments_based_submission,
			$tables_based_submission
		);
		$tables_based_grades  = $this->tables_based_repository->get_all( $tables_based_submission->get_id() );
		$result               = array();
		foreach ( $comments_based_grades as $comments_based_grade ) {
			$filtered = array_filter(
				$tables_based_grades,
				function( Grade $grade ) use ( $comments_based_grade ) {
					return $grade->get_question_id() === $comments_based_grade->get_question_id();
				}
			);
			if ( count( $filtered ) === 1 ) {
				$grade                      = array_shift( $filtered );
				$result[ $grade->get_id() ] = $grade;
			} else {
				$answer = $tables_based_answers[ $comments_based_grade->get_question_id() ] ?? null;
				if ( ! $answer ) {
					continue;
				}

				$result[ $comments_based_grade->get_id() ] = $this->tables_based_repository->create(
					$tables_based_submission,
					$answer->get_id(),
					$comments_based_grade->get_question_id(),
					$comments_based_grade->get_points(),
					$comments_based_grade->get_feedback()
				);
			}
		}

		return $result;
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
			$tables_based_submission = $this->get_or_create_tables_based_submission( $submission );
			$this->tables_based_repository->delete_all( $tables_based_submission );
		}
	}

	/**
	 * Get the tables based submission for a given submission or create if not exists.
	 *
	 * @param Submission $submission The submission.
	 *
	 * @return Submission The tables based submission.
	 */
	private function get_or_create_tables_based_submission( Submission $submission ): Submission {
		return $this->tables_based_submission_repository->get_or_create(
			$submission->get_quiz_id(),
			$submission->get_user_id(),
			$submission->get_final_grade()
		);
	}
}

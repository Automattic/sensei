<?php
/**
 * File containing the class Table_Reading_Aggregate_Grade_Repository.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Grade\Repositories;

use DateTimeImmutable;
use Sensei\Internal\Quiz_Submission\Answer\Models\Answer_Interface;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Comments_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Tables_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Grade\Models\Comments_Based_Grade;
use Sensei\Internal\Quiz_Submission\Grade\Models\Grade_Interface;
use Sensei\Internal\Quiz_Submission\Submission\Models\Submission_Interface;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Comments_Based_Submission_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Table_Reading_Aggregate_Grade_Repository
 *
 * @internal
 *
 * @since 4.19.0
 */
class Table_Reading_Aggregate_Grade_Repository implements Grade_Repository_Interface {
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
	 * @var Comments_Based_Submission_Repository
	 */
	private $comments_based_submission_repository;

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
	 * Table_Reading_Aggregate_Grade_Repository constructor.
	 *
	 * @internal
	 *
	 * @param Comments_Based_Grade_Repository      $comments_based_repository Comments based quiz answer repository implementation.
	 * @param Tables_Based_Grade_Repository        $tables_based_repository  Tables based quiz answer repository implementation.
	 * @param Comments_Based_Submission_Repository $comments_based_submission_repository Tables based quiz submission repository implementation.
	 * @param Tables_Based_Answer_Repository       $tables_based_answer_repository Tables based quiz answer repository implementation.
	 * @param Comments_Based_Answer_Repository     $comments_based_answer_repository Comments based quiz answer repository implementation.
	 */
	public function __construct(
		Comments_Based_Grade_Repository $comments_based_repository,
		Tables_Based_Grade_Repository $tables_based_repository,
		Comments_Based_Submission_Repository $comments_based_submission_repository,
		Tables_Based_Answer_Repository $tables_based_answer_repository,
		Comments_Based_Answer_Repository $comments_based_answer_repository
	) {
		$this->comments_based_repository            = $comments_based_repository;
		$this->tables_based_repository              = $tables_based_repository;
		$this->comments_based_submission_repository = $comments_based_submission_repository;
		$this->tables_based_answer_repository       = $tables_based_answer_repository;
		$this->comments_based_answer_repository     = $comments_based_answer_repository;
	}

	/**
	 * Creates a new grade.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission  The submission ID.
	 * @param Answer_Interface     $answer      The answer.
	 * @param int                  $question_id The question ID.
	 * @param int                  $points      The points.
	 * @param string|null          $feedback    The feedback.
	 *
	 * @return Grade_Interface The grade.
	 */
	public function create( Submission_Interface $submission, Answer_Interface $answer, int $question_id, int $points, ?string $feedback = null ): Grade_Interface {
		$grade = $this->tables_based_repository->create( $submission, $answer, $question_id, $points, $feedback );

		$comments_based_submission = $this->get_or_create_comments_based_submission( $submission );
		$comments_based_answers    = $this->get_or_create_comments_based_answers( $submission, $comments_based_submission );
		$comments_based_answer     = $comments_based_answers[ $question_id ] ?? null;

		if ( $comments_based_answer ) {
			$this->comments_based_repository->create( $comments_based_submission, $comments_based_answer, $question_id, $points, $feedback );
		}

		return $grade;
	}

	/**
	 * Get or create all answers for the table based submission.
	 *
	 * @param Submission_Interface $tables_based_submission The comments based submission.
	 * @param Submission_Interface $comments_based_submission   The tables based submission.
	 * @return Answer_Interface[] The answers.
	 */
	private function get_or_create_comments_based_answers( Submission_Interface $tables_based_submission, Submission_Interface $comments_based_submission ): array {
		$tables_based_answers   = $this->tables_based_answer_repository->get_all( $tables_based_submission->get_id() );
		$comments_based_answers = $this->comments_based_answer_repository->get_all( $comments_based_submission->get_id() );
		$result                 = array();
		foreach ( $tables_based_answers as $tables_based_answer ) {
			$filtered = array_filter(
				$comments_based_answers,
				function( Answer_Interface $answer ) use ( $tables_based_answer ) {
					return $answer->get_question_id() === $tables_based_answer->get_question_id();
				}
			);
			if ( count( $filtered ) === 1 ) {
				$answer                               = array_shift( $filtered );
				$result[ $answer->get_question_id() ] = $answer;
			} else {
				$result[ $tables_based_answer->get_question_id() ] = $this->comments_based_answer_repository->create(
					$comments_based_submission,
					$tables_based_answer->get_question_id(),
					$tables_based_answer->get_value()
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
	 * @return Grade_Interface[] An array of grades.
	 */
	public function get_all( int $submission_id ): array {
		return $this->tables_based_repository->get_all( $submission_id );
	}

	/**
	 * Save multiple grades.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission The submission.
	 * @param Grade_Interface[]    $grades     An array of grades.
	 */
	public function save_many( Submission_Interface $submission, array $grades ): void {
		$this->tables_based_repository->save_many( $submission, $grades );

			$comments_based_submission = $this->get_or_create_comments_based_submission( $submission );
			$comments_based_grades     = $this->get_or_create_comments_based_grades_for_save(
				$comments_based_submission,
				$submission,
				$grades
			);

			$grades_to_save = [];
		foreach ( $grades as $grade ) {
			$comments_based_grade = $comments_based_grades[ $grade->get_question_id() ] ?? null;
			if ( null === $comments_based_grade ) {
				continue;
			}

			$created_at = new DateTimeImmutable( '@' . $grade->get_created_at()->getTimestamp() );
			$updated_at = new DateTimeImmutable( '@' . $grade->get_updated_at()->getTimestamp() );

			$grades_to_save[] = new Comments_Based_Grade(
				$comments_based_grade->get_question_id(),
				$grade->get_points(),
				$grade->get_feedback(),
				$created_at,
				$updated_at
			);
		}

			$this->comments_based_repository->save_many( $comments_based_submission, $grades_to_save );
	}

	/**
	 * Get or create all grades for the table based submission.
	 *
	 * @param Submission_Interface $comments_based_submission The comments based submission.
	 * @param Submission_Interface $tables_based_submission   The tables based submission.
	 * @param Grade_Interface[]    $tables_based_grades       The tables-based grades.
	 * @return Grade_Interface[] The tables based grades.
	 */
	private function get_or_create_comments_based_grades_for_save(
		Submission_Interface $comments_based_submission,
		Submission_Interface $tables_based_submission,
		array $tables_based_grades
	): array {
		$comments_based_answers = $this->get_or_create_comments_based_answers(
			$tables_based_submission,
			$comments_based_submission
		);
		$commments_based_grades = $this->comments_based_repository->get_all( $comments_based_submission->get_id() );
		$result                 = array();
		foreach ( $tables_based_grades as $tables_based_grade ) {
			$filtered = array_filter(
				$commments_based_grades,
				function( Grade_Interface $grade ) use ( $tables_based_grade ) {
					return $grade->get_question_id() === $tables_based_grade->get_question_id();
				}
			);
			if ( count( $filtered ) === 1 ) {
				$grade                               = array_shift( $filtered );
				$result[ $grade->get_question_id() ] = $grade;
			} else {
				$answer = $comments_based_answers[ $tables_based_grade->get_question_id() ] ?? null;
				if ( ! $answer ) {
					continue;
				}

				$result[ $tables_based_grade->get_question_id() ] = $this->comments_based_repository->create(
					$comments_based_submission,
					$answer,
					$tables_based_grade->get_question_id(),
					$tables_based_grade->get_points(),
					$tables_based_grade->get_feedback()
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
	 * @param Submission_Interface $submission The submission.
	 */
	public function delete_all( Submission_Interface $submission ): void {
		$this->tables_based_repository->delete_all( $submission );

		$comments_based_submission = $this->get_or_create_comments_based_submission( $submission );
		$this->comments_based_repository->delete_all( $comments_based_submission );
	}

	/**
	 * Get the tables based submission for a given submission or create if not exists.
	 *
	 * @param Submission_Interface $submission The submission.
	 *
	 * @return Submission_Interface The comments based submission.
	 */
	private function get_or_create_comments_based_submission( Submission_Interface $submission ): Submission_Interface {
		return $this->comments_based_submission_repository->get_or_create(
			$submission->get_quiz_id(),
			$submission->get_user_id(),
			$submission->get_final_grade()
		);
	}
}

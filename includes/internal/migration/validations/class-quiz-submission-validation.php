<?php
/**
 * File containing the Quiz_Submission_Validation class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Migration\Validations;

use RuntimeException;
use Sensei\Internal\Migration\Migration_Job_Scheduler;
use Sensei\Internal\Quiz_Submission\Answer\Models\Answer_Interface;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Comments_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Tables_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Grade\Models\Grade_Interface;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Comments_Based_Grade_Repository;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Tables_Based_Grade_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Models\Submission_Interface;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Comments_Based_Submission_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Tables_Based_Submission_Repository;

/**
 * Class responsible for validation of the migrated quiz submission data.
 *
 * @since 4.19.2
 */
class Quiz_Submission_Validation {
	/**
	 * Errors.
	 *
	 * @var Validation_Error[]
	 */
	private array $errors = array();

	/**
	 * Run the validation.
	 *
	 * @internal
	 *
	 * @since 4.19.2
	 */
	public function run(): void {
		$this->errors = array();

		if ( ! $this->is_progress_migration_complete() ) {
			$this->add_error( 'The progress migration is not complete. Please run the progress migration first.' );
		}

		try {
			$this->validate_quiz_submissions();
		} catch ( RuntimeException $exception ) {
			$this->add_error( $exception->getMessage() );
		}
	}

	/**
	 * Check if there are validation errors.
	 *
	 * @internal
	 *
	 * @since 4.19.2
	 *
	 * @return bool
	 */
	public function has_errors(): bool {
		return (bool) $this->errors;
	}

	/**
	 * Get the validation errors.
	 *
	 * @internal
	 *
	 * @since 4.19.2
	 *
	 * @return Validation_Error[]
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Add a validation error.
	 *
	 * @param string $message Error message.
	 * @param array  $data    Error data.
	 */
	private function add_error( string $message, array $data = [] ): void {
		$this->errors[] = new Validation_Error( $message, $data );
	}

	/**
	 * Check if the progress migration is complete.
	 *
	 * @return bool
	 */
	private function is_progress_migration_complete(): bool {
		return (bool) get_option( Migration_Job_Scheduler::COMPLETED_OPTION_NAME, false );
	}

	/**
	 * Find comment-only submissions and validate each table submission once.
	 */
	private function validate_quiz_submissions(): void {
		global $wpdb;

		$comments_repository = new Comments_Based_Submission_Repository();
		$tables_repository   = new Tables_Based_Submission_Repository( $wpdb );
		$comments_answers    = new Comments_Based_Answer_Repository();
		$tables_answers      = new Tables_Based_Answer_Repository( $wpdb );
		$comments_grades     = new Comments_Based_Grade_Repository();
		$tables_grades       = new Tables_Based_Grade_Repository( $wpdb );

		foreach ( Validation_Records::get_comments( 'sensei_lesson_status' ) as $record ) {
			$quiz_id = Sensei()->lesson->get_quiz_id_for_lesson_progress( (int) $record->post_id );
			if ( ! $quiz_id ) {
				continue;
			}

			$user_id             = (int) $record->user_id;
			$comments_submission = $comments_repository->get( $quiz_id, $user_id );
			if ( ! $comments_submission || $comments_submission->get_id() !== (int) $record->id ) {
				continue;
			}

			if ( ! $tables_repository->get( $quiz_id, $user_id ) ) {
				$this->add_missing_submission_error( 'tables', $quiz_id, $user_id );
			}
		}

		foreach ( Validation_Records::get_submission_rows() as $record ) {
			$quiz_id             = (int) $record->post_id;
			$user_id             = (int) $record->user_id;
			$comments_submission = $comments_repository->get( $quiz_id, $user_id );
			if ( ! $comments_submission ) {
				$this->add_missing_submission_error( 'comments', $quiz_id, $user_id );
				continue;
			}

			$tables_submission = $tables_repository->get( $quiz_id, $user_id );
			if ( ! $tables_submission ) {
				continue;
			}

			$this->compare_submission( $comments_submission, $tables_submission );

			$identity = array(
				'quiz_id'                => $quiz_id,
				'user_id'                => $user_id,
				'comments_submission_id' => $comments_submission->get_id(),
				'tables_submission_id'   => $tables_submission->get_id(),
			);

			$this->compare_question_data(
				array_map( array( $this, 'get_answer_data' ), $comments_answers->get_all( $comments_submission->get_id() ) ),
				array_map( array( $this, 'get_answer_data' ), $tables_answers->get_all( $tables_submission->get_id() ) ),
				$identity,
				'answers'
			);
			$this->compare_question_data(
				array_map( array( $this, 'get_grade_data' ), $comments_grades->get_all( $comments_submission->get_id() ) ),
				array_map( array( $this, 'get_grade_data' ), $tables_grades->get_all( $tables_submission->get_id() ) ),
				$identity,
				'grades'
			);
		}
	}

	/**
	 * Record an identity with no submission in one store.
	 *
	 * @param string $store   The store missing the submission.
	 * @param int    $quiz_id The quiz ID.
	 * @param int    $user_id The student ID.
	 */
	private function add_missing_submission_error( string $store, int $quiz_id, int $user_id ): void {
		$this->add_error(
			sprintf( '%s-based quiz submission not found.', ucfirst( $store ) ),
			array(
				'quiz_id' => $quiz_id,
				'user_id' => $user_id,
				'source'  => $store,
			)
		);
	}

	/**
	 * Compare the comments and tables based submissions.
	 *
	 * @param Submission_Interface $comments_based_submission Comments based submission.
	 * @param Submission_Interface $tables_based_submission   Tables based submission.
	 */
	private function compare_submission( Submission_Interface $comments_based_submission, Submission_Interface $tables_based_submission ): void {
		$comments = $this->get_submission_data( $comments_based_submission );
		$tables   = $this->get_submission_data( $tables_based_submission );

		if ( $comments !== $tables ) {
			$this->add_error(
				'Data mismatch between comments and tables based submissions.',
				array(
					array_merge( array( 'source' => 'comments' ), $comments ),
					array_merge( array( 'source' => 'tables' ), $tables ),
				)
			);
		}
	}

	/**
	 * Compare answers or grades for each question in the two submissions.
	 *
	 * @param array  $comments The values from the comments store.
	 * @param array  $tables   The values from the tables store.
	 * @param array  $identity The quiz, student, and submission IDs.
	 * @param string $kind     The kind of question data, answers or grades.
	 */
	private function compare_question_data( array $comments, array $tables, array $identity, string $kind ): void {
		$comments_count = count( $comments );
		$tables_count   = count( $tables );
		if ( $comments_count !== $tables_count ) {
			$this->add_error(
				sprintf( 'Number of %s does not match.', 'grades' === $kind ? 'gradings' : $kind ),
				array_merge(
					$identity,
					array(
						'comments_count' => $comments_count,
						'tables_count'   => $tables_count,
					)
				)
			);
		}

		$comments = array_column( $comments, null, 'question_id' );
		$tables   = array_column( $tables, null, 'question_id' );
		foreach ( array_keys( $comments + $tables ) as $question_id ) {
			if ( ! isset( $comments[ $question_id ], $tables[ $question_id ] ) ) {
				$store = isset( $comments[ $question_id ] ) ? 'tables' : 'comments';
				$this->add_error(
					sprintf( '%s missing in %s based %s.', ucfirst( $kind ), $store, $kind ),
					array_merge(
						$identity,
						array(
							'question_id' => $question_id,
							'source'      => $store,
						)
					)
				);
				continue;
			}

			if ( $comments[ $question_id ] !== $tables[ $question_id ] ) {
				$this->add_error(
					sprintf( '%s mismatch.', ucfirst( $kind ) ),
					array(
						array_merge( $identity, array( 'source' => 'comments' ), $comments[ $question_id ] ),
						array_merge( $identity, array( 'source' => 'tables' ), $tables[ $question_id ] ),
					)
				);
			}
		}
	}

	/**
	 * Get the submission data.
	 *
	 * @param Submission_Interface $submission Submission.
	 *
	 * @return array
	 */
	private function get_submission_data( Submission_Interface $submission ): array {
		// The HPPS column stores two decimal places. A missing grade remains distinct from zero.
		$grade = $submission->get_final_grade();

		return array(
			'quiz_id'     => $submission->get_quiz_id(),
			'user_id'     => $submission->get_user_id(),
			'final_grade' => null === $grade ? null : round( $grade, 2 ),
		);
	}

	/**
	 * Get the answer data.
	 *
	 * @param Answer_Interface $answer Answer.
	 *
	 * @return array
	 */
	private function get_answer_data( Answer_Interface $answer ): array {
		return array(
			'question_id' => $answer->get_question_id(),
			'value'       => $answer->get_value(),
		);
	}

	/**
	 * Get the grade data.
	 *
	 * @param Grade_Interface $grade Grade.
	 *
	 * @return array
	 */
	private function get_grade_data( Grade_Interface $grade ): array {
		return array(
			'question_id' => $grade->get_question_id(),
			'points'      => $grade->get_points(),
			'feedback'    => $grade->get_feedback() ?? '',
		);
	}
}

<?php
/**
 * File containing the Quiz_Submission_Validation class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Migration\Validations;

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

		foreach ( $this->get_quiz_ids() as $quiz_id ) {
			$this->validate_quiz_submissions( $quiz_id );
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
	 * Get the quiz IDs.
	 *
	 * @psalm-suppress InvalidReturnType, InvalidReturnStatement -- Psalm doesn't understand the 'fields' argument.
	 *
	 * @return int[]
	 */
	private function get_quiz_ids(): array {
		return get_posts(
			array(
				'post_type'      => 'quiz',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
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
	 * Validate the quiz progress.
	 *
	 * @param int $quiz_id Quiz post ID.
	 */
	private function validate_quiz_submissions( int $quiz_id ): void {
		$lesson_id = Sensei()->quiz->get_lesson_id( $quiz_id );

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$user_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->comments}
				WHERE comment_type = 'sensei_lesson_status'
				AND comment_post_ID = %d",
				$lesson_id
			)
		);
		if ( ! $user_ids ) {
			return;
		}

		$comments_based_repository = new Comments_Based_Submission_Repository();
		$tables_based_repository   = new Tables_Based_Submission_Repository( $wpdb );

		foreach ( $user_ids as $user_id ) {
			$comments_based_submission = $comments_based_repository->get( $quiz_id, $user_id );
			$tables_based_submission   = $tables_based_repository->get( $quiz_id, $user_id );

			if ( ! $comments_based_submission && ! $tables_based_submission ) {
				continue;
			}

			if ( ! $comments_based_submission ) {
				$this->add_error(
					'Comments-based quiz submission not found.',
					array(
						'quiz_id' => $quiz_id,
						'user_id' => $user_id,
					)
				);
				continue;
			}

			if ( ! $tables_based_submission ) {
				$this->add_error(
					'Tables-based quiz submission not found.',
					array(
						'quiz_id' => $quiz_id,
						'user_id' => $user_id,
					)
				);
				continue;
			}

			$this->compare_submission( $comments_based_submission, $tables_based_submission );

			$this->compare_answers( $comments_based_submission, $tables_based_submission );
			$this->compare_gradings( $comments_based_submission, $tables_based_submission );
		}
	}

	/**
	 * Compare the comments and tables based submissions.
	 *
	 * @param Submission_Interface $comments_based_submission Comments based progress.
	 * @param Submission_Interface $tables_based_submission   Tables based progress.
	 */
	private function compare_submission( Submission_Interface $comments_based_submission, Submission_Interface $tables_based_submission ): void {
		// phpcs:ignore Universal.Operators.StrictComparisons.LooseComparison -- Intended.
		if ( $this->get_submission_data( $comments_based_submission ) != $this->get_submission_data( $tables_based_submission ) ) {
			$this->add_mismatch_submission_error( $comments_based_submission, $tables_based_submission );
		}
	}

	/**
	 * Compare the comments and tables based answers.
	 *
	 * @param Submission_Interface $comments_based_submission Comments based submission.
	 * @param Submission_Interface $tables_based_submission   Tables based submission.
	 */
	private function compare_answers( Submission_Interface $comments_based_submission, Submission_Interface $tables_based_submission ): void {
		global $wpdb;

		$comments_based_answer_repository = new Comments_Based_Answer_Repository();
		$tables_based_answer_repository   = new Tables_Based_Answer_Repository( $wpdb );

		$comments_based_answers = $comments_based_answer_repository->get_all( $comments_based_submission->get_id() );
		$tables_based_answers   = $tables_based_answer_repository->get_all( $tables_based_submission->get_id() );

		if ( count( $comments_based_answers ) !== count( $tables_based_answers ) ) {
			$this->add_error(
				'Number of answers does not match.',
				array(
					'comments-based submission_id' => $comments_based_submission->get_id(),
					'tables-based submission_id'   => $tables_based_submission->get_id(),
				)
			);
		}

		$comments_based_answers = array_reduce(
			$comments_based_answers,
			function( array $answers, Answer_Interface $answer ) {
				$answers[ $answer->get_question_id() ] = $this->get_answer_data( $answer );
				return $answers;
			},
			array()
		);

		$tables_based_answers = array_reduce(
			$tables_based_answers,
			function( array $answers, Answer_Interface $answer ) {
				$answers[ $answer->get_question_id() ] = $this->get_answer_data( $answer );
				return $answers;
			},
			array()
		);

		$comments_based_keys    = array_keys( $comments_based_answers );
		$tables_based_keys      = array_keys( $tables_based_answers );
		$missed_in_tables_based = array_diff( $comments_based_keys, $tables_based_keys );
		if ( count( $missed_in_tables_based ) ) {
			foreach ( $missed_in_tables_based as $question_id ) {
				$this->add_error(
					'Answers missing in tables based answers.',
					array(
						'tables_based_submission_id' => $tables_based_submission->get_id(),
						'comments_based_answer'      => $comments_based_answers[ $question_id ],
					)
				);
			}
		}

		$missed_in_comments_based = array_diff( $tables_based_keys, $comments_based_keys );
		if ( count( $missed_in_comments_based ) ) {
			foreach ( $missed_in_comments_based as $question_id ) {
				$this->add_error(
					'Answers missing in comments based answers.',
					array(
						'comments_based_submission_id' => $comments_based_submission->get_id(),
						'tables_based_answer'          => $tables_based_answers[ $question_id ],
					)
				);
			}
		}

		$common_keys = array_intersect( $comments_based_keys, $tables_based_keys );
		foreach ( $common_keys as $question_id ) {
			// phpcs:ignore Universal.Operators.StrictComparisons.LooseComparison -- Intended.
			if ( $comments_based_answers[ $question_id ]['value'] != $tables_based_answers[ $question_id ]['value'] ) {
				$this->add_error(
					'Answers mismatch.',
					array(
						'question_id' => $question_id,
						'comments'    => $comments_based_answers[ $question_id ],
						'tables'      => $tables_based_answers[ $question_id ],
					)
				);
			}
		}
	}

	/**
	 * Compare the comments and tables based gradings.
	 *
	 * @param Submission_Interface $comments_based_submission Comments based submission.
	 * @param Submission_Interface $tables_based_submission   Tables based submission.
	 */
	private function compare_gradings( Submission_Interface $comments_based_submission, Submission_Interface $tables_based_submission ): void {
		global $wpdb;

		$comments_based_grade_repository = new Comments_Based_Grade_Repository();
		$tables_based_grade_repository   = new Tables_Based_Grade_Repository( $wpdb );

		$comments_based_grades = $comments_based_grade_repository->get_all( $comments_based_submission->get_id() );
		$tables_based_grades   = $tables_based_grade_repository->get_all( $tables_based_submission->get_id() );

		if ( count( $comments_based_grades ) !== count( $tables_based_grades ) ) {
			$this->add_error(
				'Number of gradings does not match.',
				array(
					'comments-based submission_id' => $comments_based_submission->get_id(),
					'tables-based submission_id'   => $tables_based_submission->get_id(),
				)
			);
		}

		$comments_based_grades = array_reduce(
			$comments_based_grades,
			function( array $grades, Grade_Interface $grade ) {
				$grades[ $grade->get_question_id() ] = $this->get_grade_data( $grade );
				return $grades;
			},
			array()
		);

		$tables_based_grades = array_reduce(
			$tables_based_grades,
			function( array $grades, Grade_Interface $grade ) {
				$grades[ $grade->get_question_id() ] = $this->get_grade_data( $grade );
				return $grades;
			},
			array()
		);

		$comments_based_keys    = array_keys( $comments_based_grades );
		$tables_based_keys      = array_keys( $tables_based_grades );
		$missed_in_tables_based = array_diff( $comments_based_keys, $tables_based_keys );
		if ( count( $missed_in_tables_based ) ) {
			foreach ( $missed_in_tables_based as $question_id ) {
				$this->add_error(
					'Grades missing in tables based grades.',
					array(
						'tables_based_submission_id' => $tables_based_submission->get_id(),
						'comments_based_grade'       => $comments_based_grades[ $question_id ],
					)
				);
			}
		}

		$missed_in_comments_based = array_diff( $tables_based_keys, $comments_based_keys );
		if ( count( $missed_in_comments_based ) ) {
			foreach ( $missed_in_comments_based as $question_id ) {
				$this->add_error(
					'Grades missing in comments based grades.',
					array(
						'comments_based_submission_id' => $comments_based_submission->get_id(),
						'tables_based_grade'           => $tables_based_grades[ $question_id ],
					)
				);
			}
		}

		$common_keys = array_intersect( $comments_based_keys, $tables_based_keys );
		foreach ( $common_keys as $question_id ) {
			$comments_based_grade = array(
				'points'   => $comments_based_grades[ $question_id ]['points'],
				'feedback' => $comments_based_grades[ $question_id ]['feedback'],
			);
			$tables_based_grade   = array(
				'points'   => $tables_based_grades[ $question_id ]['points'],
				'feedback' => $tables_based_grades[ $question_id ]['feedback'],
			);
			// phpcs:ignore Universal.Operators.StrictComparisons.LooseComparison -- Intended.
			if ( $comments_based_grade != $tables_based_grade ) {
				$this->add_error(
					'Grades mismatch.',
					array(
						'question_id' => $question_id,
						'comments'    => $comments_based_grades[ $question_id ],
						'tables'      => $tables_based_grades[ $question_id ],
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
		return array(
			'quiz_id'     => $submission->get_quiz_id(),
			'user_id'     => $submission->get_user_id(),
			'final_grade' => $submission->get_final_grade(),
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
			'submission_id' => $answer->get_submission_id(),
			'question_id'   => $answer->get_question_id(),
			'value'         => $answer->get_value(),
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
			'feedback'    => $grade->get_feedback(),
		);
	}

	/**
	 * Log a progress mismatch.
	 *
	 * @param Submission_Interface $comments_based_submission Comments based progress.
	 * @param Submission_Interface $tables_based_submission   Tables based progress.
	 */
	private function add_mismatch_submission_error( Submission_Interface $comments_based_submission, Submission_Interface $tables_based_submission ): void {
		$this->add_error(
			'Data mismatch between comments and tables based submissions.',
			array(
				array_merge(
					array(
						'source' => 'comments',
					),
					$this->get_submission_data( $comments_based_submission )
				),
				array_merge(
					array(
						'source' => 'tables',
					),
					$this->get_submission_data( $tables_based_submission )
				),
			)
		);
	}
}

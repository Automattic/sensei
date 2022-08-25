<?php
/**
 * File containing the Answer_Comments_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Repositories;

use DateTime;
use Sensei\Quiz_Submission\Models\Answer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Answer_Comments_Repository.
 *
 * @since $$next-version$$
 */
class Answer_Comments_Repository implements Answer_Repository_Interface {
	/**
	 * The grade repository.
	 *
	 * @var Grade_Comments_Repository
	 */
	private $grade_repository;

	/**
	 * Constructor.
	 *
	 * @param Grade_Comments_Repository $grade_repository The grade repository.
	 */
	public function __construct( Grade_Comments_Repository $grade_repository ) {
		$this->grade_repository = $grade_repository;
	}

	/**
	 * Create a new answer.
	 *
	 * @param int    $submission_id The submission ID.
	 * @param int    $question_id   The question ID.
	 * @param string $value         The answer value.
	 *
	 * @return Answer The answer model.
	 */
	public function create( int $submission_id, int $question_id, string $value ): Answer {
		$comment_answers = $this->get_comment_answers( $submission_id );

		$comment_answers[ $question_id ] = $value;

		$this->update_comment_answers( $submission_id, $comment_answers );

		return new Answer( 0, $submission_id, $question_id, $value, new DateTime() );
	}

	/**
	 * Get a quiz submission.
	 *
	 * @param int $submission_id The submission ID.
	 * @param int $question_id   The question ID.
	 *
	 * @return Answer|null The answer model.
	 */
	public function get( int $submission_id, int $question_id ): ?Answer {
		$answers = get_comment_meta( $submission_id, 'quiz_answers', true );

		foreach ( $answers as $_question_id => $value ) {
			if ( $_question_id === $question_id ) {
				return new Answer( 0, $submission_id, $question_id, $value, new DateTime() );
			}
		}

		return null;
	}

	/**
	 * Get all answers for a quiz submission.
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Answer[] An array of answers.
	 */
	public function get_all( int $submission_id ): array {
		$answers = [];
		foreach ( $this->get_comment_answers( $submission_id ) as $question_id => $value ) {
			$answers[] = new Answer( 0, $submission_id, $question_id, $value, new DateTime() );
		}

		return $answers;
	}

	/**
	 * Get all answers and grades for a quiz submission.
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Answer[] An array of answers.
	 */
	public function get_all_answers_and_grades( int $submission_id ): array {
		$answers = $this->get_all( $submission_id );
		$grades  = $this->grade_repository->get_all( $submission_id );

		foreach ( $answers as $answer ) {
			foreach ( $grades as $grade ) {
				if ( $grade->get_question_id() === $answer->get_question_id() ) {
					$answer->set_grade( $grade );
				}
			}
		}

		return $answers;
	}

	/**
	 * Save the answer.
	 *
	 * @param Answer $answer The answer model.
	 */
	public function save( Answer $answer ): void {
		// TODO: Implement save() method.
	}

	/**
	 * Delete all answers, including their grades.
	 *
	 * @param int $submission_id The submission ID.
	 */
	public function delete_all_answers_and_grades( int $submission_id ): void {
		$this->grade_repository->delete_all( $submission_id );

		delete_comment_meta( $submission_id, 'quiz_answers' );
		delete_comment_meta( $submission_id, 'questions_asked' );
	}

	/**
	 * Get the answers stored in the comment meta.
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return array
	 */
	private function get_comment_answers( int $submission_id ): array {
		$comment_answers = get_comment_meta( $submission_id, 'quiz_answers', true );

		if ( ! $comment_answers ) {
			return [];
		}

		return $comment_answers;
	}

	/**
	 * Update the answers in the comment meta.
	 *
	 * @param int   $submission_id   The submission ID.
	 * @param array $comment_answers The comment answers.
	 *
	 * @return void
	 */
	private function update_comment_answers( int $submission_id, array $comment_answers ): void {
		update_comment_meta( $submission_id, 'quiz_answers', $comment_answers );

		$questions_asked_csv = implode( ',', array_keys( $comment_answers ) );
		update_comment_meta( $submission_id, 'questions_asked', $questions_asked_csv );
	}
}

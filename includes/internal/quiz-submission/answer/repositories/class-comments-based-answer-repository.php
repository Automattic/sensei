<?php
/**
 * File containing the Comments_Based_Answer_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Answer\Repositories;

use Sensei\Internal\Quiz_Submission\Answer\Models\Answer_Interface;
use Sensei\Internal\Quiz_Submission\Answer\Models\Comments_Based_Answer;
use Sensei\Internal\Quiz_Submission\Submission\Models\Submission_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comments_Based_Answer_Repository.
 *
 * @internal
 *
 * @since 4.7.2
 */
class Comments_Based_Answer_Repository implements Answer_Repository_Interface {
	/**
	 * Create a new answer.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission  The submission.
	 * @param int                  $question_id The question ID.
	 * @param string               $value       The answer value.
	 *
	 * @return Answer_Interface The answer model.
	 */
	public function create( Submission_Interface $submission, int $question_id, string $value ): Answer_Interface {
		/**
		 * Filters the submission ID when quiz answer is created.
		 *
		 * @hook sensei_quiz_answer_create_submission_id
		 *
		 * @since $$next-version$$
		 *
		 * @param {int} $submission_id The submission ID.
		 * @param {string} $context    The context.
		 * @return {int} The submission ID.
		 */
		$submission_id = (int) apply_filters( 'sensei_quiz_answer_create_submission_id', $submission->get_id(), 'comments' );

		/**
		 * Filters the question ID when quiz answer is created.
		 *
		 * @hook sensei_quiz_answer_create_question_id
		 *
		 * @since $$next-version$$
		 *
		 * @param {int} $question_id The question ID.
		 * @return {int} The question ID.
		 */
		$question_id = (int) apply_filters( 'sensei_quiz_answer_create_question_id', $question_id );

		$answers_map                 = $this->get_answers_map( $submission_id );
		$answers_map[ $question_id ] = $value;
		$questions_asked_csv         = implode( ',', array_keys( $answers_map ) );

		update_comment_meta( $submission_id, 'quiz_answers', $answers_map );
		update_comment_meta( $submission_id, 'questions_asked', $questions_asked_csv );

		$created_at = current_datetime();

		return new Comments_Based_Answer( $submission_id, $question_id, $value, $created_at, $created_at );
	}

	/**
	 * Get all answers for a quiz submission.
	 *
	 * @internal
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Answer_Interface[] An array of answers.
	 */
	public function get_all( int $submission_id ): array {
		/**
		 * Filters the submission ID when getting all quiz answers.
		 *
		 * @hook sensei_quiz_answer_get_all_submission_id
		 *
		 * @since $$next-version$$
		 *
		 * @param {int}    $submission_id The submission ID.
		 * @param {string} $context       The context.
		 * @return {int} The submission ID.
		 */
		$submission_id = (int) apply_filters( 'sensei_quiz_answer_get_all_submission_id', $submission_id, 'comments' );

		$answers    = [];
		$created_at = current_datetime();

		foreach ( $this->get_answers_map( $submission_id ) as $question_id => $value ) {
			$answers[] = new Comments_Based_Answer( $submission_id, $question_id, $value, $created_at, $created_at );
		}

		return $answers;
	}

	/**
	 * Delete all answers for a submission.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission The submission.
	 */
	public function delete_all( Submission_Interface $submission ): void {
		/**
		 * Filters the submission ID when deleting all quiz answers.
		 *
		 * @hook sensei_quiz_answer_delete_all_submission_id
		 *
		 * @since $$next-version$$
		 *
		 * @param {int}    $submission_id The submission ID.
		 * @param {string} $context       The context.
		 * @return {int} The submission ID.
		 */
		$submission_id = (int) apply_filters( 'sensei_quiz_answer_delete_all_submission_id', $submission->get_id(), 'comments' );

		delete_comment_meta( $submission_id, 'quiz_answers' );
		delete_comment_meta( $submission_id, 'questions_asked' );
	}

	/**
	 * Get the answers map stored in the comment meta.
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return array
	 */
	private function get_answers_map( int $submission_id ): array {
		$comment_answers = get_comment_meta( $submission_id, 'quiz_answers', true );

		if ( ! $comment_answers || ! is_array( $comment_answers ) ) {
			return [];
		}

		return $comment_answers;
	}
}

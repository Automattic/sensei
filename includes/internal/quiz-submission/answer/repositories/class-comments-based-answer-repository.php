<?php
/**
 * File containing the Comments_Based_Answer_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Answer\Repositories;

use Sensei\Internal\Quiz_Submission\Answer\Models\Answer;

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
	 * @param int    $submission_id The submission ID.
	 * @param int    $question_id   The question ID.
	 * @param string $value         The answer value.
	 *
	 * @return Answer The answer model.
	 */
	public function create( int $submission_id, int $question_id, string $value ): Answer {
		$answers_map                 = $this->get_answers_map( $submission_id );
		$answers_map[ $question_id ] = $value;
		$questions_asked_csv         = implode( ',', array_keys( $answers_map ) );

		update_comment_meta( $submission_id, 'quiz_answers', $answers_map );
		update_comment_meta( $submission_id, 'questions_asked', $questions_asked_csv );

		$created_at = current_datetime();

		return new Answer( 0, $submission_id, $question_id, $value, $created_at, $created_at );
	}

	/**
	 * Get all answers for a quiz submission.
	 *
	 * @internal
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Answer[] An array of answers.
	 */
	public function get_all( int $submission_id ): array {
		$answers    = [];
		$created_at = current_datetime();

		foreach ( $this->get_answers_map( $submission_id ) as $question_id => $value ) {
			$answers[] = new Answer( 0, $submission_id, $question_id, $value, $created_at, $created_at );
		}

		return $answers;
	}

	/**
	 * Delete all answers for a submission.
	 *
	 * @internal
	 *
	 * @param int $submission_id The submission ID.
	 */
	public function delete_all( int $submission_id ): void {
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

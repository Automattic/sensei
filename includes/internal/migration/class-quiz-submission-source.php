<?php
/**
 * Historical quiz submission eligibility and diagnostics.
 *
 * @package sensei
 */

namespace Sensei\Internal\Migration;

use Sensei\Internal\Migration\Validations\Validation_Error;
use Sensei\Internal\Migration\Validations\Validation_Records;

/**
 * Inspect the representable fields without deciding whether historical grades are valid.
 *
 * @internal
 * @since $$next-version$$
 */
class Quiz_Submission_Source {
	/**
	 * Read the existing submission contract and report information that cannot be copied.
	 *
	 * @since $$next-version$$
	 *
	 * @param int        $quiz_id The quiz ID.
	 * @param int        $student_id The student ID.
	 * @param array|null $records Shared persisted rows for this identity.
	 * @return array Submission, representable question data, eligibility, and unresolved errors.
	 */
	public static function inspect( int $quiz_id, int $student_id, ?array &$records ): array {
		$records    = $records ?? array();
		$submission = Validation_Records::get_comments_submission( $quiz_id, $student_id, $records );
		$comments   = $records['comments'] ?? array();
		$comment_id = (int) ( $comments[0]->comment_ID ?? 0 );
		// Other comments remain inspectable but cannot contribute quiz history.
		$metadata  = array_filter(
			$records['comment_meta'] ?? array(),
			static function ( object $row ) use ( $comment_id ): bool {
				return (int) $row->comment_id === $comment_id;
			}
		);
		$keys      = array_column( $metadata, 'meta_key' );
		$errors    = array();
		$result    = array(
			'submission'  => $submission,
			'has_history' => false,
			'can_copy'    => false,
			'answers'     => array(),
			'grades'      => array(),
			'feedback'    => array(),
			'errors'      => array(),
		);
		$identity  = array(
			'quiz_id'     => $quiz_id,
			'user_id'     => $student_id,
			'comment_ids' => array_map( 'intval', array_column( $comments, 'comment_ID' ) ),
			'source'      => 'comments',
		);
		$add_error = static function ( string $message, array $data = array() ) use ( &$errors, $identity ): void {
			$errors[] = new Validation_Error( $message, array_merge( $identity, $data ) );
		};

		$has_answer_history   = (bool) array_intersect( $keys, array( 'quiz_answers', 'quiz_grades', 'quiz_answers_feedback' ) );
		$has_question_history = $has_answer_history || in_array( 'questions_asked', $keys, true );
		if ( ! $comments || ( ! $has_question_history && ! in_array( 'grade', $keys, true ) ) ) {
			return $result;
		}

		$final_grade         = Validation_Records::get_metadata( 'comment', $comment_id, 'grade', $records );
		$has_final_grade     = is_numeric( $final_grade ) || 'NAN' === $final_grade;
		$questions_asked     = Validation_Records::get_metadata( 'comment', $comment_id, 'questions_asked', $records );
		$is_completion_grade = 'passed' === $comments[0]->comment_approved
			&& is_numeric( $final_grade ) && 0.0 === (float) $final_grade;
		// Marking a course complete can leave a placeholder zero without a quiz attempt.
		if ( ! $has_question_history && $is_completion_grade ) {
			$result['submission'] = null;
			return $result;
		}

		// Older resets left empty question and grade entries after clearing the attempt.
		if ( 'in-progress' === $comments[0]->comment_approved
			&& in_array( 'questions_asked', $keys, true ) && in_array( 'grade', $keys, true )
			&& '' === $final_grade
			&& '' === $questions_asked && ! $has_answer_history
		) {
			return $result;
		}

		// Older quiz views saved question selections before any answers were saved.
		// Existing HPPS submissions still need comparison against the source history.
		if ( ! $has_answer_history && is_string( $questions_asked )
			&& 1 === preg_match( '/^[1-9][0-9]*(?:,[1-9][0-9]*)*$/D', $questions_asked )
			&& ( '' === $final_grade || $is_completion_grade )
			&& ! in_array( $comments[0]->comment_approved, array( 'graded', 'ungraded', 'failed' ), true )
			&& ! Validation_Records::get_tables_submission( $quiz_id, $student_id, $records )
			&& empty( $records['submissions'] )
		) {
			$result['submission'] = null;
			return $result;
		}

		$result['has_history'] = true;
		$answers               = Validation_Records::get_metadata( 'comment', $comment_id, 'quiz_answers', $records );
		$grades                = Validation_Records::get_metadata( 'comment', $comment_id, 'quiz_grades', $records );
		$feedback              = Validation_Records::get_metadata( 'comment', $comment_id, 'quiz_answers_feedback', $records );
		$has_no_answers        = '' === $answers || array() === $answers;
		$valid_answers         = is_array( $answers ) && $answers;
		foreach ( is_array( $answers ) ? $answers : array() as $answer ) {
			if ( ! is_scalar( $answer ) ) {
				$valid_answers = false;
			}
		}

		if ( ! $valid_answers && ! ( $has_final_grade && $has_no_answers ) ) {
			$add_error( 'Unsupported historical data: saved answers are missing or malformed. This cannot be repaired automatically.' );
		} elseif ( $valid_answers && ! $questions_asked ) {
			$add_error( 'Unsupported historical data: saved question IDs are missing. This cannot be repaired automatically.' );
		}

		if ( '' !== $final_grade && 'NAN' !== $final_grade && ! is_numeric( $final_grade ) ) {
			$add_error( 'Unsupported historical data: the overall quiz grade is invalid. This cannot be repaired automatically.', array( 'stored_grade' => $final_grade ) );
		}

		$result['answers']  = $valid_answers ? $answers : array();
		$result['feedback'] = is_array( $feedback ) ? $feedback : array();
		if ( $grades && ! is_array( $grades ) ) {
			$add_error( 'Unsupported historical data: question grades are malformed. This cannot be repaired automatically.' );
		}
		if ( $feedback && ! is_array( $feedback ) ) {
			$add_error( 'Unsupported historical data: question feedback is malformed. This cannot be repaired automatically.' );
		}

		foreach ( is_array( $grades ) ? $grades : array() as $question_id => $points ) {
			// Older auto-grading stored incorrect answers as false instead of zero points.
			$points = false === $points ? 0 : $points;
			if ( ! is_numeric( $points ) ) {
				$add_error(
					'Unsupported historical data: question points are invalid. This cannot be repaired automatically.',
					array(
						'question_id'   => $question_id,
						'stored_points' => $points,
					)
				);
				continue;
			}

			$result['grades'][ $question_id ] = $points;
			if ( ! array_key_exists( $question_id, $result['answers'] ) ) {
				$add_error( 'Unsupported historical data: a question grade has no saved answer. This cannot be repaired automatically.', array( 'question_id' => $question_id ) );
			}
		}

		foreach ( $result['feedback'] as $question_id => $value ) {
			if ( null !== $value && ! is_scalar( $value ) ) {
				$add_error( 'Unsupported historical data: question feedback is malformed. This cannot be repaired automatically.', array( 'question_id' => $question_id ) );
				unset( $result['feedback'][ $question_id ] );
				continue;
			}

			if ( '' === $value || null === $value ) {
				continue;
			}

			if ( ! array_key_exists( $question_id, $result['answers'] ) || ! array_key_exists( $question_id, $result['grades'] ) ) {
				$add_error( 'Unsupported historical data: question feedback requires numeric points and a saved answer. This cannot be repaired automatically.', array( 'question_id' => $question_id ) );
			}
		}

		$result['can_copy'] = null !== $submission
			&& ( ( $valid_answers && $questions_asked ) || ( $has_final_grade && $has_no_answers ) );
		$result['errors']   = $errors;
		return $result;
	}
}

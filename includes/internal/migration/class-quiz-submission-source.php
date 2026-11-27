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

		$quiz_keys = array( 'questions_asked', 'quiz_answers', 'grade', 'quiz_grades', 'quiz_answers_feedback' );
		if ( ! $comments || ! array_intersect( $keys, $quiz_keys ) ) {
			return $result;
		}

		$result['has_history'] = true;
		$answers               = Validation_Records::get_metadata( 'comment', $comment_id, 'quiz_answers', $records );
		$grades                = Validation_Records::get_metadata( 'comment', $comment_id, 'quiz_grades', $records );
		$feedback              = Validation_Records::get_metadata( 'comment', $comment_id, 'quiz_answers_feedback', $records );
		$final_grade           = Validation_Records::get_metadata( 'comment', $comment_id, 'grade', $records );
		$valid_answers         = is_array( $answers ) && $answers;
		foreach ( is_array( $answers ) ? $answers : array() as $answer ) {
			if ( ! is_scalar( $answer ) ) {
				$valid_answers = false;
			}
		}

		if ( ! $valid_answers ) {
			$add_error( 'Comments-based quiz submission is incomplete: saved answers are missing or malformed.' );
		} elseif ( ! $submission ) {
			$add_error( 'Comments-based quiz submission is incomplete: questions asked are missing.' );
		}

		if ( '' !== $final_grade && 'NAN' !== $final_grade && ! is_numeric( $final_grade ) ) {
			$add_error( 'Comments-based quiz submission has an invalid final grade.', array( 'stored_grade' => $final_grade ) );
		}

		$result['answers']  = $valid_answers ? $answers : array();
		$result['feedback'] = is_array( $feedback ) ? $feedback : array();
		if ( $grades && ! is_array( $grades ) ) {
			$add_error( 'Comments-based question grades are malformed.' );
		}
		if ( $feedback && ! is_array( $feedback ) ) {
			$add_error( 'Comments-based question feedback is malformed.' );
		}

		foreach ( is_array( $grades ) ? $grades : array() as $question_id => $points ) {
			// Older auto-grading stored incorrect answers as false instead of zero points.
			$points = false === $points ? 0 : $points;
			if ( ! is_numeric( $points ) ) {
				$add_error(
					'Comments-based question grade has invalid points.',
					array(
						'question_id'   => $question_id,
						'stored_points' => $points,
					)
				);
				continue;
			}

			$result['grades'][ $question_id ] = $points;
			if ( ! array_key_exists( $question_id, $result['answers'] ) ) {
				$add_error( 'Comments-based question grade cannot be copied without its saved answer.', array( 'question_id' => $question_id ) );
			}
		}

		foreach ( $result['feedback'] as $question_id => $value ) {
			if ( null !== $value && ! is_scalar( $value ) ) {
				$add_error( 'Comments-based question feedback is malformed.', array( 'question_id' => $question_id ) );
				unset( $result['feedback'][ $question_id ] );
				continue;
			}

			if ( ! array_key_exists( $question_id, $result['answers'] ) || ! array_key_exists( $question_id, $result['grades'] ) ) {
				$add_error( 'Comments-based question feedback cannot be copied without numeric points and a saved answer.', array( 'question_id' => $question_id ) );
			}
		}

		$result['can_copy'] = null !== $submission && $valid_answers;
		$result['errors']   = $errors;
		return $result;
	}
}

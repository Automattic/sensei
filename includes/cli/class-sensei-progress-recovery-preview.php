<?php
/**
 * Read-only, targeted progress recovery previews.
 *
 * @package sensei
 */

use Sensei\Internal\Migration\Validations\Validation_Error;
use Sensei\Internal\Migration\Validations\Quiz_Submission_Validation;
use Sensei\Internal\Migration\Validations\Validation_Records;
use Sensei\Internal\Services\Progress_Inspection;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Inspect both copies without choosing an authoritative store or writing data.
 *
 * @internal
 * @since $$next-version$$
 */
class Sensei_Progress_Recovery_Preview {
	/**
	 * Inspect a student and one course, lesson, or quiz.
	 *
	 * @since $$next-version$$
	 *
	 * @param string      $type       The content type.
	 * @param int         $content_id The stored content ID.
	 * @param int         $student_id The stored student ID.
	 * @param string|null $from       An optional hypothetical source store.
	 * @return array The read-only preview.
	 * @throws InvalidArgumentException When the scope or source is invalid.
	 */
	public function inspect( string $type, int $content_id, int $student_id, ?string $from = null ): array {
		if ( ! in_array( $type, array( 'course', 'lesson', 'quiz' ), true ) || $content_id < 1 || $student_id < 1 ) {
			throw new InvalidArgumentException( 'Provide course, lesson, or quiz and positive content and student IDs.' );
		}

		if ( null !== $from && ! in_array( $from, array( 'comments', 'tables' ), true ) ) {
			throw new InvalidArgumentException( 'The hypothetical source must be comments or tables.' );
		}

		$scopes = array( array( $type, $content_id ) );
		if ( 'lesson' === $type ) {
			$quiz_id = Validation_Records::get_quiz_id( $content_id );
			if ( $quiz_id ) {
				$scopes[] = array( 'quiz', $quiz_id );
			}
		}

		$records = array();
		foreach ( $scopes as list( $scope_type, $post_id ) ) {
			$records[] = $this->inspect_record( $scope_type, $post_id, $student_id, $from );
		}

		return array(
			'read_only'           => true,
			'student_id'          => $student_id,
			'scope'               => array(
				'type'       => $type,
				'content_id' => $content_id,
			),
			'hypothetical_source' => $from,
			'requires_review'     => 'Neither the selected read store nor --from establishes the intended result. No repair is executable.',
			'records'             => $records,
		);
	}

	/**
	 * Inspect progress and, for quizzes, the related submission bundle.
	 *
	 * @param string      $type       The progress type.
	 * @param int         $post_id    The stored content ID.
	 * @param int         $student_id The student ID.
	 * @param string|null $from       The hypothetical source.
	 * @return array
	 */
	private function inspect_record( string $type, int $post_id, int $student_id, ?string $from ): array {
		$inspection = Progress_Inspection::inspect( $type, $post_id, $student_id );
		$progress   = $inspection['progress'];
		$submission = $inspection['submission'];
		$rows       = $inspection['rows'];
		$result     = array(
			'type'       => $type,
			'content_id' => $post_id,
			'stored'     => $rows,
		);

		if ( Validation_Records::has_duplicates( $rows ) ) {
			$result['status'] = 'ambiguous';
			$result['review'] = 'Duplicate records or metadata need review; no copy has been selected and no changes are proposed.';
			return $result;
		}

		$result['progress'] = $this->describe_difference( $progress, $from, array( 'status', 'started_at', 'completed_at' ) );
		foreach ( $inspection['invalid_dates'] as $field ) {
			$result['progress']['warnings'][] = 'Invalid ' . $field . ' is preserved for review.';
		}

		if ( $submission ) {
			$result['submission'] = $this->describe_difference( $submission, $from, array( 'final_grade', 'answers', 'grades' ) );
		}

		return $result;
	}

	/**
	 * Describe conditional operations using only fields the validators compare.
	 *
	 * @param array       $inspection The validator inspection.
	 * @param string|null $from       The hypothetical source.
	 * @param string[]    $fields     Fields eligible for comparison.
	 * @return array
	 */
	private function describe_difference( array $inspection, ?string $from, array $fields ): array {
		$comments = $inspection['comments'];
		$tables   = $inspection['tables'];
		$errors   = $inspection['errors'];
		$warnings = $inspection['warnings'] ?? array();
		$result   = array(
			'comments' => $comments,
			'tables'   => $tables,
			'status'   => $errors ? 'different' : 'equivalent',
			'warnings' => array_map(
				static function ( Validation_Error $warning ): string {
					return $warning->get_message();
				},
				$warnings
			),
			'changes'  => array(),
		);

		if ( ! $comments && ! $tables ) {
			$result['status'] = 'absent';
			return $result;
		}

		if ( ! $errors ) {
			return $result;
		}

		if ( ! $comments || ! $tables ) {
			$result['status'] = 'ambiguous_missing';
			$result['review'] = 'A missing copy may mean a failed copy or an intended deletion. Confirm whether to copy the surviving bundle or remove it; neither is established by this preview.';
		} else {
			$result['review'] = 'Confirm the intended values from external evidence before replacing either copy.';
		}

		$withheld_fields = array();
		if ( $comments && $tables ) {
			foreach ( array_intersect( $fields, array( 'answers', 'grades' ) ) as $kind ) {
				$questions = Quiz_Submission_Validation::question_changes( $comments[ $kind ], $tables[ $kind ] );
				foreach ( array(
					'tables'   => $questions['missing'],
					'comments' => $questions['extra'],
				) as $missing_from => $children ) {
					foreach ( $children as $question_id => $child ) {
						$result['missing_children'][] = array(
							'kind'         => $kind,
							'question_id'  => $question_id,
							'missing_from' => $missing_from,
						);
						if ( $from === $missing_from ) {
							$withheld_fields[] = $kind;
						}
					}
				}
			}
		}

		if ( ! empty( $result['missing_children'] ) ) {
			$result['status']       = 'ambiguous_missing';
			$result['review']       = 'Missing answers or grades may mean a failed copy or an intended deletion. Confirm the intended outcome for each child; --from does not establish it.';
			$result['alternatives'] = array(
				'Copy a surviving child only after confirming that it should exist in both stores.',
				'Remove a surviving child only after confirming an intended deletion; no removal is proposed.',
			);
		}

		// The preview describes whole answer and grade lists. Withhold a list
		// replacement if it would remove an item that exists only in the target.
		if ( $withheld_fields ) {
			$result['withheld_fields'] = array_values( array_unique( $withheld_fields ) );
			$result['review']         .= ' Collection replacements that would remove a surviving child are withheld.';
		}

		// --from asks what copying from that store would look like. It does not
		// confirm that the source is correct or that a missing item was deleted.
		if ( null === $from ) {
			$result['alternatives'] = $result['alternatives'] ?? array(
				'Use comments values only after confirming them.',
				'Use tables values only after confirming them.',
			);
			return $result;
		}

		$to     = 'comments' === $from ? 'tables' : 'comments';
		$source = $inspection[ $from ];
		$target = $inspection[ $to ];
		if ( ! $source ) {
			$result['review'] .= ' The chosen source is absent; no deletion is proposed.';
			return $result;
		}

		foreach ( Progress_Inspection::field_changes( $inspection, $from, array_diff( $fields, $withheld_fields ) ) as $field => $change ) {
			$result['changes'][] = array_merge(
				array(
					'if_confirmed' => $target ? 'replace' : 'copy',
					'from'         => $from,
					'to'           => $to,
					'field'        => $field,
				),
				$change
			);
		}

		return $result;
	}
}

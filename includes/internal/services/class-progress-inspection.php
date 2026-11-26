<?php
/**
 * Shared progress checks for previews and repair planning.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

use DateTimeImmutable;
use DateTimeZone;
use Sensei\Internal\Migration\Validations\Progress_Validation;
use Sensei\Internal\Migration\Validations\Quiz_Submission_Validation;
use Sensei\Internal\Migration\Validations\Validation_Records;

/**
 * Compare the two stored copies without choosing which one to trust.
 *
 * @internal
 * @since $$next-version$$
 */
class Progress_Inspection {

	/**
	 * Read progress and quiz data using the existing validation readers.
	 *
	 * @since $$next-version$$
	 * @param string            $type The course, lesson or quiz type.
	 * @param int               $post_id The stored content ID.
	 * @param int               $student_id The student ID.
	 * @param DateTimeZone|null $timezone Saved site timezone, or null to use the current WordPress option.
	 * @return array Comparison results, original rows and dates that need review.
	 */
	public static function inspect( string $type, int $post_id, int $student_id, ?DateTimeZone $timezone = null ): array {
		$stored  = Validation_Records::get_progress_records( $type, $post_id, $student_id );
		$records = $stored;
		$dates   = array();

		// Keep bad dates in the original rows so they can still be shown for review.
		// Hide them only from the models used for comparison, so other fields
		// can still be checked. Cloning keeps the original row objects unchanged.
		foreach ( $records['comment_meta'] ?? array() as $index => $meta ) {
			if ( 'start' === $meta->meta_key && $meta->meta_value && ! self::is_date( $meta->meta_value ) ) {
				$dates[]                                       = 'started_at';
				$records['comment_meta'][ $index ]             = clone $meta;
				$records['comment_meta'][ $index ]->meta_value = null;
			}
		}
		foreach ( $records['tables'] as $index => $row ) {
			if ( $row->started_at && ! self::is_date( $row->started_at ) ) {
				$dates[]                                 = 'started_at';
				$records['tables'][ $index ]             = clone $row;
				$records['tables'][ $index ]->started_at = null;
			}
		}

		$progress                   = ( new Progress_Validation() )->inspect( $type, $post_id, $student_id, $records, $timezone );
		$progress['ignored_fields'] = array_unique( array_merge( $progress['ignored_fields'], $dates ) );
		$submission                 = 'quiz' === $type ? ( new Quiz_Submission_Validation() )->inspect( $post_id, $student_id, $records, $timezone ) : null;
		$groups                     = 'quiz' === $type
			? array( 'comments', 'tables', 'submissions', 'comment_meta', 'answers', 'grades' )
			: array( 'comments', 'tables', 'comment_meta' );

		// Keep quiz rows fetched by the validators, but use the original progress
		// rows so the report still shows any bad dates exactly as stored.
		return array(
			'progress'      => $progress,
			'submission'    => $submission,
			'rows'          => array_replace( array_fill_keys( $groups, array() ), $records ?? array(), $stored ),
			'invalid_dates' => array_values( array_unique( $dates ) ),
		);
	}

	/**
	 * Find field changes, leaving out fields the validator cannot safely compare.
	 *
	 * @since $$next-version$$
	 * @param array    $inspection The values and differences returned by the validator.
	 * @param string   $source The store chosen as the source, including for a preview.
	 * @param string[] $fields The fields to compare.
	 * @return array Each changed field with its old and proposed values.
	 */
	public static function field_changes( array $inspection, string $source, array $fields ): array {
		$from    = $inspection[ $source ];
		$to      = $inspection[ 'comments' === $source ? 'tables' : 'comments' ];
		$changes = array();
		if ( ! $from ) {
			return $changes;
		}
		foreach ( array_diff( $fields, $inspection['ignored_fields'] ?? array() ) as $field ) {
			if ( ! $to || $from[ $field ] !== $to[ $field ] ) {
				$changes[ $field ] = array(
					'before' => $to[ $field ] ?? null,
					'after'  => $from[ $field ],
				);
			}
		}
		return $changes;
	}

	/**
	 * Check that a stored date has the exact expected format.
	 *
	 * @param string $value The stored value.
	 * @return bool Whether the whole value matches the expected date and time format.
	 */
	private static function is_date( string $value ): bool {
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $value );
		return $date && $date->format( 'Y-m-d H:i:s' ) === $value;
	}
}

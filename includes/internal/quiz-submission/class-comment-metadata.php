<?php
/**
 * Checked comment metadata operations for quiz persistence.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission;

use Sensei\Internal\Persistence_Exception;
use Sensei\Internal\Quiz_Submission\Submission\Models\Submission_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Distinguishes unchanged quiz metadata from failed writes.
 *
 * WordPress returns false for both an unchanged metadata value and a failed
 * update. Answer, grade, and submission repositories use this check so a
 * repeat save succeeds while a failed required write stops the operation.
 *
 * @internal
 * @since $$next-version$$
 */
class Comment_Metadata {
	/**
	 * Read metadata before changing a quiz record.
	 *
	 * @since $$next-version$$
	 *
	 * @param int                  $comment_id Comment ID.
	 * @param string               $key Metadata key.
	 * @param Submission_Interface $submission Submission identity.
	 * @return mixed Metadata value.
	 * @throws Persistence_Exception If the metadata cannot be read.
	 */
	public static function get( int $comment_id, string $key, Submission_Interface $submission ) {
		global $wpdb;

		$wpdb->last_error = '';

		$value = get_comment_meta( $comment_id, $key, true );

		if ( '' !== $wpdb->last_error ) {
			wp_cache_delete( $comment_id, 'comment_meta' );
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Safe persistence diagnostics.
			throw new Persistence_Exception(
				'quiz_submission',
				'comments',
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception context contains numeric IDs.
				$submission->get_quiz_id(),
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception context contains numeric IDs.
				$submission->get_user_id(),
				'metadata_read_failed',
				'read'
			);
		}

		return $value;
	}

	/**
	 * Save metadata only when the requested value differs from the stored value.
	 *
	 * @since $$next-version$$
	 *
	 * @param int                  $comment_id Comment ID.
	 * @param string               $key Metadata key.
	 * @param mixed                $value Metadata value.
	 * @param Submission_Interface $submission Submission identity.
	 * @return void
	 * @throws Persistence_Exception If the metadata cannot be saved.
	 */
	public static function update( int $comment_id, string $key, $value, Submission_Interface $submission ): void {
		$stored_value     = self::get( $comment_id, $key, $submission );
		$normalized_value = sanitize_meta( $key, wp_unslash( $value ), 'comment', get_object_subtype( 'comment', $comment_id ) );

		// Compare the value WordPress would store, but do not treat a missing key as unchanged.
		// update_comment_meta() returns false for both unchanged values and failed writes.
		if ( metadata_exists( 'comment', $comment_id, $key )
			&& (string) maybe_serialize( $stored_value ) === (string) maybe_serialize( $normalized_value ) ) {
			return;
		}

		if ( false === update_comment_meta( $comment_id, $key, $value ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Safe persistence diagnostics.
			throw new Persistence_Exception(
				'quiz_submission',
				'comments',
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception context contains numeric IDs.
				$submission->get_quiz_id(),
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception context contains numeric IDs.
				$submission->get_user_id(),
				'metadata_write_failed',
				'save'
			);
		}
	}

	/**
	 * Clear a final grade, accepting metadata that is already absent.
	 *
	 * @since $$next-version$$
	 *
	 * @param int                  $comment_id Comment ID.
	 * @param Submission_Interface $submission Submission identity.
	 * @return void
	 * @throws Persistence_Exception If the final grade cannot be cleared.
	 */
	public static function clear_grade( int $comment_id, Submission_Interface $submission ): void {
		// Check the read before treating an absent key as an already cleared grade.
		self::get( $comment_id, 'grade', $submission );
		if ( ! metadata_exists( 'comment', $comment_id, 'grade' ) ) {
			return;
		}

		if ( false === delete_comment_meta( $comment_id, 'grade' ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Safe persistence diagnostics.
			throw new Persistence_Exception(
				'quiz_submission',
				'comments',
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception context contains numeric IDs.
				$submission->get_quiz_id(),
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception context contains numeric IDs.
				$submission->get_user_id(),
				'metadata_delete_failed',
				'save'
			);
		}
	}

	/**
	 * Delete required quiz metadata, accepting a key that is already absent.
	 *
	 * @since $$next-version$$
	 *
	 * @param int                  $comment_id Comment ID.
	 * @param string               $key Metadata key.
	 * @param Submission_Interface $submission Submission identity.
	 * @param string               $entity Quiz data type for diagnostics.
	 * @return void
	 * @throws Persistence_Exception If the metadata cannot be deleted.
	 */
	public static function delete( int $comment_id, string $key, Submission_Interface $submission, string $entity ): void {
		self::get( $comment_id, $key, $submission );
		if ( ! metadata_exists( 'comment', $comment_id, $key ) ) {
			return;
		}

		if ( false === delete_comment_meta( $comment_id, $key ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages contain safe diagnostics and are not rendered.
			throw new Persistence_Exception( $entity, 'comments', $submission->get_quiz_id(), $submission->get_user_id(), 'metadata_delete_failed', 'delete' );
		}
	}
}

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
		$wpdb             = $GLOBALS['wpdb'];
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
	 * Save metadata, accepting a value that is already stored.
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
		$stored = self::get( $comment_id, $key, $submission );
		if ( metadata_exists( 'comment', $comment_id, $key )
			&& (string) maybe_serialize( $stored ) === (string) maybe_serialize( wp_unslash( $value ) ) ) {
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
}

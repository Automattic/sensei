<?php
/**
 * File containing the Comment_Progress class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interprets comment statuses and completion dates across progress readers.
 *
 * @internal
 * @since $$next-version$$
 */
final class Comment_Progress {
	/**
	 * Resolve a comment's status, including its saved pre-trash status.
	 *
	 * @since $$next-version$$
	 * @param string $type         Course, lesson, or quiz progress.
	 * @param string $status       Stored comment status.
	 * @param mixed  $saved_status Saved status from before trashing, if available.
	 * @return string|null The status, or null when the original trashed status is unknown.
	 */
	public static function resolve_status( string $type, string $status, $saved_status = null ): ?string {
		if ( 'post-trashed' !== $status ) {
			return $status;
		}

		$allowed = 'course' === $type
			? array( 'complete', 'in-progress' )
			: array( 'complete', 'in-progress', 'passed', 'graded', 'failed', 'ungraded' );

		return in_array( $saved_status, $allowed, true ) ? $saved_status : null;
	}

	/**
	 * Get the completion date represented by a comment's resolved status.
	 *
	 * @since $$next-version$$
	 * @param string $type             Course, lesson, or quiz progress.
	 * @param string $status           Resolved comment status.
	 * @param string $comment_date_gmt Stored UTC comment date.
	 * @param bool   $pass_required    Whether the lesson requires a quiz pass to complete.
	 * @return string|null The stored UTC completion date, or null for incomplete progress.
	 */
	public static function get_completed_at( string $type, string $status, string $comment_date_gmt, bool $pass_required = true ): ?string {
		return self::is_complete( $type, $status, $pass_required ) ? $comment_date_gmt : null;
	}

	/**
	 * Check whether a resolved comment status represents completed progress.
	 *
	 * @since $$next-version$$
	 * @param string      $type          Course, lesson, or quiz progress.
	 * @param string|null $status        Resolved comment status.
	 * @param bool        $pass_required Whether the lesson requires a quiz pass to complete.
	 * @return bool
	 */
	public static function is_complete( string $type, ?string $status, bool $pass_required = true ): bool {
		if ( 'course' === $type ) {
			return 'complete' === $status;
		}

		if ( 'lesson' === $type && 'failed' === $status ) {
			return ! $pass_required;
		}

		return in_array( $status, array( 'complete', 'passed', 'graded' ), true );
	}
}

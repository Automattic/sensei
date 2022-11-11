<?php
/**
 * File containing the Comments_Based_Quiz_Progress_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Quiz_Progress\Repositories;

use DateTime;
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress;
use Sensei_Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comments_Based_Quiz_Progress_Repository.
 *
 * @internal
 *
 * @since 4.7.2
 */
class Comments_Based_Quiz_Progress_Repository implements Quiz_Progress_Repository_Interface {

	/**
	 * Create a new quiz progress.
	 *
	 * @internal
	 *
	 * @param int $quiz_id Quiz identifier.
	 * @param int $user_id User identifier.
	 * @return Quiz_Progress
	 * @throws \RuntimeException When the quiz progress doesn't exist. In this implementation we re-use lesson progress.
	 */
	public function create( int $quiz_id, int $user_id ): Quiz_Progress {
		$progress = $this->get( $quiz_id, $user_id );
		if ( ! $progress ) {
			/**
			 * In comments-based implementation we don't have a separate quiz progress.
			 * It depends on the lesson progress. If it doesn't exist yet, throw an exception.
			 */
			throw new \RuntimeException( 'Cannot create quiz progress' );
		}

		return $progress;
	}

	/**
	 * Find a quiz progress by quiz and user identifiers.
	 *
	 * @internal
	 *
	 * @param int $quiz_id Quiz identifier.
	 * @param int $user_id User identifier.
	 * @return Quiz_Progress
	 */
	public function get( int $quiz_id, int $user_id ): ?Quiz_Progress {
		$lesson_id = Sensei()->quiz->get_lesson_id( $quiz_id );
		if ( ! $lesson_id ) {
			return null;
		}

		$activity_args = [
			'post_id' => $lesson_id,
			'user_id' => $user_id,
			'type'    => 'sensei_lesson_status',
		];
		$comment       = Sensei_Utils::sensei_check_for_activity( $activity_args, true );
		if ( ! $comment ) {
			return null;
		}

		$supported_statuses = [
			Quiz_Progress::STATUS_IN_PROGRESS,
			Quiz_Progress::STATUS_FAILED,
			Quiz_Progress::STATUS_GRADED,
			Quiz_Progress::STATUS_PASSED,
			Quiz_Progress::STATUS_UNGRADED,
		];

		$comment_date = new DateTime( $comment->comment_date, wp_timezone() );
		$meta_start   = get_comment_meta( $comment->comment_ID, 'start', true );
		$started_at   = ! empty( $meta_start ) ? new DateTime( $meta_start, wp_timezone() ) : current_datetime();
		$status       = in_array( $comment->comment_approved, $supported_statuses, true )
			? $comment->comment_approved
			: Quiz_Progress::STATUS_IN_PROGRESS;

		if ( in_array( $comment->comment_approved, [ 'complete', 'passed', 'graded' ], true ) ) {
			$completed_at = $comment_date;
		} else {
			$completed_at = null;
		}

		return new Quiz_Progress( (int) $comment->comment_ID, $quiz_id, $user_id, $status, $started_at, $completed_at, $comment_date, $comment_date );
	}

	/**
	 * Check if a quiz progress exists.
	 *
	 * @internal
	 *
	 * @param int $quiz_id Quiz identifier.
	 * @param int $user_id User identifier.
	 * @return bool
	 */
	public function has( int $quiz_id, int $user_id ): bool {
		$lesson_id     = Sensei()->quiz->get_lesson_id( $quiz_id );
		$activity_args = [
			'post_id' => $lesson_id,
			'user_id' => $user_id,
			'type'    => 'sensei_lesson_status',
		];
		$count         = Sensei_Utils::sensei_check_for_activity( $activity_args );
		return $count > 0;

	}

	/**
	 * Save the quiz progress.
	 *
	 * @internal
	 *
	 * @param Quiz_Progress $quiz_progress Quiz progress.
	 */
	public function save( Quiz_Progress $quiz_progress ): void {
		$lesson_id = Sensei()->quiz->get_lesson_id( $quiz_progress->get_quiz_id() );
		$metadata  = [];
		if ( $quiz_progress->get_started_at() ) {
			$metadata['start'] = $quiz_progress->get_started_at()->format( 'Y-m-d H:i:s' );
		}
		Sensei_Utils::update_lesson_status( $quiz_progress->get_user_id(), $lesson_id, $quiz_progress->get_status(), $metadata );
	}

	/**
	 * Delete the quiz progress.
	 *
	 * @internal
	 *
	 * @param Quiz_Progress $quiz_progress Quiz progress.
	 */
	public function delete( Quiz_Progress $quiz_progress ): void {
		delete_comment_meta( $quiz_progress->get_quiz_id(), 'quiz_answers' );
		delete_comment_meta( $quiz_progress->get_id(), 'grade' );

		// Backward compatibility with Sensei prior to 1.7.
		$lesson_id = Sensei()->quiz->get_lesson_id( $quiz_progress->get_quiz_id() );
		Sensei_Utils::sensei_delete_quiz_grade( $lesson_id, $quiz_progress->get_user_id() );
		Sensei_Utils::sensei_delete_quiz_answers( $lesson_id, $quiz_progress->get_user_id() );
	}
}

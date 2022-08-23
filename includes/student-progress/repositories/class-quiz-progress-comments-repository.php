<?php
/**
 * File containing the Sensei_Quiz_Progress_Comments_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Student_Progress\Repositories;

use DateTime;
use Sensei\Student_Progress\Models\Quiz_Progress;
use Sensei_Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Quiz_Progress_Comments_Repository.
 *
 * @since $$next-version$$
 */
class Quiz_Progress_Comments_Repository implements Quiz_Progress_Repository_Interface {

	/**
	 * Create a new quiz progress.
	 *
	 * @param int $quiz_id Quiz identifier.
	 * @param int $user_id User identifier.
	 * @return Quiz_Progress
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
	 * @param int $quiz_id Quiz identifier.
	 * @param int $user_id User identifier.
	 * @return Quiz_Progress
	 */
	public function get( int $quiz_id, int $user_id ): ?Quiz_Progress {
		$lesson_id     = Sensei()->quiz->get_lesson_id( $quiz_id );
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

		$created_at = new DateTime( $comment->comment_date );
		$meta_start = get_comment_meta( $comment->ID, 'start', true );
		$started_at = ! empty( $meta_start ) ? new DateTime( $meta_start ) : new DateTime();
		$status     = in_array( $comment->comment_approved, $supported_statuses, true )
			? $comment->comment_approved
			: Quiz_Progress::STATUS_IN_PROGRESS;

		return new Quiz_Progress( (int) $comment->comment_ID, $quiz_id, $user_id, $status, $started_at, null, $created_at, $created_at );
	}

	/**
	 * Check if a quiz progress exists.
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
}

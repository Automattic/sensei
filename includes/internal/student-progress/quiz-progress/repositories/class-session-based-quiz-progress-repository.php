<?php
/**
 * File containing the Session_Based_Quiz_Progress_Repository class.
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
 * Class Session_Based_Quiz_Progress_Repository.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Session_Based_Quiz_Progress_Repository implements Quiz_Progress_Repository_Interface {

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

		$now = current_datetime();

		return new Quiz_Progress( 0, $quiz_id, $user_id, Quiz_Progress::STATUS_IN_PROGRESS, $now, $now, $now, $now );
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
		return false;

	}

	/**
	 * Save the quiz progress.
	 *
	 * @internal
	 *
	 * @param Quiz_Progress $quiz_progress Quiz progress.
	 */
	public function save( Quiz_Progress $quiz_progress ): void {

	}

	/**
	 * Delete the quiz progress.
	 *
	 * @internal
	 *
	 * @param Quiz_Progress $quiz_progress Quiz progress.
	 */
	public function delete( Quiz_Progress $quiz_progress ): void {

	}
}

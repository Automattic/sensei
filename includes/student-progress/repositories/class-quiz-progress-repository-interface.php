<?php
/**
 * File containing the Sensei_Quiz_Progress_Repository_Interface interface.
 *
 * @package sensei
 */

namespace Sensei\StudentProgress\Repositories;

use Sensei\StudentProgress\Models\Quiz_Progress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Sensei_Quiz_Progress_Repository_Interface.
 *
 * @since $$next-version$$
 */
interface Quiz_Progress_Repository_Interface {
	/**
	 * Create a new quiz progress.
	 *
	 * @param int $quiz_id Quiz identifier.
	 * @param int $user_id User identifier.
	 * @return Quiz_Progress
	 */
	public function create( int $quiz_id, int $user_id ): Quiz_Progress;

	/**
	 * Find a quiz progress by quiz and user identifiers.
	 *
	 * @param int $quiz_id Quiz identifier.
	 * @param int $user_id User identifier.
	 * @return Quiz_Progress
	 */
	public function get( int $quiz_id, int $user_id ): ?Quiz_Progress;

	/**
	 * Check if a quiz progress exists.
	 *
	 * @param int $quiz_id Quiz identifier.
	 * @param int $user_id User identifier.
	 * @return bool
	 */
	public function has( int $quiz_id, int $user_id ): bool;

	/**
	 * Save the quiz progress.
	 *
	 * @param Quiz_Progress $quiz_progress Quiz progress.
	 */
	public function save( Quiz_Progress $quiz_progress ): void;
}

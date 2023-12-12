<?php
/**
 * File containing the Quiz_Progress_Repository_Interface interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Quiz_Progress\Repositories;

use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Quiz_Progress_Repository_Interface.
 *
 * @internal
 *
 * @since 4.7.2
 */
interface Quiz_Progress_Repository_Interface {
	/**
	 * Create a new quiz progress.
	 *
	 * @internal
	 *
	 * @param int $quiz_id Quiz identifier.
	 * @param int $user_id User identifier.
	 * @return Quiz_Progress_Interface
	 */
	public function create( int $quiz_id, int $user_id ): Quiz_Progress_Interface;

	/**
	 * Find a quiz progress by quiz and user identifiers.
	 *
	 * @internal
	 *
	 * @param int $quiz_id Quiz identifier.
	 * @param int $user_id User identifier.
	 * @return Quiz_Progress_Interface|null
	 */
	public function get( int $quiz_id, int $user_id ): ?Quiz_Progress_Interface;

	/**
	 * Check if a quiz progress exists.
	 *
	 * @internal
	 *
	 * @param int $quiz_id Quiz identifier.
	 * @param int $user_id User identifier.
	 * @return bool
	 */
	public function has( int $quiz_id, int $user_id ): bool;

	/**
	 * Save the quiz progress.
	 *
	 * @internal
	 *
	 * @param Quiz_Progress_Interface $quiz_progress Quiz progress.
	 */
	public function save( Quiz_Progress_Interface $quiz_progress ): void;

	/**
	 * Delete the quiz progress.
	 *
	 * @internal
	 *
	 * @param Quiz_Progress_Interface $quiz_progress The quiz progress.
	 */
	public function delete( Quiz_Progress_Interface $quiz_progress ): void;

	/**
	 * Delete all quiz progress for a quiz.
	 *
	 * @internal
	 *
	 * @param int $quiz_id Quiz identifier.
	 */
	public function delete_for_quiz( int $quiz_id ): void;

	/**
	 * Delete all quiz progress for a user.
	 *
	 * @internal
	 *
	 * @param int $user_id User identifier.
	 */
	public function delete_for_user( int $user_id ): void;

	/**
	 * Find course progress.
	 *
	 * @internal
	 *
	 * @param array $args The arguments.
	 * @return Quiz_Progress_Interface[] The course progress.
	 */
	public function find( array $args ): array;
}

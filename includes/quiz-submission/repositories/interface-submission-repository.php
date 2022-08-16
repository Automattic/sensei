<?php
/**
 * File containing the Submission_Repository_Interface.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Repositories;

use Sensei\Quiz_Submission\Models\Submission;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Submission_Repository_Interface.
 *
 * @since $$next-version$$
 */
interface Submission_Repository_Interface {
	/**
	 * Creates a new quiz submission.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Submission The quiz submission.
	 */
	public function create( int $quiz_id, int $user_id ): Submission;

	/**
	 * Get or create a new quiz submission if it doesn't exist.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Submission The quiz submission.
	 */
	public function get_or_create( int $quiz_id, int $user_id ): Submission;

	/**
	 * Gets a quiz submission.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Submission|null The quiz submission.
	 */
	public function get( int $quiz_id, int $user_id ): ?Submission;

	/**
	 * Checks if a quiz submission exists.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return bool Whether the quiz submission exists.
	 */
	public function has( int $quiz_id, int $user_id ): bool;

	/**
	 * Save quiz submission.
	 *
	 * @param Submission $submission The quiz submission.
	 */
	public function save( Submission $submission ): void;
}

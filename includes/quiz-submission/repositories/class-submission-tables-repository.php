<?php
/**
 * File containing the Submission_Tables_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Repositories;

use Sensei\Quiz_Submission\Models\Submission;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Submission_Tables_Repository.
 *
 * @since $$next-version$$
 */
class Submission_Tables_Repository implements Submission_Repository_Interface {
	/**
	 * Creates a new quiz submission.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Submission The quiz submission.
	 */
	public function create( int $quiz_id, int $user_id ): Submission {
		// TODO: Implement create() method.
	}

	/**
	 * Get or create a new quiz submission if it doesn't exist.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Submission The course progress.
	 */
	public function get_or_create( int $quiz_id, int $user_id ): Submission {
		// TODO: Implement create() method.
	}

	/**
	 * Gets a quiz submission.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Submission|null The quiz submission.
	 */
	public function get( int $quiz_id, int $user_id ): ?Submission {
		// TODO: Implement get() method.
	}

	/**
	 * Checks if a quiz submission exists.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return bool Whether the quiz submission exists.
	 */
	public function has( int $quiz_id, int $user_id ): bool {
		// TODO: Implement has() method.
	}

	/**
	 * Save quiz submission.
	 *
	 * @param Submission $submission The quiz submission.
	 */
	public function save( Submission $submission ): void {
		// TODO: Implement save() method.
	}
}

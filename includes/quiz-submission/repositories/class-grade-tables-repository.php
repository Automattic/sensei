<?php
/**
 * File containing the Grade_Tables_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Repositories;

use Sensei\Quiz_Submission\Models\Grade;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Grade_Tables_Repository.
 *
 * @since $$next-version$$
 */
class Grade_Tables_Repository implements Grade_Repository_Interface {
	/**
	 * Get all grades for a quiz submission.
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Grade[] An array of grades.
	 */
	public function get_all( int $submission_id ): array {
		// TODO: Implement get_all() method.

		return [];
	}

	/**
	 * Delete all grades for a submission.
	 *
	 * @param int $submission_id The submission ID.
	 */
	public function delete_all( int $submission_id ): void {
		// TODO: Implement delete_all() method.
	}
}

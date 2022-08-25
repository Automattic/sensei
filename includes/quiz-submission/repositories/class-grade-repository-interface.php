<?php
/**
 * File containing the Grade_Repository_Interface.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Repositories;

use Sensei\Quiz_Submission\Models\Grade_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Grade_Repository_Interface.
 *
 * @since $$next-version$$
 */
interface Grade_Repository_Interface {
	/**
	 * Get all grades for a quiz submission.
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Grade_Interface[] An array of grades.
	 */
	public function get_all( int $submission_id ): array;

	/**
	 * Delete all grades for a submission.
	 *
	 * @param int $submission_id The submission ID.
	 */
	public function delete_all( int $submission_id ): void;
}

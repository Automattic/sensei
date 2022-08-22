<?php
/**
 * File containing the Grade_Repository_Interface.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Repositories;

use Sensei\Quiz_Submission\Models\Grade;

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
	 * Create a new grade.
	 *
	 * @param int         $answer_id The answer ID.
	 * @param int         $points    The points.
	 * @param string|null $feedback  The feedback.
	 *
	 * @return Grade The grade model.
	 */
	public function create( int $answer_id, int $points, string $feedback = null ): Grade;

	/**
	 * Get a grade.
	 *
	 * @param int $answer_id The answer ID.
	 *
	 * @return Grade|null The grade model.
	 */
	public function get( int $answer_id ): ?Grade;

	/**
	 * Get all grades for a quiz submission.
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Grade[] An array of grades.
	 */
	public function get_all( int $submission_id ): array;

	/**
	 * Delete all grades for a submission.
	 *
	 * @param int $submission_id The submission ID.
	 */
	public function delete_all( int $submission_id ): void;
}

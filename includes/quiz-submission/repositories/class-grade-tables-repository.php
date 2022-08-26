<?php
/**
 * File containing the Grade_Tables_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Repositories;

use Sensei\Quiz_Submission\Models\Grade_Interface;
use Sensei\Quiz_Submission\Models\Tables_Based_Grade;

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
	 * Creates a new grade.
	 *
	 * @param int         $submission_id The submission ID.
	 * @param int         $answer_id     The answer ID.
	 * @param int         $question_id   The question ID.
	 * @param int         $points        The points.
	 * @param string|null $feedback      The feedback.
	 *
	 * @return Tables_Based_Grade The grade.
	 */
	public function create( int $submission_id, int $answer_id, int $question_id, int $points, string $feedback = null ): Grade_Interface {
		// TODO: Implement create() method.

		return new Tables_Based_Grade();
	}

	/**
	 * Get all grades for a quiz submission.
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Tables_Based_Grade[] An array of grades.
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

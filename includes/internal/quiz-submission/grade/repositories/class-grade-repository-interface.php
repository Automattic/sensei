<?php
/**
 * File containing the Grade_Repository_Interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Grade\Repositories;

use Sensei\Internal\Quiz_Submission\Grade\Models\Grade;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Grade_Repository_Interface.
 *
 * @internal
 *
 * @since 4.7.2
 */
interface Grade_Repository_Interface {
	/**
	 * Creates a new grade.
	 *
	 * @internal
	 *
	 * @param int         $submission_id The submission ID.
	 * @param int         $answer_id     The answer ID.
	 * @param int         $question_id   The question ID.
	 * @param int         $points        The points.
	 * @param string|null $feedback      The feedback.
	 *
	 * @return Grade The grade.
	 */
	public function create( int $submission_id, int $answer_id, int $question_id, int $points, string $feedback = null ): Grade;

	/**
	 * Get all grades for a quiz submission.
	 *
	 * @internal
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Grade[] An array of grades.
	 */
	public function get_all( int $submission_id ): array;

	/**
	 * Save multiple grades.
	 *
	 * @internal
	 *
	 * @param int     $submission_id The submission ID.
	 * @param Grade[] $grades        An array of grades.
	 */
	public function save_many( int $submission_id, array $grades ): void;

	/**
	 * Delete all grades for a submission.
	 *
	 * @internal
	 *
	 * @param int $submission_id The submission ID.
	 */
	public function delete_all( int $submission_id ): void;
}

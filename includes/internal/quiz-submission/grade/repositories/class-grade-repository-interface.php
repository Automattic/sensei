<?php
/**
 * File containing the Grade_Repository_Interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Grade\Repositories;

use Sensei\Internal\Quiz_Submission\Answer\Models\Answer_Interface;
use Sensei\Internal\Quiz_Submission\Grade\Models\Grade_Interface;
use Sensei\Internal\Quiz_Submission\Submission\Models\Submission_Interface;

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
	 * @param Submission_Interface $submission  The submission.
	 * @param Answer_Interface     $answer      The answer.
	 * @param int                  $question_id The question ID.
	 * @param int                  $points      The points.
	 * @param string|null          $feedback    The feedback.
	 *
	 * @return Grade_Interface The grade.
	 */
	public function create( Submission_Interface $submission, Answer_Interface $answer, int $question_id, int $points, string $feedback = null ): Grade_Interface;

	/**
	 * Get all grades for a quiz submission.
	 *
	 * @internal
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Grade_Interface[] An array of grades.
	 */
	public function get_all( int $submission_id ): array;

	/**
	 * Save multiple grades.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission The submission.
	 * @param Grade_Interface[]    $grades     An array of grades.
	 */
	public function save_many( Submission_Interface $submission, array $grades ): void;

	/**
	 * Delete all grades for a submission.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission The submission.
	 */
	public function delete_all( Submission_Interface $submission ): void;
}

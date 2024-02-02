<?php
/**
 * File containing the Answer_Repository_Interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Answer\Repositories;

use Sensei\Internal\Quiz_Submission\Answer\Models\Answer_Interface;
use Sensei\Internal\Quiz_Submission\Submission\Models\Submission_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Answer_Repository_Interface.
 *
 * @internal
 *
 * @since 4.7.2
 */
interface Answer_Repository_Interface {
	/**
	 * Create a new answer.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission  The submission.
	 * @param int                  $question_id The question ID.
	 * @param string               $value       The answer value.
	 *
	 * @return Answer_Interface The answer model.
	 */
	public function create( Submission_Interface $submission, int $question_id, string $value ): Answer_Interface;

	/**
	 * Get all answers for a quiz submission.
	 *
	 * @internal
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Answer_Interface[] An array of answers.
	 */
	public function get_all( int $submission_id ): array;

	/**
	 * Delete all answers for a submission.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission The submission.
	 */
	public function delete_all( Submission_Interface $submission ): void;
}

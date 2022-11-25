<?php
/**
 * File containing the Answer_Repository_Interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Answer\Repositories;

use Sensei\Internal\Quiz_Submission\Answer\Models\Answer;

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
	 * @param int    $submission_id The submission ID.
	 * @param int    $question_id   The question ID.
	 * @param string $value         The answer value.
	 *
	 * @return Answer The answer model.
	 */
	public function create( int $submission_id, int $question_id, string $value ): Answer;

	/**
	 * Get all answers for a quiz submission.
	 *
	 * @internal
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Answer[] An array of answers.
	 */
	public function get_all( int $submission_id ): array;

	/**
	 * Delete all answers for a submission.
	 *
	 * @internal
	 *
	 * @param int $submission_id The submission ID.
	 */
	public function delete_all( int $submission_id ): void;
}

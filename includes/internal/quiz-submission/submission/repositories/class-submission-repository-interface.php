<?php
/**
 * File containing the Submission_Repository_Interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Submission\Repositories;

use Sensei\Internal\Quiz_Submission\Submission\Models\Submission_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Submission_Repository_Interface.
 *
 * @internal
 *
 * @since 4.7.2
 */
interface Submission_Repository_Interface {
	/**
	 * Creates a new quiz submission.
	 *
	 * @internal
	 *
	 * @param int        $quiz_id     The quiz ID.
	 * @param int        $user_id     The user ID.
	 * @param float|null $final_grade The final grade.
	 *
	 * @return Submission_Interface The quiz submission.
	 */
	public function create( int $quiz_id, int $user_id, float $final_grade = null ): Submission_Interface;

	/**
	 * Get or create a new quiz submission if it doesn't exist.
	 *
	 * @internal
	 *
	 * @param int        $quiz_id     The quiz ID.
	 * @param int        $user_id     The user ID.
	 * @param float|null $final_grade The final grade.
	 *
	 * @return Submission_Interface The quiz submission.
	 */
	public function get_or_create( int $quiz_id, int $user_id, float $final_grade = null ): Submission_Interface;

	/**
	 * Gets a quiz submission.
	 *
	 * @internal
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Submission_Interface|null The quiz submission.
	 */
	public function get( int $quiz_id, int $user_id ): ?Submission_Interface;

	/**
	 * Get the questions related to the quiz submission.
	 *
	 * @internal
	 *
	 * @param int $submission_id The quiz submission ID.
	 *
	 * @return array An array of question post IDs.
	 */
	public function get_question_ids( int $submission_id ): array;

	/**
	 * Save quiz submission.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission The quiz submission.
	 */
	public function save( Submission_Interface $submission ): void;

	/**
	 * Delete the quiz submission.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission The quiz submission.
	 */
	public function delete( Submission_Interface $submission ): void;
}

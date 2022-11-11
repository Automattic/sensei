<?php
/**
 * File containing the Submission_Repository_Interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Submission\Repositories;

use Sensei\Internal\Quiz_Submission\Submission\Models\Submission;

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
	 * @return Submission The quiz submission.
	 */
	public function create( int $quiz_id, int $user_id, float $final_grade = null ): Submission;

	/**
	 * Get or create a new quiz submission if it doesn't exist.
	 *
	 * @internal
	 *
	 * @param int        $quiz_id     The quiz ID.
	 * @param int        $user_id     The user ID.
	 * @param float|null $final_grade The final grade.
	 *
	 * @return Submission The quiz submission.
	 */
	public function get_or_create( int $quiz_id, int $user_id, float $final_grade = null ): Submission;

	/**
	 * Gets a quiz submission.
	 *
	 * @internal
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Submission|null The quiz submission.
	 */
	public function get( int $quiz_id, int $user_id ): ?Submission;

	/**
	 * Get the question IDs related to this quiz submission.
	 *
	 * @internal
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return array An array of question post IDs.
	 */
	public function get_question_ids( int $quiz_id, int $user_id ): array;

	/**
	 * Save quiz submission.
	 *
	 * @internal
	 *
	 * @param Submission $submission The quiz submission.
	 */
	public function save( Submission $submission ): void;
}

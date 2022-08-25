<?php
/**
 * File containing the Answer_Tables_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Repositories;

use Sensei\Quiz_Submission\Models\Answer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Answer_Tables_Repository.
 *
 * @since $$next-version$$
 */
class Answer_Tables_Repository implements Answer_Repository_Interface {
	/**
	 * Create a new answer.
	 *
	 * @param int    $submission_id The submission ID.
	 * @param int    $question_id   The question ID.
	 * @param string $value         The answer string.
	 *
	 * @return Answer The answer model.
	 */
	public function create( int $submission_id, int $question_id, string $value ): Answer {
		// TODO: Implement create() method.

		return new Answer();
	}

	/**
	 * Get a quiz submission.
	 *
	 * @param int $submission_id The submission ID.
	 * @param int $question_id   The question ID.
	 *
	 * @return Answer|null The answer model.
	 */
	public function get( int $submission_id, int $question_id ): ?Answer {
		// TODO: Implement get() method.

		return new Answer();
	}

	/**
	 * Get all answers for a quiz submission.
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Answer[] An array of answers.
	 */
	public function get_all( int $submission_id ): array {
		// TODO: Implement get_all() method.

		return [];
	}

	/**
	 * Get all answers and grades for a quiz submission.
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Answer[] An array of answers.
	 */
	public function get_all_answers_and_grades( int $submission_id ): array {
		return [];
	}

	/**
	 * Save the answer.
	 *
	 * @param Answer $answer The answer model.
	 */
	public function save( Answer $answer ): void {
		// TODO: Implement save() method.
	}

	/**
	 * Delete all answers, including their grades.
	 *
	 * @param int $submission_id The submission ID.
	 */
	public function delete_all_answers_and_grades( int $submission_id ): void {
		// TODO: Implement delete_all_answers_and_grades() method.
	}
}

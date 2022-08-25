<?php
/**
 * File containing the Grade_Comments_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Repositories;

use DateTime;
use Sensei\Quiz_Submission\Models\Grade;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Grade_Comments_Repository.
 *
 * @since $$next-version$$
 */
class Grade_Comments_Repository implements Grade_Repository_Interface {
	/**
	 * Create a new grade.
	 *
	 * @param int         $answer_id   The answer ID.
	 * @param int         $question_id The question ID.
	 * @param int         $points      The points.
	 * @param string|null $feedback    The feedback.
	 *
	 * @return Grade The grade model.
	 */
	public function create( int $answer_id, int $question_id, int $points, string $feedback = null ): Grade {
		// TODO: Implement create() method.

		return new Grade();
	}

	/**
	 * Get a grade.
	 *
	 * @param int $answer_id The answer ID.
	 *
	 * @return Grade|null The grade model.
	 */
	public function get( int $answer_id ): ?Grade {
		// TODO: Implement get() method.

		return null;
	}

	/**
	 * Get all grades for a quiz submission.
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Grade[] An array of grades.
	 */
	public function get_all( int $submission_id ): array {
		$grades_map = get_comment_meta( $submission_id, 'quiz_grades', true );

		if ( ! $grades_map ) {
			return [];
		}

		$feedback_map = get_comment_meta( $submission_id, 'quiz_answers_feedback', true );

		$grades = [];
		foreach ( $grades_map as $question_id => $points ) {
			$feedback = $feedback_map[ $question_id ] ?? null;
			$grades[] = new Grade( 0, 0, $question_id, $points, new DateTime(), null, $feedback );
		}

		return $grades;
	}

	/**
	 * Delete all grades for a submission.
	 *
	 * @param int $submission_id The submission ID.
	 */
	public function delete_all( int $submission_id ): void {
		delete_comment_meta( $submission_id, 'quiz_grades' );
		delete_comment_meta( $submission_id, 'quiz_answers_feedback' );
	}
}

<?php
/**
 * File containing the Grade_Comments_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Repositories;

use DateTime;
use Sensei\Quiz_Submission\Models\Grade_Comments;
use Sensei\Quiz_Submission\Models\Grade_Interface;

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
	 * Get all grades for a quiz submission.
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Grade_Comments[] An array of grades.
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
			$grades[] = new Grade_Comments( $question_id, $points, $feedback );
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

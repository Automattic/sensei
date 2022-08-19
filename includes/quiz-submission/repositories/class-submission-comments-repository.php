<?php
/**
 * File containing the Submission_Comments_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Repositories;

use DateTime;
use Exception;
use Sensei\Quiz_Submission\Models\Submission;
use Sensei_Utils;
use WP_Comment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Submission_Comments_Repository.
 *
 * @since $$next-version$$
 */
class Submission_Comments_Repository implements Submission_Repository_Interface {
	/**
	 * Creates a new quiz submission.
	 *
	 * @param int        $quiz_id     The quiz ID.
	 * @param int        $user_id     The user ID.
	 * @param float|null $final_grade The final grade.
	 *
	 * @return Submission The course progress.
	 * @throws Exception  Emits Exception in case the lesson status is missing.
	 */
	public function create( int $quiz_id, int $user_id, float $final_grade = null ): Submission {
		$status_comment = $this->get_status_comment( $quiz_id, $user_id );

		if ( ! $status_comment ) {
			throw new Exception( 'The lesson status is missing.' );
		}

		if ( null !== $final_grade ) {
			update_comment_meta( $status_comment->comment_ID, 'grade', $final_grade );
		}

		$created_at = $this->get_created_date( $status_comment );

		return new Submission(
			$status_comment->comment_ID,
			$quiz_id,
			$user_id,
			$created_at,
			null,
			$final_grade
		);
	}

	/**
	 * Get or create a new quiz submission if it doesn't exist.
	 *
	 * @param int        $quiz_id     The quiz ID.
	 * @param int        $user_id     The user ID.
	 * @param float|null $final_grade The final grade.
	 *
	 * @return Submission The course progress.
	 */
	public function get_or_create( int $quiz_id, int $user_id, float $final_grade = null ): Submission {
		$submission = $this->get( $quiz_id, $user_id );

		if ( $submission ) {
			return $submission;
		}

		return $this->create( $quiz_id, $user_id, $final_grade );
	}

	/**
	 * Gets a course progress.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Submission|null The quiz submission.
	 */
	public function get( int $quiz_id, int $user_id ): ?Submission {
		$status_comment = $this->get_status_comment( $quiz_id, $user_id );

		if ( ! $status_comment ) {
			return null;
		}

		$final_grade = get_comment_meta( $status_comment->comment_ID, 'grade', true );
		$created_at  = $this->get_created_date( $status_comment );

		return new Submission(
			$status_comment->comment_ID,
			$quiz_id,
			$user_id,
			$created_at,
			null,
			$final_grade ? $final_grade : null
		);
	}

	/**
	 * Save quiz submission.
	 *
	 * @param Submission $submission The quiz submission.
	 *
	 * @throws Exception Emits Exception in case the lesson status is missing.
	 */
	public function save( Submission $submission ): void {
		$status_comment = $this->get_status_comment( $submission->get_quiz_id(), $submission->get_user_id() );

		if ( ! $status_comment ) {
			throw new Exception( 'The lesson status is missing.' );
		}

		$final_grade = $submission->get_final_grade();

		if ( null !== $final_grade ) {
			update_comment_meta( $status_comment->comment_ID, 'grade', $final_grade );
		}
	}

	/**
	 * Get the lesson status comment.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return WP_Comment|null The comment instance or null if not found.
	 */
	private function get_status_comment( int $quiz_id, int $user_id ): ?WP_Comment {
		$lesson_id = Sensei()->quiz->get_lesson_id( $quiz_id );

		$status_comment = Sensei_Utils::user_lesson_status( $lesson_id, $user_id );

		if ( ! is_a( $status_comment, WP_comment::class ) ) {
			return null;
		}

		return $status_comment;
	}

	/**
	 * Get the submission creation date by using the status comment start date.
	 * The status comment start date is not the real submission creation date,
	 * but this is the best we have.
	 *
	 * @param WP_Comment $status_comment The lesson status comment.
	 *
	 * @return DateTime  The created date.
	 * @throws Exception Emits Exception in case of a date error.
	 */
	private function get_created_date( WP_Comment $status_comment ): DateTime {
		$start_date = get_comment_meta( $status_comment->comment_ID, 'start', true );

		return $start_date ? new DateTime( $start_date ) : new DateTime();
	}
}

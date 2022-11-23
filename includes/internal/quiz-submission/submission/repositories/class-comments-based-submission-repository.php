<?php
/**
 * File containing the Comments_Based_Submission_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Submission\Repositories;

use DateTime;
use DateTimeInterface;
use RuntimeException;
use Sensei\Internal\Quiz_Submission\Submission\Models\Submission;
use Sensei_Utils;
use WP_Comment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comments_Based_Submission_Repository.
 *
 * @internal
 *
 * @since 4.7.2
 */
class Comments_Based_Submission_Repository implements Submission_Repository_Interface {
	/**
	 * Creates a new quiz submission.
	 *
	 * @internal
	 *
	 * @param int        $quiz_id     The quiz ID.
	 * @param int        $user_id     The user ID.
	 * @param float|null $final_grade The final grade.
	 *
	 * @return Submission       The course progress.
	 * @throws RuntimeException In case the lesson status is missing.
	 */
	public function create( int $quiz_id, int $user_id, float $final_grade = null ): Submission {
		$status_comment = $this->get_status_comment( $quiz_id, $user_id );

		if ( ! $status_comment ) {
			throw new RuntimeException( 'Missing lesson status.' );
		}

		if ( null !== $final_grade ) {
			update_comment_meta( $status_comment->comment_ID, 'grade', $final_grade );
		}

		$created_at = $this->get_created_date( $status_comment );

		return new Submission(
			$status_comment->comment_ID,
			$quiz_id,
			$user_id,
			$final_grade,
			$created_at,
			$created_at
		);
	}

	/**
	 * Get or create a new quiz submission if it doesn't exist.
	 *
	 * @internal
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
	 * @internal
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
			is_numeric( $final_grade ) ? $final_grade : null,
			$created_at,
			$created_at
		);
	}

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
	public function get_question_ids( int $quiz_id, int $user_id ): array {
		$status_comment = $this->get_status_comment( $quiz_id, $user_id );
		if ( ! $status_comment ) {
			return [];
		}

		$questions_asked_csv = get_comment_meta( $status_comment->comment_ID, 'questions_asked', true );
		if ( ! $questions_asked_csv ) {
			return [];
		}

		return array_map(
			'intval',
			explode( ',', $questions_asked_csv )
		);
	}

	/**
	 * Save quiz submission.
	 *
	 * @internal
	 *
	 * @param Submission $submission The quiz submission.
	 *
	 * @throws RuntimeException In case the lesson status is missing.
	 */
	public function save( Submission $submission ): void {
		$status_comment = $this->get_status_comment( $submission->get_quiz_id(), $submission->get_user_id() );

		if ( ! $status_comment ) {
			throw new RuntimeException( 'Missing lesson status.' );
		}

		if ( null !== $submission->get_final_grade() ) {
			update_comment_meta( $status_comment->comment_ID, 'grade', $submission->get_final_grade() );
		} else {
			delete_comment_meta( $status_comment->comment_ID, 'grade' );
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

		if ( ! $status_comment ) {
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
	 * @return DateTimeInterface The created date.
	 */
	private function get_created_date( WP_Comment $status_comment ): DateTimeInterface {
		$start_date = get_comment_meta( $status_comment->comment_ID, 'start', true );

		return $start_date
			? new DateTime( $start_date, wp_timezone() )
			: current_datetime();
	}
}

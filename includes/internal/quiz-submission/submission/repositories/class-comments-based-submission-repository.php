<?php
/**
 * File containing the Comments_Based_Submission_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Submission\Repositories;

use Sensei\Internal\Quiz_Submission\Comment_Metadata;
use DateTimeImmutable;
use DateTimeInterface;
use Sensei\Internal\Persistence_Exception;
use Sensei\Internal\Quiz_Submission\Submission\Models\Comments_Based_Submission;
use Sensei\Internal\Quiz_Submission\Submission\Models\Submission_Interface;
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
	 * @return Submission_Interface The quiz submission.
	 * @throws Persistence_Exception If lesson progress is missing or the grade cannot be saved.
	 */
	public function create( int $quiz_id, int $user_id, ?float $final_grade = null ): Submission_Interface {
		/**
		 * Filters the quiz ID when quiz submission is created.
		 *
		 * @hook sensei_quiz_submission_create_quiz_id
		 *
		 * @since 4.23.1
		 *
		 * @param {int} $quiz_id The quiz ID.
		 * @return {int} The quiz ID.
		*/
		$quiz_id = (int) apply_filters( 'sensei_quiz_submission_create_quiz_id', $quiz_id );

		$status_comment = $this->get_status_comment( $quiz_id, $user_id );

		if ( ! $status_comment ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Safe persistence diagnostics.
			throw new Persistence_Exception( 'quiz_submission', 'comments', $quiz_id, $user_id, 'lesson_progress_missing' );
		}

		$created_at = $this->get_created_date( $status_comment );

		$submission = new Comments_Based_Submission(
			(int) $status_comment->comment_ID,
			$quiz_id,
			$user_id,
			$final_grade,
			$created_at,
			$created_at
		);

		if ( null !== $final_grade ) {
			Comment_Metadata::update( $status_comment->comment_ID, 'grade', $final_grade, $submission );
		}

		return $submission;
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
	 * @return Submission_Interface The quiz submission.
	 * @throws \Sensei\Internal\Persistence_Exception If required quiz data cannot be persisted.
	 */
	public function get_or_create( int $quiz_id, int $user_id, ?float $final_grade = null ): Submission_Interface {
		/**
		 * Filters the quiz ID when quiz submission is created.
		 *
		 * @hook sensei_quiz_submission_get_or_create_quiz_id
		 *
		 * @since 4.23.1
		 *
		 * @param {int} $quiz_id The quiz ID.
		 * @return {int} The quiz ID.
		 */
		$quiz_id = (int) apply_filters( 'sensei_quiz_submission_get_or_create_quiz_id', $quiz_id );

		$submission = $this->get( $quiz_id, $user_id );

		if ( $submission ) {
			return $submission;
		}

		return $this->create( $quiz_id, $user_id, $final_grade );
	}

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
	public function get( int $quiz_id, int $user_id ): ?Submission_Interface {
		/**
		 * Filters the quiz ID when quiz submission is retrieved.
		 *
		 * @hook sensei_quiz_submission_get_quiz_id
		 *
		 * @since 4.23.1
		 *
		 * @param {int} $quiz_id The quiz ID.
		 * @return {int} The quiz ID.
		 */
		$quiz_id = (int) apply_filters( 'sensei_quiz_submission_get_quiz_id', $quiz_id );

		$status_comment = $this->get_status_comment( $quiz_id, $user_id );

		if (
			! $status_comment
			|| ! $this->get_question_ids( $status_comment->comment_ID )
		) {
			return null;
		}

		$final_grade = get_comment_meta( $status_comment->comment_ID, 'grade', true );
		$created_at  = $this->get_created_date( $status_comment );

		return new Comments_Based_Submission(
			(int) $status_comment->comment_ID,
			$quiz_id,
			$user_id,
			is_numeric( $final_grade ) ? $final_grade : null,
			$created_at,
			$created_at
		);
	}

	/**
	 * Get the questions related to the quiz submission.
	 *
	 * @internal
	 *
	 * @param int $submission_id The quiz submission ID.
	 *
	 * @return array An array of question post IDs.
	 */
	public function get_question_ids( int $submission_id ): array {
		/**
		 * Filters the quiz submission ID when getting the question IDs.
		 *
		 * @hook sensei_quiz_submission_get_question_ids_submission_id
		 *
		 * @since 4.23.1
		 *
		 * @param {int}    $submission_id The quiz submission ID.
		 * @param {string} $context       The context.
		 * @return {int} The quiz submission ID.
		 */
		$submission_id = apply_filters( 'sensei_quiz_submission_get_question_ids_submission_id', $submission_id, 'comments' );

		$questions_asked_csv = get_comment_meta( $submission_id, 'questions_asked', true );
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
	 * @param Submission_Interface $submission The quiz submission.
	 *
	 * @throws Persistence_Exception If lesson progress is missing or the grade cannot be saved.
	 */
	public function save( Submission_Interface $submission ): void {
		$status_comment = $this->get_status_comment( $submission->get_quiz_id(), $submission->get_user_id() );

		if ( ! $status_comment ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Safe persistence diagnostics.
			throw new Persistence_Exception( 'quiz_submission', 'comments', $submission->get_quiz_id(), $submission->get_user_id(), 'lesson_progress_missing', 'save' );
		}

		if ( null !== $submission->get_final_grade() ) {
			Comment_Metadata::update( (int) $status_comment->comment_ID, 'grade', $submission->get_final_grade(), $submission );
		} else {
			Comment_Metadata::clear_grade( (int) $status_comment->comment_ID, $submission );
		}
	}

	/**
	 * Delete the quiz submission.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission The quiz submission.
	 * @throws Persistence_Exception If required submission metadata cannot be deleted.
	 */
	public function delete( Submission_Interface $submission ): void {
		if ( $submission instanceof Comments_Based_Submission ) {
			$comment_id = $submission->get_id();
		} else {
			// A table submission ID is not a comment ID.
			$status_comment = $this->get_status_comment( $submission->get_quiz_id(), $submission->get_user_id() );
			if ( ! $status_comment ) {
				return;
			}
			$comment_id = (int) $status_comment->comment_ID;
		}

		Comment_Metadata::delete( $comment_id, 'grade', $submission, 'quiz_submission' );

		// This key makes the comments-based submission discoverable, so clear it last.
		Comment_Metadata::delete( $comment_id, 'questions_asked', $submission, 'quiz_submission' );
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
			? new DateTimeImmutable( $start_date, wp_timezone() )
			: new DateTimeImmutable( 'now', wp_timezone() );
	}
}

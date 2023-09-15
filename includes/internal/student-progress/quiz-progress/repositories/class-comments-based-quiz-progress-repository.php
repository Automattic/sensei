<?php
/**
 * File containing the Comments_Based_Quiz_Progress_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Quiz_Progress\Repositories;

use DateTime;
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Comments_Based_Quiz_Progress;
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress_Interface;
use Sensei_Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comments_Based_Quiz_Progress_Repository.
 *
 * @internal
 *
 * @since 4.7.2
 */
class Comments_Based_Quiz_Progress_Repository implements Quiz_Progress_Repository_Interface {

	/**
	 * Create a new quiz progress.
	 *
	 * @internal
	 *
	 * @param int $quiz_id Quiz identifier.
	 * @param int $user_id User identifier.
	 * @return Comments_Based_Quiz_Progress
	 * @throws \RuntimeException When the quiz progress doesn't exist. In this implementation we re-use lesson progress.
	 */
	public function create( int $quiz_id, int $user_id ): Comments_Based_Quiz_Progress {
		$progress = $this->get( $quiz_id, $user_id );
		if ( ! $progress ) {
			/**
			 * In comments-based implementation we don't have a separate quiz progress.
			 * It depends on the lesson progress. If it doesn't exist yet, throw an exception.
			 */
			throw new \RuntimeException( 'Cannot create quiz progress' );
		}

		return $progress;
	}

	/**
	 * Find a quiz progress by quiz and user identifiers.
	 *
	 * @internal
	 *
	 * @param int $quiz_id Quiz identifier.
	 * @param int $user_id User identifier.
	 * @return Comments_Based_Quiz_Progress
	 */
	public function get( int $quiz_id, int $user_id ): ?Comments_Based_Quiz_Progress {
		$lesson_id = Sensei()->quiz->get_lesson_id( $quiz_id );
		if ( ! $lesson_id ) {
			return null;
		}

		$activity_args = [
			'post_id' => $lesson_id,
			'user_id' => $user_id,
			'type'    => 'sensei_lesson_status',
		];
		$comment       = Sensei_Utils::sensei_check_for_activity( $activity_args, true );
		if ( ! $comment ) {
			return null;
		}

		$comment_date = new DateTime( $comment->comment_date, wp_timezone() );
		$meta_start   = get_comment_meta( $comment->comment_ID, 'start', true );
		$started_at   = ! empty( $meta_start ) ? new DateTime( $meta_start, wp_timezone() ) : current_datetime();

		if ( in_array( $comment->comment_approved, [ 'complete', 'passed', 'graded' ], true ) ) {
			$completed_at = $comment_date;
		} else {
			$completed_at = null;
		}

		return new Comments_Based_Quiz_Progress( (int) $comment->comment_ID, $quiz_id, $user_id, $comment->comment_approved, $started_at, $completed_at, $comment_date, $comment_date );
	}

	/**
	 * Check if a quiz progress exists.
	 *
	 * @internal
	 *
	 * @param int $quiz_id Quiz identifier.
	 * @param int $user_id User identifier.
	 * @return bool
	 */
	public function has( int $quiz_id, int $user_id ): bool {
		$lesson_id     = Sensei()->quiz->get_lesson_id( $quiz_id );
		$activity_args = [
			'post_id' => $lesson_id,
			'user_id' => $user_id,
			'type'    => 'sensei_lesson_status',
		];
		$count         = Sensei_Utils::sensei_check_for_activity( $activity_args );
		return $count > 0;

	}

	/**
	 * Save the quiz progress.
	 *
	 * @internal
	 *
	 * @param Quiz_Progress_Interface $quiz_progress Quiz progress.
	 */
	public function save( Quiz_Progress_Interface $quiz_progress ): void {
		$this->assert_comments_based_quiz_progress( $quiz_progress );

		$lesson_id = Sensei()->quiz->get_lesson_id( $quiz_progress->get_quiz_id() );
		$metadata  = [];
		if ( $quiz_progress->get_started_at() ) {
			$metadata['start'] = $quiz_progress->get_started_at()->format( 'Y-m-d H:i:s' );
		}

		// We need to use internal value for status, not the one returned by the getter.
		$reflection_class = new \ReflectionClass( Comments_Based_Quiz_Progress::class );
		$status_property  = $reflection_class->getProperty( 'status' );
		$status_property->setAccessible( true );
		$status           = $status_property->getValue( $quiz_progress );

		Sensei_Utils::update_lesson_status( $quiz_progress->get_user_id(), $lesson_id, $status, $metadata );
	}

	/**
	 * Delete the quiz progress.
	 *
	 * @internal
	 *
	 * @param Quiz_Progress_Interface $quiz_progress Quiz progress.
	 */
	public function delete( Quiz_Progress_Interface $quiz_progress ): void {
		Sensei_Utils::sensei_delete_quiz_answers( $quiz_progress->get_quiz_id(), $quiz_progress->get_user_id() );
	}

	/**
	 * Delete all quiz progress for a given quiz.
	 *
	 * @internal
	 *
	 * @param int $quiz_id Quiz identifier.
	 */
	public function delete_for_quiz( int $quiz_id ): void {
		$lesson_id = Sensei()->quiz->get_lesson_id( $quiz_id );

		$activity_args = [
			'post_id' => $lesson_id,
			'type'    => 'sensei_lesson_status',
		];
		$comments      = Sensei_Utils::sensei_check_for_activity( $activity_args, true );
		foreach ( $comments as $comment ) {
			$this->delete_grade_and_answers( $comment->comment_ID );
			Sensei_Utils::sensei_delete_quiz_answers( $quiz_id, $comment->user_id );
		}
	}

	/**
	 * Delete all quiz grades and answers for a user.
	 *
	 * @internal
	 *
	 * @param int $user_id User identifier.
	 */
	public function delete_for_user( int $user_id ): void {
		$activity_args = [
			'user_id' => $user_id,
			'type'    => 'sensei_lesson_status',
		];
		$comments      = Sensei_Utils::sensei_check_for_activity( $activity_args, true );
		foreach ( $comments as $comment ) {
			$this->delete_grade_and_answers( $comment->comment_ID );
		}
	}

	/**
	 * Delete the quiz grade and answers.
	 *
	 * @param int $comment_id Comment identifier.
	 */
	private function delete_grade_and_answers( $comment_id ): void {
		delete_comment_meta( $comment_id, 'quiz_answers' );
		delete_comment_meta( $comment_id, 'grade' );
	}

	/**
	 * Assert that the quiz progress is a Comments_Based_Quiz_Progress.
	 *
	 * @param Quiz_Progress_Interface $quiz_progress Quiz progress.
	 * @throws \InvalidArgumentException When the quiz progress is not a Comments_Based_Quiz_Progress.
	 */
	private function assert_comments_based_quiz_progress( Quiz_Progress_Interface $quiz_progress ): void {
		if ( ! $quiz_progress instanceof Comments_Based_Quiz_Progress ) {
			$actual_type = get_class( $quiz_progress );
			throw new \InvalidArgumentException( "Expected a Comments_BasedQuiz_Progress, got {$actual_type} instead." );
		}
	}
}

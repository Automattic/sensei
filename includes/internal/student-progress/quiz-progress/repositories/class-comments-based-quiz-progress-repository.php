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
use WP_Comment;

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
	 * @return Quiz_Progress_Interface
	 * @throws \RuntimeException When the quiz progress doesn't exist. In this implementation we re-use lesson progress.
	 */
	public function create( int $quiz_id, int $user_id ): Quiz_Progress_Interface {
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
	 * @return Quiz_Progress_Interface
	 */
	public function get( int $quiz_id, int $user_id ): ?Quiz_Progress_Interface {
		if ( ! $user_id ) {
			return null;
		}

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
		if ( ! $comment instanceof WP_Comment ) {
			return null;
		}

		return $this->create_progress_from_comment( $comment, $quiz_id );
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
		if ( ! $user_id ) {
			return false;
		}

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
		// Commets-based `get_status` method excludes lesson-progress `complete` status, that we still need while saving.
		$reflection_class = new \ReflectionClass( Comments_Based_Quiz_Progress::class );
		$status_property  = $reflection_class->getProperty( 'status' );
		$status_property->setAccessible( true );
		$status = $status_property->getValue( $quiz_progress );

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
		if ( ! $user_id ) {
			return;
		}

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
			throw new \InvalidArgumentException( esc_html( "Expected Comments_Based_Quiz_Progress, got {$actual_type}." ) );
		}
	}

	/**
	 * Find quiz progress.
	 *
	 * @internal
	 *
	 * @param array $args The arguments.
	 * @return Quiz_Progress_Interface[] The quiz progress.
	 * @throws \InvalidArgumentException When ordering is not supported.
	 */
	public function find( array $args ): array {
		$comments_args = array(
			'type'    => 'sensei_lesson_status',
			'order'   => 'ASC',
			'orderby' => 'comment_ID',
		);

		$quiz_id = $args['quiz_id'] ?? null;
		if ( isset( $args['quiz_id'] ) ) {
			$lesson_ids = Sensei()->quiz->get_lesson_ids( (array) $args['quiz_id'] );
			if ( ! empty( $lesson_ids ) ) {
				$comments_args['post__in'] = $lesson_ids;
			} else {
				return array();
			}
		}

		if ( isset( $args['user_id'] ) ) {
			$comments_args['user_id'] = $args['user_id'];
		}

		if ( isset( $args['status'] ) ) {
			$comments_args['status'] = $args['status'];
		}

		if ( isset( $args['order'] ) ) {
			$comments_args['order'] = $args['order'];
		}

		if ( isset( $args['orderby'] ) ) {
			switch ( $args['orderby'] ) {
				case 'started_at':
					throw new \InvalidArgumentException( 'Ordering by started_at is not supported in comments-based version.' );
				case 'completed_at':
				case 'created_at':
				case 'updated_at':
					$comments_args['orderby'] = 'comment_date';
					break;
				case 'quiz_id':
					// We need to order by lesson ID, not quiz ID, as the lesson ID is not reachable from the comment.
					$comments_args['orderby'] = 'comment_post_ID';
					break;
				case 'id':
					$comments_args['orderby'] = 'comment_ID';
					break;
				case 'status':
					$comments_args['orderby'] = 'comment_approved';
					break;
				default:
					$comments_args['orderby'] = $args['orderby'];
					break;
			}
		}

		if ( isset( $args['order'] ) ) {
			$comments_args['order'] = $args['order'];
		}

		if ( isset( $args['offset'] ) ) {
			$comments_args['offset'] = $args['offset'];
		}

		if ( isset( $args['number'] ) ) {
			$comments_args['number'] = $args['number'];
		}

		$comments = \Sensei_Utils::sensei_check_for_activity( $comments_args, true );
		if ( empty( $comments ) ) {
			return array();
		}

		$comments = is_array( $comments ) ? $comments : array( $comments );

		$quiz_progresses = [];
		foreach ( $comments as $comment ) {
			$quiz_progresses[] = $this->create_progress_from_comment( $comment, $quiz_id );
		}

		return $quiz_progresses;
	}

	/**
	 * Create a lesson progress from a comment.
	 *
	 * @param WP_Comment $comment The comment.
	 * @param int|null   $quiz_id The quiz ID that is associated with the status comment.
	 * @return Comments_Based_Quiz_Progress The course progress.
	 */
	private function create_progress_from_comment( WP_Comment $comment, ?int $quiz_id = null ): Comments_Based_Quiz_Progress {
		$comment_date = new DateTime( $comment->comment_date, wp_timezone() );
		$meta_start   = get_comment_meta( (int) $comment->comment_ID, 'start', true );
		$started_at   = ! empty( $meta_start ) ? new DateTime( $meta_start, wp_timezone() ) : current_datetime();

		if ( in_array( $comment->comment_approved, [ 'complete', 'passed', 'graded' ], true ) ) {
			$completed_at = $comment_date;
		} else {
			$completed_at = null;
		}

		if ( is_null( $quiz_id ) ) {
			$quiz_id = Sensei()->lesson->lesson_quizzes( $comment->comment_post_ID );
		}

		return new Comments_Based_Quiz_Progress(
			(int) $comment->comment_ID,
			(int) $quiz_id,
			(int) $comment->user_id,
			$comment->comment_approved,
			$started_at,
			$completed_at,
			$comment_date,
			$comment_date
		);
	}
}

<?php
/**
 * File containing the Comments_Based_Lesson_Progress_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Lesson_Progress\Repositories;

use DateTime;
use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Comments_Based_Lesson_Progress;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress_Interface;
use Sensei_Utils;
use WP_Comment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comments_Based_Lesson_Progress_Repository.
 *
 * @internal
 *
 * @since 4.7.2
 */
class Comments_Based_Lesson_Progress_Repository implements Lesson_Progress_Repository_Interface {
	/**
	 * Creates a new lesson progress.
	 *
	 * @internal
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Lesson_Progress_Interface The lesson progress.
	 * @throws RuntimeException When the lesson progress could not be created.
	 */
	public function create( int $lesson_id, int $user_id ): Lesson_Progress_Interface {
		$metadata   = [
			'start' => current_time( 'mysql' ),
		];
		$comment_id = Sensei_Utils::update_lesson_status( $user_id, $lesson_id, Lesson_Progress_Interface::STATUS_IN_PROGRESS, $metadata );
		if ( ! $comment_id ) {
			throw new RuntimeException( "Can't create a lesson progress" );
		}

		$progress = $this->get( $lesson_id, $user_id );
		if ( ! $progress ) {
			throw new RuntimeException( 'Created lesson progress not found' );
		}

		return $progress;
	}

	/**
	 * Finds a lesson progress by lesson and user.
	 *
	 * @internal
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Lesson_Progress_Interface|null The lesson progress or null if not found.
	 */
	public function get( int $lesson_id, int $user_id ): ?Lesson_Progress_Interface {
		if ( ! $user_id ) {
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

		return $this->create_progress_from_comment( $comment );
	}

	/**
	 * Check if a lesson progress exists.
	 *
	 * @internal
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 * @return bool
	 */
	public function has( int $lesson_id, int $user_id ): bool {
		if ( ! $user_id ) {
			return false;
		}

		$activity_args = [
			'post_id' => $lesson_id,
			'user_id' => $user_id,
			'type'    => 'sensei_lesson_status',
		];
		$count         = Sensei_Utils::sensei_check_for_activity( $activity_args );
		return $count > 0;
	}

	/**
	 * Save the lesson progress.
	 *
	 * @internal
	 *
	 * @param Lesson_Progress_Interface $lesson_progress The lesson progress.
	 */
	public function save( Lesson_Progress_Interface $lesson_progress ): void {
		$this->assert_comments_based_lesson_progress( $lesson_progress );

		$metadata = [];
		if ( $lesson_progress->get_started_at() ) {
			$metadata['start'] = $lesson_progress->get_started_at()->format( 'Y-m-d H:i:s' );
		}

		// We need to use internal value for status, not the one returned by the getter.
		// Comments_Based_Lesson_Progress::get_status() returns a normalized status, but we need the internal one.
		$reflection_class = new ReflectionClass( Comments_Based_Lesson_Progress::class );
		$status_property  = $reflection_class->getProperty( 'status' );
		$status_property->setAccessible( true );
		$status = $status_property->getValue( $lesson_progress );

		$comment_id = Sensei_Utils::update_lesson_status(
			$lesson_progress->get_user_id(),
			$lesson_progress->get_lesson_id(),
			$status,
			$metadata
		);

		if ( $lesson_progress->is_complete() && $comment_id ) {
			$comment = [
				'comment_ID'   => $comment_id,
				'comment_date' => $lesson_progress->get_completed_at()->format( 'Y-m-d H:i:s' ),
			];
			wp_update_comment( $comment );
			Sensei()->flush_comment_counts_cache( $lesson_progress->get_lesson_id() );
		}
	}

	/**
	 * Delete the lesson progress.
	 *
	 * @internal
	 *
	 * @param Lesson_Progress_Interface $lesson_progress The lesson progress.
	 */
	public function delete( Lesson_Progress_Interface $lesson_progress ): void {
		$args = array(
			'post_id' => $lesson_progress->get_lesson_id(),
			'type'    => 'sensei_lesson_status',
			'user_id' => $lesson_progress->get_user_id(),
		);

		// This auto deletes the corresponding meta data, such as the quiz grade, and questions asked.
		Sensei_Utils::sensei_delete_activities( $args );
	}

	/**
	 * Delete all lesson progress for a lesson.
	 * This is used when a lesson is deleted.
	 *
	 * @internal
	 *
	 * @param int $lesson_id The lesson ID.
	 */
	public function delete_for_lesson( int $lesson_id ): void {
		$args = array(
			'post_id' => $lesson_id,
			'type'    => 'sensei_lesson_status',
		);

		$this->delete_activities( $args );
	}

	/**
	 * Delete all lesson progress for a user.
	 * This is used when a user is deleted.
	 *
	 * @internal
	 *
	 * @param int $user_id The user ID.
	 */
	public function delete_for_user( int $user_id ): void {
		$args = array(
			'user_id' => $user_id,
			'type'    => 'sensei_lesson_status',
		);

		$this->delete_activities( $args );
	}

	/**
	 * Returns the number of started lessons for a user in a course.
	 * The number of started lessons is the same as the number of lessons that have a progress record.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return int
	 */
	public function count( int $course_id, int $user_id ): int {
		if ( ! $user_id ) {
			return 0;
		}

		$lessons = Sensei()->course->course_lessons( $course_id, 'publish', 'ids' );

		if ( empty( $lessons ) ) {
			return 0;
		}

		$activity_args = array(
			'post__in' => $lessons,
			'user_id'  => $user_id,
			'type'     => 'sensei_lesson_status',
		);

		return Sensei_Utils::sensei_check_for_activity( $activity_args );
	}

	/**
	 * Delete activity comments by given arguments.
	 *
	 * @param array $args Arguments to delete activity comments.
	 */
	private function delete_activities( array $args ): void {
		$comments = Sensei_Utils::sensei_check_for_activity( $args, true );
		if ( ! $comments ) {
			return;
		}

		$comments = is_array( $comments ) ? $comments : [ $comments ];
		$post_ids = [];
		foreach ( $comments as $comment ) {
			if ( isset( $comment->comment_post_ID ) ) {
				$post_ids[] = $comment->comment_post_ID;
			}

			if ( isset( $comment->comment_ID ) && 0 < $comment->comment_ID ) {
				wp_delete_comment( intval( $comment->comment_ID ), true );
			}
		}

		foreach ( $post_ids as $post_id ) {
			Sensei()->flush_comment_counts_cache( $post_id );
		}
	}

	/**
	 * Asserts that the lesson progress is a Comments_Based_Lesson_Progress.
	 *
	 * @param Lesson_Progress_Interface $lesson_progress The lesson progress.
	 * @throws InvalidArgumentException When the lesson progress is not a Comments_Based_Lesson_Progress.
	 */
	private function assert_comments_based_lesson_progress( Lesson_Progress_Interface $lesson_progress ): void {
		if ( ! $lesson_progress instanceof Comments_Based_Lesson_Progress ) {
			$actual_type = get_class( $lesson_progress );
			throw new InvalidArgumentException( esc_html( "Expected Comments_Based_Lesson_Progress, got {$actual_type}." ) );
		}
	}

	/**
	 * Find lesson progress.
	 *
	 * @internal
	 *
	 * @param array $args The arguments.
	 * @return Lesson_Progress_Interface[]
	 * @throws InvalidArgumentException When the ordering is not supported.
	 */
	public function find( array $args ): array {
		$comments_args = array(
			'type'    => 'sensei_lesson_status',
			'order'   => 'ASC',
			'orderby' => 'comment_ID',
		);

		if ( isset( $args['lesson_id'] ) ) {
			$comments_args['post__in'] = (array) $args['lesson_id'];
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
					throw new InvalidArgumentException( 'Ordering by started_at is not supported in comments-based version.' );
				case 'completed_at':
				case 'created_at':
				case 'updated_at':
					$comments_args['orderby'] = 'comment_date';
					break;
				case 'lesson_id':
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

		$lesson_progresses = [];
		foreach ( $comments as $comment ) {
			$lesson_progresses[] = $this->create_progress_from_comment( $comment );
		}

		return $lesson_progresses;
	}

	/**
	 * Create a lesson progress from a comment.
	 *
	 * @param WP_Comment $comment The comment.
	 * @return Comments_Based_Lesson_Progress The course progress.
	 */
	private function create_progress_from_comment( WP_Comment $comment ): Comments_Based_Lesson_Progress {
		$comment_date = new DateTime( $comment->comment_date, wp_timezone() );
		$meta_start   = get_comment_meta( (int) $comment->comment_ID, 'start', true );
		$started_at   = ! empty( $meta_start ) ? new DateTime( $meta_start, wp_timezone() ) : current_datetime();

		if ( in_array( $comment->comment_approved, [ 'complete', 'passed', 'graded' ], true ) ) {
			$completed_at = $comment_date;
		} else {
			$completed_at = null;
		}

		return new Comments_Based_Lesson_Progress(
			(int) $comment->comment_ID,
			(int) $comment->comment_post_ID,
			(int) $comment->user_id,
			$comment->comment_approved,
			$started_at,
			$completed_at,
			$comment_date,
			$comment_date
		);
	}
}

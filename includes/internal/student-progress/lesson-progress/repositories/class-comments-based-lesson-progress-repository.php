<?php
/**
 * File containing the Comments_Based_Lesson_Progress_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Lesson_Progress\Repositories;

use DateTime;
use ReflectionClass;
use RuntimeException;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Comments_Based_Lesson_Progress;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress_Interface;
use Sensei_Utils;

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
	 * @return Comments_Based_Lesson_Progress The lesson progress.
	 * @throws RuntimeException When the lesson progress could not be created.
	 */
	public function create( int $lesson_id, int $user_id ): Comments_Based_Lesson_Progress {
		$metadata   = [
			'start' => current_time( 'mysql' ),
		];
		$comment_id = Sensei_Utils::update_lesson_status( $user_id, $lesson_id, Lesson_Progress_Interface::STATUS_IN_PROGRESS, $metadata );
		if ( ! $comment_id ) {
			throw new RuntimeException( "Can't create a lesson progress" );
		}

		return $this->get( $lesson_id, $user_id );
	}

	/**
	 * Finds a lesson progress by lesson and user.
	 *
	 * @internal
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Comments_Based_Lesson_Progress|null The lesson progress or null if not found.
	 */
	public function get( int $lesson_id, int $user_id ): ?Comments_Based_Lesson_Progress {
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

		return new Comments_Based_Lesson_Progress( (int) $comment->comment_ID, $lesson_id, $user_id, $comment->comment_approved, $started_at, $completed_at, $comment_date, $comment_date );
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
		$reflection_class = new ReflectionClass( Comments_Based_Lesson_Progress::class );
		$status_property  = $reflection_class->getProperty( 'status' );
		$status_property->setAccessible( true );
		$status           = $status_property->getValue( $lesson_progress );

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
	 * @throws RuntimeException When the lesson progress is not a Comments_Based_Lesson_Progress.
	 */
	private function assert_comments_based_lesson_progress( Lesson_Progress_Interface $lesson_progress ): void {
		if ( ! $lesson_progress instanceof Comments_Based_Lesson_Progress ) {
			$actual_type = get_class( $lesson_progress );
			throw new RuntimeException( "Expected Comments_Based_Lesson_Progress, {$actual_type} given instead" );
		}
	}
}


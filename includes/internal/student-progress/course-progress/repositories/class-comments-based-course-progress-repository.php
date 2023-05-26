<?php
/**
 * File containing the Comments_Based_Course_Progress_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Course_Progress\Repositories;

use DateTime;
use Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress;
use Sensei_Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comments_Based_Course_Progress_Repository
 *
 * @internal
 *
 * @since 4.7.2
 */
class Comments_Based_Course_Progress_Repository implements Course_Progress_Repository_Interface {
	/**
	 * Creates a new course progress.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return Course_Progress The course progress.
	 * @throws \RuntimeException If the course progress could not be created.
	 */
	public function create( int $course_id, int $user_id ): Course_Progress {
		$metadata   = [
			'start'    => current_time( 'mysql' ),
			'percent'  => 0,
			'complete' => 0,
		];
		$comment_id = Sensei_Utils::update_course_status( $user_id, $course_id, Course_Progress::STATUS_IN_PROGRESS, $metadata );
		if ( ! $comment_id ) {
			throw new \RuntimeException( "Can't create a course progress" );
		}

		return $this->get( $course_id, $user_id );
	}

	/**
	 * Gets a course progress.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Course_Progress|null The course progress.
	 */
	public function get( int $course_id, int $user_id ): ?Course_Progress {
		$activity_args = [
			'post_id' => $course_id,
			'user_id' => $user_id,
			'type'    => 'sensei_course_status',
		];
		$comment       = Sensei_Utils::sensei_check_for_activity( $activity_args, true );
		if ( ! $comment ) {
			return null;
		}

		if ( is_array( $comment ) ) {
			usort(
				$comment,
				[ $this, 'sort_comments' ]
			);
			$comment = reset( $comment );
		}

		$meta_start = get_comment_meta( $comment->comment_ID, 'start', true );
		$started_at = $meta_start ? new DateTime( $meta_start, wp_timezone() ) : current_datetime();

		$comment_date = new DateTime( $comment->comment_date, wp_timezone() );
		if ( Course_Progress::STATUS_COMPLETE === $comment->comment_approved ) {
			$completed_at = $comment_date;
		} else {
			$completed_at = null;
		}

		return new Course_Progress( (int) $comment->comment_ID, $course_id, $user_id, $comment->comment_approved, $started_at, $completed_at, $comment_date, $comment_date );
	}

	/**
	 * Sort comments by comment ID in descending order.
	 *
	 * @param \stdClass $a First comment to compare.
	 * @param \stdClass $b Second comment to compare.
	 *
	 * @return int
	 */
	private function sort_comments( $a, $b ) {
		$a_id = (int) $a->comment_ID;
		$b_id = (int) $b->comment_ID;
		if ( $a_id === $b_id ) {
			return 0;
		}
		return ( $a_id > $b_id ) ? -1 : 1;
	}

	/**
	 * Checks if a course progress exists.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return bool Whether the course progress exists.
	 */
	public function has( int $course_id, int $user_id ): bool {
		$activity_args = [
			'post_id' => $course_id,
			'user_id' => $user_id,
			'type'    => 'sensei_course_status',
		];
		$count         = Sensei_Utils::sensei_check_for_activity( $activity_args );
		return $count > 0;
	}

	/**
	 * Save course progress.
	 *
	 * @internal
	 *
	 * @param Course_Progress $course_progress The course progress.
	 */
	public function save( Course_Progress $course_progress ): void {
		$metadata = [];
		if ( $course_progress->get_started_at() ) {
			$metadata['start'] = $course_progress->get_started_at()->format( 'Y-m-d H:i:s' );
		}
		Sensei_Utils::update_course_status( $course_progress->get_user_id(), $course_progress->get_course_id(), $course_progress->get_status(), $metadata );
	}

	/**
	 * Delete course progress.
	 *
	 * @internal
	 *
	 * @param Course_Progress $course_progress The course progress.
	 */
	public function delete( Course_Progress $course_progress ): void {
		$args = array(
			'post_id' => $course_progress->get_course_id(),
			'type'    => 'sensei_course_status',
			'user_id' => $course_progress->get_user_id(),
		);

		Sensei_Utils::sensei_delete_activities( $args );
	}
}

<?php
/**
 * File containing the Comments_Based_Course_Progress_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Student_Progress\Course_Progress\Repositories;

use DateTime;
use Sensei\Student_Progress\Course_Progress\Models\Comments_Based_Course_Progress;
use Sensei\Student_Progress\Course_Progress\Models\Course_Progress_Interface;
use Sensei_Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comments_Based_Course_Progress_Repository
 *
 * @since $$next-version$$
 */
class Comments_Based_Course_Progress_Repository implements Course_Progress_Repository_Interface {
	/**
	 * Creates a new course progress.
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return Course_Progress_Interface The course progress.
	 * @throws \RuntimeException If the course progress could not be created.
	 */
	public function create( int $course_id, int $user_id ): Course_Progress_Interface {
		$metadata   = [
			'start'    => current_time( 'mysql' ),
			'percent'  => 0,
			'complete' => 0,
		];
		$comment_id = Sensei_Utils::update_course_status( $user_id, $course_id, Course_Progress_Interface::STATUS_IN_PROGRESS, $metadata );
		if ( ! $comment_id ) {
			throw new \RuntimeException( "Can't create a course progress" );
		}

		return $this->get( $course_id, $user_id );
	}

	/**
	 * Gets a course progress.
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return Comments_Based_Course_Progress|null The course progress.
	 */
	public function get( int $course_id, int $user_id ): ?Course_Progress_Interface {
		$activity_args = [
			'post_id' => $course_id,
			'user_id' => $user_id,
			'type'    => 'sensei_course_status',
		];
		$comment       = Sensei_Utils::sensei_check_for_activity( $activity_args, true );
		if ( ! $comment ) {
			return null;
		}

		$comment_date = new DateTime( $comment->comment_date, wp_timezone() );
		$comment_meta = [];
		$source_meta  = get_comment_meta( $comment->comment_ID );
		foreach ( $source_meta as $key => $values ) {
			$comment_meta[ $key ] = $values[0] ?? null;
		}
		$started_at = ! empty( $comment_meta['start'] ) ? new DateTime( $comment_meta['start'], wp_timezone() ) : current_datetime();
		unset( $comment_meta['start'] );

		if ( Course_Progress_Interface::STATUS_COMPLETE === $comment->comment_approved ) {
			$completed_at = $comment_date;
		} else {
			$completed_at = null;
		}

		return new Comments_Based_Course_Progress( (int) $comment->comment_ID, $course_id, $user_id, $comment_date, $comment->comment_approved, $started_at, $completed_at, $comment_date, $comment_meta );
	}

	/**
	 * Checks if a course progress exists.
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
	 * @param Course_Progress_Interface $course_progress The course progress.
	 */
	public function save( Course_Progress_Interface $course_progress ): void {
		$metadata = $course_progress->get_metadata();
		if ( $course_progress->get_started_at() ) {
			$metadata['start'] = $course_progress->get_started_at()->format( 'Y-m-d H:i:s' );
		}
		Sensei_Utils::update_course_status( $course_progress->get_user_id(), $course_progress->get_course_id(), $course_progress->get_status(), $metadata );
	}
}

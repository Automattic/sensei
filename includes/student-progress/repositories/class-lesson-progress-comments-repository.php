<?php
/**
 * File containing the Sensei_Lesson_Progress_Comments_Repository class.
 *
 * @package sensei
 */

namespace Sensei\StudentProgress\Repositories;

use DateTime;
use Sensei\StudentProgress\Models\Lesson_Progress_Comments;
use Sensei\StudentProgress\Models\Lesson_Progress_Interface;
use Sensei_Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Lesson_Progress_Comments_Repository.
 *
 * @since $$next-version$$
 */
class Lesson_Progress_Comments_Repository implements Lesson_Progress_Repository_Interface {
	/**
	 * Creates a new lesson progress.
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 * @return Lesson_Progress_Comments The lesson progress.
	 */
	public function create( int $lesson_id, int $user_id ): Lesson_Progress_Interface {
		$comment_id = Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'in-progress' );

		$comment    = get_comment( $comment_id );
		$created_at = new DateTime( $comment->comment_date );

		$comment_meta = get_comment_meta( $comment_id );
		$started_at   = ! empty( $comment_meta['start'] ) ? new DateTime( $comment_meta['start'] ) : new DateTime();
		unset( $comment_meta['start'] );

		return new Lesson_Progress_Comments( $comment_id, $lesson_id, $user_id, $comment->comment_approved, $started_at, null, $created_at, $created_at );
	}

	/**
	 * Finds a lesson progress by lesson and user.
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 * @return Lesson_Progress_Comments|null The lesson progress or null if not found.
	 */
	public function get( int $lesson_id, int $user_id ): ?Lesson_Progress_Interface {
		$activity_args = [
			'post_id' => $lesson_id,
			'user_id' => $user_id,
			'type'    => 'sensei_lesson_status',
		];
		$comment       = Sensei_Utils::sensei_check_for_activity( $activity_args, true );
		if ( ! $comment ) {
			return null;
		}

		$created_at = new DateTime( $comment->comment_date );
		$meta_start = get_comment_meta( $comment->ID, 'start', true );
		$started_at = ! empty( $meta_start ) ? new DateTime( $meta_start ) : new DateTime();

		return new Lesson_Progress_Comments( $comment->ID, $lesson_id, $user_id, $comment->comment_approved, $started_at, null, $created_at, $created_at, $comment_meta );
	}

	/**
	 * Check if a lesson progress exists.
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
	 * @param Lesson_Progress_Interface $lesson_progress The lesson progress.
	 */
	public function save( Lesson_Progress_Interface $lesson_progress ): void {
		$metadata = [];
		if ( $lesson_progress->get_started_at() ) {
			$metadata['start'] = $lesson_progress->get_started_at()->format( 'Y-m-d H:i:s' );
		}
		Sensei_Utils::update_lesson_status( $lesson_progress->get_user_id(), $lesson_progress->get_lesson_id(), $lesson_progress->get_status(), $metadata );
	}

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
}

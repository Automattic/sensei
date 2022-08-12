<?php

class Sensei_Lesson_Progress_Comments_Repository implements Sensei_Lesson_Progress_Repository_Interface {
	/**
	 * Creates a new lesson progress.
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 * @return Sensei_Lesson_Progress The lesson progress.
	 */
	public function create( int $lesson_id, int $user_id ): Sensei_Lesson_Progress {
		$comment_id = Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'in-progress' );

		$comment    = get_comment( $comment_id );
		$created_at = new DateTime( $comment->comment_date );

		$comment_meta = get_comment_meta( $comment_id );
		$started_at   = ! empty( $comment_meta['start'] ) ? new DateTime( $comment_meta['start'] ) : new DateTime();
		unset( $comment_meta['start'] );

		return new Sensei_Lesson_Progress( $comment_id, $lesson_id, $user_id, $comment->comment_approved, $started_at, null, $created_at, $created_at, $comment_meta );
	}

	/**
	 * Finds a lesson progress by lesson and user.
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 * @return Sensei_Lesson_Progress|null The lesson progress or null if not found.
	 */
	public function get( int $lesson_id, int $user_id ): ?Sensei_Lesson_Progress {
		$activity_args = [
			'post_id' => $lesson_id,
			'user_id' => $user_id,
			'type'    => 'sensei_lesson_status',
		];
		$comment       = Sensei_Utils::sensei_check_for_activity( $activity_args, true );
		if ( ! $comment ) {
			return null;
		}

		$created_at   = new DateTime( $comment->comment_date );
		$comment_meta = get_comment_meta( $comment->ID );
		$started_at   = ! empty( $comment_meta['start'] ) ? new DateTime( $comment_meta['start'] ) : new DateTime();
		unset( $comment_meta['start'] );

		return new Sensei_Lesson_Progress( $comment->ID, $lesson_id, $user_id, $comment->comment_approved, $started_at, null, $created_at, $created_at, $comment_meta );
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
	 * @param Sensei_Lesson_Progress $lesson_progress
	 */
	public function save( Sensei_Lesson_Progress $lesson_progress ): void {
		$metadata = $lesson_progress->get_metadata();
		if ( $lesson_progress->get_started_at() ) {
			$metadata['start'] = $lesson_progress->get_started_at()->format( 'Y-m-d H:i:s' );
		}
		Sensei_Utils::update_lesson_status( $lesson_progress->get_user_id(), $lesson_progress->get_lesson_id(), $course_progress->get_status(), $metadata );
	}
}

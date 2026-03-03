<?php
/**
 * File with trait Sensei_Progress_Test_Helpers.
 *
 * @package sensei-tests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helpers for creating progress data through the repository pattern.
 *
 * Use these instead of Sensei_Utils::update_course_status() or
 * Sensei_Utils::update_lesson_status() so that tests exercise the
 * correct storage backend in both comments and HPPS modes.
 *
 * @since 4.25.0
 */
trait Sensei_Progress_Test_Helpers {
	/**
	 * Start course progress for a user.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course ID.
	 */
	private function start_course_progress( int $user_id, int $course_id ): void {
		Sensei_Utils::user_start_course( $user_id, $course_id );
	}

	/**
	 * Complete course progress for a user.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course ID.
	 */
	private function complete_course_progress( int $user_id, int $course_id ): void {
		Sensei_Utils::user_complete_course( $course_id, $user_id, false );
	}

	/**
	 * Start lesson progress for a user.
	 *
	 * @param int $user_id   User ID.
	 * @param int $lesson_id Lesson ID.
	 */
	private function start_lesson_progress( int $user_id, int $lesson_id ): void {
		Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id, false );
	}

	/**
	 * Complete lesson progress for a user.
	 *
	 * @param int $user_id   User ID.
	 * @param int $lesson_id Lesson ID.
	 */
	private function complete_lesson_progress( int $user_id, int $lesson_id ): void {
		Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id, true );
	}
}

<?php
/**
 * File containing the class Sensei_Update_Backfill_Course_Welcome_Email_Sent.
 *
 * @since 4.26.2
 * @package sensei
 */

use Sensei\Internal\Emails\Generators\Course_Welcome;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Backfill the "course welcome email sent" flag for existing course progress.
 *
 * Marks every existing student/course relationship as already welcomed so that,
 * once the email is enabled, a later enrolment transition never re-welcomes
 * existing students.
 *
 * The relationships are enumerated through the resolved course progress
 * repository, so the correct backend (comments or custom tables) is used
 * regardless of whether HPPS is enabled.
 *
 * @since 4.26.2
 */
class Sensei_Update_Backfill_Course_Welcome_Email_Sent extends Sensei_Background_Job_Batch {
	/**
	 * Get the job batch size.
	 *
	 * @return int
	 */
	protected function get_batch_size(): int {
		return 100;
	}

	/**
	 * Can multiple instances be enqueued at the same time?
	 *
	 * @return bool
	 */
	protected function allow_multiple_instances(): bool {
		return false;
	}

	/**
	 * Run batch.
	 *
	 * @param int $offset Current offset.
	 *
	 * @return bool Returns true if there is more to do.
	 */
	protected function run_batch( int $offset ): bool {
		$course_progresses = Sensei()->course_progress_repository->find(
			array(
				'number'  => $this->get_batch_size(),
				'offset'  => $offset,
				'orderby' => 'id',
				'order'   => 'ASC',
			)
		);

		foreach ( $course_progresses as $course_progress ) {
			$user_id   = $course_progress->get_user_id();
			$course_id = $course_progress->get_course_id();

			if ( ! $user_id || ! $course_id ) {
				continue;
			}

			$meta_key = Course_Welcome::get_welcome_sent_meta_key( $course_id );

			// Skip relationships that already have the flag so re-runs never overwrite
			// an existing value.
			if ( ! metadata_exists( 'user', $user_id, $meta_key ) ) {
				update_user_meta( $user_id, $meta_key, Course_Welcome::WELCOME_ASSUMED );
			}
		}

		return count( $course_progresses ) === $this->get_batch_size();
	}
}

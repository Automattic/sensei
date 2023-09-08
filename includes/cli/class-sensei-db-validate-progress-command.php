<?php
/**
 * Sensei_DB_Validate_Progress_Command class file.
 *
 * @package sensei
 */

use Sensei\Internal\Migration\Migration_Job_Scheduler;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Comments_Based_Course_Progress_Repository;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Tables_Based_Course_Progress_Repository;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comments_Based_Lesson_Progress_Repository;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Tables_Based_Lesson_Progress_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * WP-CLI command that validates the progress data.
 *
 * @since $$next_version$$
 */
class Sensei_DB_Validate_Progress_Command {
	/**
	 * Seed the database.
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command arguments with names.
	 */
	public function __invoke( array $args = [], array $assoc_args = [] ) {
		if ( ! $this->is_progress_migration_complete() ) {
			WP_CLI::error( 'The progress migration is not complete. Please run the progress migration first.' );
		}

		$courses = Sensei_Course::get_all_courses();

		foreach ( $courses as $course ) {
			$this->validate_course( $course );

			$lessons = Sensei()->course->course_lessons( $course->ID, 'any' );
			foreach ( $lessons as $lesson ) {
				$this->validate_lesson( $lesson );
			}
		}
	}

	/**
	 * Check if the progress migration is complete.
	 *
	 * @return bool
	 */
	private function is_progress_migration_complete(): bool {
		return (bool) get_option( Migration_Job_Scheduler::COMPLETED_OPTION_NAME, false );
	}

	/**
	 * Validate the course progress.
	 *
	 * @param WP_Post $course Course post object.
	 */
	private function validate_course( WP_Post $course ): void {
		WP_CLI::log( "Validating course {$course->post_title} ({$course->ID})..." );

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$user_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->comments}
				WHERE comment_type = 'sensei_course_status'
				AND comment_post_ID = %d",
				$course->ID
			)
		);
		if ( ! $user_ids ) {
			return;
		}

		$comments_based_repository = new Comments_Based_Course_Progress_Repository();
		$tables_based_repository   = new Tables_Based_Course_Progress_Repository( $wpdb );

		foreach ( $user_ids as $user_id ) {
			$comments_based_progress = $comments_based_repository->get( $course->ID, $user_id );
			$tables_based_progress   = $tables_based_repository->get( $course->ID, $user_id );

			if ( ! $comments_based_progress ) {
				WP_CLI::warning( 'Comments based progress not found.' );
				continue;
			}

			if ( ! $tables_based_progress ) {
				WP_CLI::warning( 'Tables based progress not found.' );
				continue;
			}

			if (
				$comments_based_progress->get_course_id() !== $tables_based_progress->get_course_id()
				|| $comments_based_progress->get_user_id() !== $tables_based_progress->get_user_id()
				|| $comments_based_progress->get_status() !== $tables_based_progress->get_status()
				|| $comments_based_progress->get_started_at() != $tables_based_progress->get_started_at() // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				|| $comments_based_progress->get_completed_at() != $tables_based_progress->get_completed_at() // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			) {
				WP_CLI::warning( 'Data mismatch between comments and tables based progress.' );
				WP_CLI\Utils\format_items(
					'table',
					[
						[
							'source'       => 'comments',
							'course_id'    => $comments_based_progress->get_course_id(),
							'user_id'      => $comments_based_progress->get_user_id(),
							'status'       => $comments_based_progress->get_status(),
							'started_at'   => $comments_based_progress->get_started_at(),
							'completed_at' => $comments_based_progress->get_completed_at(),
						],
						[
							'source'       => 'tables',
							'course_id'    => $tables_based_progress->get_course_id(),
							'user_id'      => $tables_based_progress->get_user_id(),
							'status'       => $tables_based_progress->get_status(),
							'started_at'   => $tables_based_progress->get_started_at(),
							'completed_at' => $tables_based_progress->get_completed_at(),
						],
					],
					[ 'source', 'course_id', 'user_id', 'status', 'started_at', 'completed_at' ]
				);
			}
		}
	}

	/**
	 * Validate the lesson progress.
	 *
	 * @param WP_Post $lesson Lesson post object.
	 */
	private function validate_lesson( WP_Post $lesson ): void {
		WP_CLI::log( "Validating lesson {$lesson->post_title} ({$lesson->ID})..." );

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$user_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->comments}
				WHERE comment_type = 'sensei_lesson_status'
				AND comment_post_ID = %d",
				$lesson->ID
			)
		);
		if ( ! $user_ids ) {
			return;
		}

		$comments_based_repository = new Comments_Based_Lesson_Progress_Repository();
		$tables_based_repository   = new Tables_Based_Lesson_Progress_Repository( $wpdb );

		foreach ( $user_ids as $user_id ) {
			$comments_based_progress = $comments_based_repository->get( $lesson->ID, $user_id );
			$tables_based_progress   = $tables_based_repository->get( $lesson->ID, $user_id );

			if ( ! $comments_based_progress ) {
				WP_CLI::warning( 'Comments based progress not found.' );
				continue;
			}

			if ( ! $tables_based_progress ) {
				WP_CLI::warning( 'Tables based progress not found.' );
				continue;
			}

			if (
				$comments_based_progress->get_lesson_id() !== $tables_based_progress->get_lesson_id()
				|| $comments_based_progress->get_user_id() !== $tables_based_progress->get_user_id()
				|| $comments_based_progress->get_status() !== $tables_based_progress->get_status()
				|| $comments_based_progress->get_started_at() != $tables_based_progress->get_started_at() // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				|| $comments_based_progress->get_completed_at() != $tables_based_progress->get_completed_at() // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			) {
				WP_CLI::warning( 'Data mismatch between comments and tables based progress.' );
				WP_CLI\Utils\format_items(
					'table',
					[
						[
							'source'       => 'comments',
							'lesson_id'    => $comments_based_progress->get_lesson_id(),
							'user_id'      => $comments_based_progress->get_user_id(),
							'status'       => $comments_based_progress->get_status(),
							'started_at'   => $comments_based_progress->get_started_at(),
							'completed_at' => $comments_based_progress->get_completed_at(),
						],
						[
							'source'       => 'tables',
							'lesson_id'    => $tables_based_progress->get_lesson_id(),
							'user_id'      => $tables_based_progress->get_user_id(),
							'status'       => $tables_based_progress->get_status(),
							'started_at'   => $tables_based_progress->get_started_at(),
							'completed_at' => $tables_based_progress->get_completed_at(),
						],
					],
					[ 'source', 'lesson_id', 'user_id', 'status', 'started_at', 'completed_at' ]
				);
			}
		}
	}
}

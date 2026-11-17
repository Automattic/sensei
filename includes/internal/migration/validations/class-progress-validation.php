<?php
/**
 * File containing the Progress_Validation class.
 *
 * @package sensei
 * @since 4.19.1
 */

namespace Sensei\Internal\Migration\Validations;

use RuntimeException;
use Sensei\Internal\Migration\Migration_Job_Scheduler;
use Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress_Interface;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Comments_Based_Course_Progress_Repository;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Interface;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Tables_Based_Course_Progress_Repository;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress_Interface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comments_Based_Lesson_Progress_Repository;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Interface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Tables_Based_Lesson_Progress_Repository;
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress_Interface;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Comments_Based_Quiz_Progress_Repository;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Interface;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Tables_Based_Quiz_Progress_Repository;

/**
 * Class responsible for validation of the migrated progress data.
 *
 * @since 4.19.1
 *
 * @psalm-type Progress_Repository = Course_Progress_Repository_Interface|Lesson_Progress_Repository_Interface|Quiz_Progress_Repository_Interface
 */
class Progress_Validation {
	/**
	 * Errors.
	 *
	 * @var Validation_Error[]
	 */
	private array $errors = array();

	/**
	 * Run the validation.
	 *
	 * @internal
	 *
	 * @since 4.19.1
	 */
	public function run(): void {
		$this->errors = array();

		if ( ! $this->is_progress_migration_complete() ) {
			$this->add_error( 'The progress migration is not complete. Please run the progress migration first.' );
		}

		global $wpdb;

		try {
			$this->validate_progress(
				'course',
				new Comments_Based_Course_Progress_Repository(),
				new Tables_Based_Course_Progress_Repository( $wpdb )
			);
			$this->validate_progress(
				'lesson',
				new Comments_Based_Lesson_Progress_Repository(),
				new Tables_Based_Lesson_Progress_Repository( $wpdb )
			);
			foreach ( $this->get_quiz_ids() as $quiz_id ) {
				$this->validate_quiz_progress( $quiz_id );
			}
		} catch ( RuntimeException $exception ) {
			$this->add_error( $exception->getMessage() );
		}
	}

	/**
	 * Check if there are validation errors.
	 *
	 * @internal
	 *
	 * @since 4.19.1
	 *
	 * @return bool
	 */
	public function has_errors(): bool {
		return (bool) $this->errors;
	}

	/**
	 * Get the validation errors.
	 *
	 * @internal
	 *
	 * @since 4.19.1
	 *
	 * @return Validation_Error[]
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Add a validation error.
	 *
	 * @param string $message Error message.
	 * @param array  $data    Error data.
	 */
	private function add_error( string $message, array $data = [] ): void {
		$this->errors[] = new Validation_Error( $message, $data );
	}

	/**
	 * Get the quiz IDs.
	 *
	 * @psalm-suppress InvalidReturnType, InvalidReturnStatement -- Psalm doesn't understand the 'fields' argument.
	 *
	 * @return int[]
	 */
	private function get_quiz_ids(): array {
		return get_posts(
			[
				'post_type'      => 'quiz',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			]
		);
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
	 * Find comment-only records and validate each table record once.
	 *
	 * @param string $type                The progress type.
	 * @param object $comments_repository The comments repository.
	 * @param object $tables_repository   The tables repository.
	 * @psalm-param Progress_Repository $comments_repository
	 * @psalm-param Progress_Repository $tables_repository
	 */
	private function validate_progress( string $type, object $comments_repository, object $tables_repository ): void {
		$comment_type = 'course' === $type ? 'sensei_course_status' : 'sensei_lesson_status';
		foreach ( Validation_Records::get_comments( $comment_type ) as $record ) {
			$post_id = (int) $record->post_id;
			$user_id = (int) $record->user_id;

			$comments_progress = $comments_repository->get( $post_id, $user_id );
			if ( ! $comments_progress || $comments_progress->get_id() !== (int) $record->id ) {
				continue;
			}

			if ( $tables_repository->get( $post_id, $user_id ) ) {
				continue;
			}

			$this->add_missing_progress_error( $type, 'tables', $post_id, $user_id );
		}

		foreach ( Validation_Records::get_progress_rows( $type ) as $record ) {
			$post_id           = (int) $record->post_id;
			$user_id           = (int) $record->user_id;
			$comments_progress = $comments_repository->get( $post_id, $user_id );
			if ( ! $comments_progress ) {
				$this->add_missing_progress_error( $type, 'comments', $post_id, $user_id );
				continue;
			}

			$tables_progress = $tables_repository->get( $post_id, $user_id );
			if ( $tables_progress ) {
				$this->compare_progress( $comments_progress, $tables_progress );
			}
		}
	}

	/**
	 * Record an identity with no progress in one store.
	 *
	 * @param string $type    The progress type.
	 * @param string $store   The store missing progress.
	 * @param int    $post_id The content ID.
	 * @param int    $user_id The student ID.
	 */
	private function add_missing_progress_error( string $type, string $store, int $post_id, int $user_id ): void {
		$this->add_error(
			sprintf( '%s %s based progress not found.', ucfirst( $type ), $store ),
			array(
				$type . '_id' => $post_id,
				'user_id'     => $user_id,
				'source'      => $store,
			)
		);
	}

	/**
	 * Validate the quiz progress.
	 *
	 * @param int $quiz_id Quiz post ID.
	 */
	private function validate_quiz_progress( int $quiz_id ): void {
		$lesson_id = Sensei()->quiz->get_lesson_id( $quiz_id );

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$user_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->comments}
				WHERE comment_type = 'sensei_lesson_status'
				AND comment_post_ID = %d",
				$lesson_id
			)
		);
		if ( ! $user_ids ) {
			return;
		}

		$comments_based_repository = new Comments_Based_Quiz_Progress_Repository();
		$tables_based_repository   = new Tables_Based_Quiz_Progress_Repository( $wpdb );

		foreach ( $user_ids as $user_id ) {
			$comments_based_progress = $comments_based_repository->get( $quiz_id, $user_id );
			$tables_based_progress   = $tables_based_repository->get( $quiz_id, $user_id );

			if ( ! $comments_based_progress ) {
				$this->add_error(
					'Quiz comments based progress not found.',
					[
						'quiz_id' => $quiz_id,
						'user_id' => $user_id,
					]
				);
				continue;
			}

			if ( ! $tables_based_progress ) {
				$this->add_error(
					'Quiz tables based progress not found.',
					[
						'quiz_id' => $quiz_id,
						'user_id' => $user_id,
					]
				);
				continue;
			}

			$this->compare_progress( $comments_based_progress, $tables_based_progress );
		}
	}

	/**
	 * Compare the comments and tables based progress.
	 *
	 * @param object $comments_based_progress Comments based progress.
	 * @param object $tables_based_progress   Tables based progress.
	 */
	private function compare_progress( object $comments_based_progress, object $tables_based_progress ): void {
		$comments = $this->get_progress_data( $comments_based_progress );
		$tables   = $this->get_progress_data( $tables_based_progress );

		if ( $comments !== $tables ) {
			$this->add_error(
				'Data mismatch between comments and tables based progress.',
				array(
					array_merge( array( 'source' => 'comments' ), $comments ),
					array_merge( array( 'source' => 'tables' ), $tables ),
				)
			);
		}
	}

	/**
	 * Get the progress data.
	 *
	 * @param object $progress Progress.
	 *
	 * @return array
	 * @throws \InvalidArgumentException When invalid progress type is provided.
	 */
	private function get_progress_data( object $progress ): array {
		if ( $progress instanceof Course_Progress_Interface ) {
			$type    = 'course';
			$post_id = $progress->get_course_id();
		} elseif ( $progress instanceof Lesson_Progress_Interface ) {
			$type    = 'lesson';
			$post_id = $progress->get_lesson_id();
		} elseif ( $progress instanceof Quiz_Progress_Interface ) {
			$type    = 'quiz';
			$post_id = $progress->get_quiz_id();
		} else {
			throw new \InvalidArgumentException( 'Invalid progress type.' );
		}

		// Compare the same instant regardless of the timezone used by either store.
		$started_at   = $progress->get_started_at();
		$completed_at = $progress->get_completed_at();

		return array(
			'type'         => $type,
			'post_id'      => $post_id,
			'user_id'      => $progress->get_user_id(),
			'status'       => $progress->get_status(),
			'started_at'   => $started_at ? gmdate( 'Y-m-d H:i:s', $started_at->getTimestamp() ) : null,
			'completed_at' => $completed_at ? gmdate( 'Y-m-d H:i:s', $completed_at->getTimestamp() ) : null,
		);
	}
}

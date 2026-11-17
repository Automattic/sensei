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
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Comments_Based_Quiz_Progress;
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
			$this->validate_progress(
				'quiz',
				new Comments_Based_Quiz_Progress_Repository(),
				new Tables_Based_Quiz_Progress_Repository( $wpdb )
			);
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
			if ( 'quiz' === $type ) {
				$post_id = Sensei()->lesson->get_quiz_id_for_lesson_progress( $post_id );
				if ( ! $post_id ) {
					continue;
				}
			}

			$comments_progress = $comments_repository->get( $post_id, $user_id );
			if ( ! $comments_progress || $comments_progress->get_id() !== (int) $record->id ) {
				continue;
			}

			if ( $tables_repository->get( $post_id, $user_id ) ) {
				continue;
			}

			if ( ! $comments_progress instanceof Comments_Based_Quiz_Progress || $this->has_recorded_quiz_activity( $comments_progress ) ) {
				$this->add_missing_progress_error( $type, 'tables', $post_id, $user_id );
			}
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
	 * Whether the shared lesson comment contains evidence that the quiz was attempted.
	 *
	 * A lesson start or completion alone can appear as quiz progress in the comments repository.
	 *
	 * @param Comments_Based_Quiz_Progress $progress Comments-based quiz progress.
	 * @return bool
	 */
	private function has_recorded_quiz_activity( Comments_Based_Quiz_Progress $progress ): bool {
		return in_array(
			$progress->get_raw_status(),
			array(
				Quiz_Progress_Interface::STATUS_PASSED,
				Quiz_Progress_Interface::STATUS_FAILED,
				Quiz_Progress_Interface::STATUS_GRADED,
				Quiz_Progress_Interface::STATUS_UNGRADED,
			),
			true
		) || (bool) get_comment_meta( $progress->get_id(), 'questions_asked', true );
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

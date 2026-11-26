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
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Comments_Based_Lesson_Progress;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress_Interface;
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Comments_Based_Quiz_Progress;
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress_Interface;

/**
 * Class responsible for validation of the migrated progress data.
 *
 * @since 4.19.1
 */
class Progress_Validation {
	/**
	 * Errors.
	 *
	 * @var Validation_Error[]
	 */
	private array $errors = array();

	/**
	 * Non-failing validation warnings.
	 *
	 * @var Validation_Error[]
	 */
	private array $warnings = array();

	/**
	 * Run the validation.
	 *
	 * @internal
	 *
	 * @since 4.19.1
	 */
	public function run(): void {
		$this->errors   = array();
		$this->warnings = array();

		if ( ! $this->is_progress_migration_complete() ) {
			$this->add_error( 'The progress migration is not complete. Please run the progress migration first.' );
		}

		try {
			$this->validate_progress( 'course' );
			$this->validate_progress( 'lesson' );
			$this->validate_progress( 'quiz' );
		} catch ( RuntimeException $exception ) {
			$this->add_error( $exception->getMessage() );
		}
	}

	/**
	 * Inspect one stored identity using the validation comparison rules.
	 *
	 * @internal
	 * @since $$next-version$$
	 *
	 * @param string     $type    The progress type.
	 * @param int        $post_id The stored content ID.
	 * @param int        $user_id The student ID.
	 * @param array|null $records Shared rows for this student and content, populated by the readers.
	 * @return array Normalized copies, differences, and excluded fields.
	 */
	public function inspect( string $type, int $post_id, int $user_id, ?array &$records = null ): array {
		$this->errors   = array();
		$this->warnings = array();

		return $this->inspect_progress( $type, $post_id, $user_id, $records );
	}

	/**
	 * Inspect an identity without clearing findings from other identities.
	 *
	 * @param string     $type    The progress type.
	 * @param int        $post_id The content ID.
	 * @param int        $user_id The student ID.
	 * @param array|null $records Shared stored records.
	 * @return array The inspection result.
	 */
	private function inspect_progress( string $type, int $post_id, int $user_id, ?array &$records = null ): array {
		$records  = $records ?? array();
		$comments = Validation_Records::get_comments_progress( $type, $post_id, $user_id, $records );
		$tables   = Validation_Records::get_tables_progress( $type, $post_id, $user_id, $records );

		// Excluded fields stay visible in the output, but cannot become proposed repair changes.
		$ignored_fields = array();
		$cannot_verify  = isset( $records['unverifiable_trash_status'] );

		if ( isset( $records['unverifiable_trash_status'] ) ) {
			$this->add_error(
				'Original progress status could not be established for the trashed comment.',
				array(
					$type . '_id' => $post_id,
					'user_id'     => $user_id,
					'comment_id'  => $records['unverifiable_trash_status'],
				)
			);
		}

		if ( $cannot_verify ) {
			$ignored_fields = array( 'status', 'started_at', 'completed_at' );
		}

		// HPPS records quiz visits separately. The shared comment can also say passed
		// after an optional lesson is completed, without proving that its quiz was submitted.
		$has_expected_quiz_visit_difference = $tables instanceof Quiz_Progress_Interface
			&& ( ! $comments || $comments instanceof Comments_Based_Quiz_Progress )
			&& $this->has_expected_quiz_visit_difference( $tables, $comments, $records );

		// A shared comment may describe lesson activity before the quiz was attempted.
		// A missing HPPS quiz row is expected in that case.
		$has_only_lesson_activity = ! $tables && $comments instanceof Comments_Based_Quiz_Progress
			&& ! $this->has_recorded_quiz_activity( $comments, $records );

		// Only report progress mismatches outside these expected differences between stores.
		if ( ! $cannot_verify && ! $has_expected_quiz_visit_difference && ! $has_only_lesson_activity ) {
			if ( $comments && $tables ) {
				$this->compare_progress( $comments, $tables );

				// The original lesson start may be unverifiable, or these may be separate
				// lesson/quiz start events. Keep either date out of proposed repairs.
				$cannot_compare_start = $this->get_warnings() || ( 'quiz' === $type
					&& Quiz_Progress_Interface::STATUS_IN_PROGRESS === $comments->get_status()
					&& Quiz_Progress_Interface::STATUS_IN_PROGRESS === $tables->get_status()
				);
				if ( $cannot_compare_start ) {
					$ignored_fields[] = 'started_at';
				}
			} elseif ( $comments || $tables ) {
				$this->add_missing_progress_error( $type, $comments ? 'tables' : 'comments', $post_id, $user_id );
			}
		}

		return array(
			'comments'       => $comments ? array_merge(
				array( 'id' => $comments->get_id() ),
				$this->get_progress_data( $comments )
			) : null,
			'tables'         => $tables ? array_merge(
				array( 'id' => $tables->get_id() ),
				$this->get_progress_data( $tables )
			) : null,
			'errors'         => $this->errors,
			'warnings'       => $this->warnings,
			'ignored_fields' => $ignored_fields,
		);
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
	 * Get warnings about progress that could not be fully verified.
	 *
	 * @internal
	 * @since 4.28.0
	 *
	 * @return Validation_Error[]
	 */
	public function get_warnings(): array {
		return $this->warnings;
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
	 * Find progress missing from either store and compare records present in both.
	 *
	 * @param string $type The progress type.
	 */
	private function validate_progress( string $type ): void {
		$comment_type = 'course' === $type ? 'sensei_course_status' : 'sensei_lesson_status';
		$inspected    = array();

		foreach ( Validation_Records::get_comments( $comment_type ) as $record ) {
			$post_id = (int) $record->post_id;
			$user_id = (int) $record->user_id;
			if ( 'quiz' === $type ) {
				$post_id = Validation_Records::get_quiz_id( $post_id );
				if ( ! $post_id ) {
					continue;
				}
			}

			$key = $post_id . ':' . $user_id;
			if ( ! isset( $inspected[ $key ] ) ) {
				$this->inspect_progress( $type, $post_id, $user_id );
				$inspected[ $key ] = true;
			}
		}

		foreach ( Validation_Records::get_progress_rows( $type ) as $record ) {
			$post_id = (int) $record->post_id;
			$user_id = (int) $record->user_id;
			$key     = $post_id . ':' . $user_id;
			if ( ! isset( $inspected[ $key ] ) ) {
				$this->inspect_progress( $type, $post_id, $user_id );
				$inspected[ $key ] = true;
			}
		}
	}

	/**
	 * Allow quiz progress that the shared lesson comment cannot represent independently.
	 *
	 * Opening a quiz only creates a table row. Completing an optional lesson can mark
	 * its comment as passed while the quiz remains in progress. A grade in either
	 * store provides evidence that the quiz progress should also have been updated.
	 *
	 * @param Quiz_Progress_Interface           $tables_progress   Quiz progress in the tables.
	 * @param Comments_Based_Quiz_Progress|null $comments_progress Quiz progress from the lesson comment.
	 * @param array|null                        $records           Shared rows for one targeted inspection.
	 * @return bool
	 */
	private function has_expected_quiz_visit_difference( Quiz_Progress_Interface $tables_progress, ?Comments_Based_Quiz_Progress $comments_progress, ?array &$records = null ): bool {
		if ( Quiz_Progress_Interface::STATUS_IN_PROGRESS !== $tables_progress->get_status() ) {
			return false;
		}

		$quiz_id = $tables_progress->get_quiz_id();
		if ( $comments_progress ) {
			$comment_says_passed = Quiz_Progress_Interface::STATUS_PASSED === $comments_progress->get_status();
			$pass_required       = (bool) Validation_Records::get_metadata( 'post', $quiz_id, '_pass_required' );

			// Only a passed comment for an optional quiz could come from completing the lesson alone.
			// Otherwise, return false so the caller continues normal validation.
			if ( ! $comment_says_passed || $pass_required ) {
				return false;
			}
		}

		$submission = Validation_Records::get_tables_submission( $quiz_id, $tables_progress->get_user_id(), $records );

		// Saved answers should have a lesson comment even if quiz progress is still in progress.
		if ( ! $comments_progress ) {
			return null === $submission;
		}

		// The shared passed status alone cannot distinguish a completed lesson from a submitted quiz.
		$comment_id        = $comments_progress->get_id();
		$has_comment_grade = Validation_Records::get_metadata( 'comment', $comment_id, 'questions_asked', $records )
			&& is_numeric( Validation_Records::get_metadata( 'comment', $comment_id, 'grade', $records ) );

		return ( ! $submission || null === $submission->get_final_grade() ) && ! $has_comment_grade;
	}

	/**
	 * Whether the shared lesson comment contains evidence that the quiz was attempted.
	 *
	 * A lesson start or completion alone can appear as quiz progress in the comments repository.
	 *
	 * @param Comments_Based_Quiz_Progress $progress Comments-based quiz progress.
	 * @param array|null                   $records  Shared rows for one targeted inspection.
	 * @return bool
	 */
	private function has_recorded_quiz_activity( Comments_Based_Quiz_Progress $progress, ?array &$records = null ): bool {
		if ( Quiz_Progress_Interface::STATUS_PASSED === $progress->get_status()
			&& ! Validation_Records::get_metadata( 'post', $progress->get_quiz_id(), '_pass_required' )
		) {
			// Completing a lesson can record a quiz pass without any answers.
			return (bool) Validation_Records::get_metadata( 'comment', $progress->get_id(), 'questions_asked', $records );
		}

		return in_array(
			$progress->get_raw_status(),
			array(
				Quiz_Progress_Interface::STATUS_PASSED,
				Quiz_Progress_Interface::STATUS_FAILED,
				Quiz_Progress_Interface::STATUS_GRADED,
				Quiz_Progress_Interface::STATUS_UNGRADED,
			),
			true
		) || (bool) Validation_Records::get_metadata( 'comment', $progress->get_id(), 'questions_asked', $records );
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

		$comments_to_compare = $comments;
		if ( 'quiz' === $comments['type']
			&& Quiz_Progress_Interface::STATUS_IN_PROGRESS === $comments['status']
			&& Quiz_Progress_Interface::STATUS_IN_PROGRESS === $tables['status']
		) {
			// Before submission, the lesson start in comments and the quiz start in HPPS are separate events.
			$comments_to_compare['started_at'] = $tables['started_at'];
		}

		if ( $this->has_unverifiable_lesson_start( $comments, $tables ) ) {
			// A matching quiz start explains the difference, but the lost lesson start means
			// we cannot verify that the HPPS lesson date is correct. Report a warning instead.
			$this->warnings[] = new Validation_Error(
				'Lesson start date could not be verified because the shared comment now records the quiz start.',
				array(
					'lesson_id' => $comments['post_id'],
					'user_id'   => $comments['user_id'],
				)
			);

			// Exclude only this date from comparison; still check status and completion.
			// This changes the comparison data without saving either stored record.
			$comments_to_compare['started_at'] = $tables['started_at'];
		}

		if ( $comments_to_compare !== $tables ) {
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
	 * Check whether a quiz start explains a later lesson start in comments.
	 *
	 * @param array $comments Comments progress data.
	 * @param array $tables   Tables progress data.
	 * @return bool
	 */
	private function has_unverifiable_lesson_start( array $comments, array $tables ): bool {
		// Comments share one start date for the lesson and quiz. Saving quiz progress can overwrite
		// the earlier lesson start, while HPPS keeps the two dates separately.
		if ( 'lesson' !== $comments['type']
			|| null === $comments['started_at']
			|| null === $tables['started_at']
			|| $tables['started_at'] >= $comments['started_at']
		) {
			return false;
		}

		$quiz_id       = Validation_Records::get_quiz_id( $comments['post_id'] );
		$quiz_progress = $quiz_id ? Validation_Records::get_tables_progress( 'quiz', $quiz_id, $comments['user_id'] ) : null;
		$quiz_start    = $quiz_progress ? $quiz_progress->get_started_at() : null;

		return $quiz_start && gmdate( 'Y-m-d H:i:s', $quiz_start->getTimestamp() ) === $comments['started_at'];
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

		if ( $progress instanceof Comments_Based_Lesson_Progress && 'failed' === $progress->get_raw_status() ) {
			// The model's failed-lesson normalization reads quiz settings through cached WordPress APIs.
			$quiz_id       = Validation_Records::get_quiz_id( $post_id );
			$pass_required = $quiz_id && Validation_Records::get_metadata( 'post', $quiz_id, '_pass_required' );

			$status = $quiz_id && ! $pass_required
				? Lesson_Progress_Interface::STATUS_COMPLETE
				: Lesson_Progress_Interface::STATUS_IN_PROGRESS;
		} else {
			$status = $progress->get_status();
		}

		return array(
			'type'         => $type,
			'post_id'      => $post_id,
			'user_id'      => $progress->get_user_id(),
			'status'       => $status,
			'started_at'   => $started_at ? gmdate( 'Y-m-d H:i:s', $started_at->getTimestamp() ) : null,
			'completed_at' => $completed_at ? gmdate( 'Y-m-d H:i:s', $completed_at->getTimestamp() ) : null,
		);
	}
}

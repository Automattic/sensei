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
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Tables_Based_Submission_Repository;
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
	 * Get warnings about progress that could not be fully verified.
	 *
	 * @internal
	 * @since $$next-version$$
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
	 * @param string $type                The progress type.
	 * @param object $comments_repository The comments repository.
	 * @param object $tables_repository   The tables repository.
	 * @psalm-param Progress_Repository $comments_repository
	 * @psalm-param Progress_Repository $tables_repository
	 */
	private function validate_progress( string $type, object $comments_repository, object $tables_repository ): void {
		$comment_type = 'course' === $type ? 'sensei_course_status' : 'sensei_lesson_status';

		// Scan comments for progress that is missing from the tables.
		foreach ( Validation_Records::get_comments( $comment_type ) as $record ) {
			$post_id = (int) $record->post_id;
			$user_id = (int) $record->user_id;
			if ( 'quiz' === $type ) {
				$post_id = (int) Sensei()->lesson->lesson_quizzes( $post_id );
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

			// Always report missing course or lesson progress. Quiz progress needs evidence of activity because it shares the lesson comment.
			if ( ! $comments_progress instanceof Comments_Based_Quiz_Progress || $this->has_recorded_quiz_activity( $comments_progress ) ) {
				$this->add_missing_progress_error( $type, 'tables', $post_id, $user_id );
			}
		}

		// For each HPPS row, report missing comments or compare progress for the same student and course, lesson, or quiz.
		foreach ( Validation_Records::get_progress_rows( $type ) as $record ) {
			$post_id           = (int) $record->post_id;
			$user_id           = (int) $record->user_id;
			$comments_progress = $comments_repository->get( $post_id, $user_id );
			$tables_progress   = $tables_repository->get( $post_id, $user_id );
			if ( ! $tables_progress ) {
				continue;
			}

			if ( $tables_progress instanceof Quiz_Progress_Interface
				&& ( ! $comments_progress || $comments_progress instanceof Comments_Based_Quiz_Progress )
				&& $this->has_expected_quiz_visit_difference( $tables_progress, $comments_progress )
			) {
				continue;
			}

			if ( ! $comments_progress ) {
				$this->add_missing_progress_error( $type, 'comments', $post_id, $user_id );
				continue;
			}

			$this->compare_progress( $comments_progress, $tables_progress );
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
	 * @return bool
	 */
	private function has_expected_quiz_visit_difference( Quiz_Progress_Interface $tables_progress, ?Comments_Based_Quiz_Progress $comments_progress ): bool {
		if ( Quiz_Progress_Interface::STATUS_IN_PROGRESS !== $tables_progress->get_status() ) {
			return false;
		}

		$quiz_id = $tables_progress->get_quiz_id();
		if ( $comments_progress ) {
			$comment_says_passed = Quiz_Progress_Interface::STATUS_PASSED === $comments_progress->get_status();
			$pass_required       = (bool) get_post_meta( $quiz_id, '_pass_required', true );

			// Only a passed comment for an optional quiz could come from completing the lesson alone.
			// Otherwise, return false so the caller continues normal validation.
			if ( ! $comment_says_passed || $pass_required ) {
				return false;
			}
		}

		global $wpdb;
		$submission = ( new Tables_Based_Submission_Repository( $wpdb ) )->get( $quiz_id, $tables_progress->get_user_id() );

		// Saved answers should have a lesson comment even if quiz progress is still in progress.
		if ( ! $comments_progress ) {
			return null === $submission;
		}

		// The shared passed status alone cannot distinguish a completed lesson from a submitted quiz.
		$comment_id        = $comments_progress->get_id();
		$has_comment_grade = get_comment_meta( $comment_id, 'questions_asked', true )
			&& is_numeric( get_comment_meta( $comment_id, 'grade', true ) );

		return ( ! $submission || null === $submission->get_final_grade() ) && ! $has_comment_grade;
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
		if ( Quiz_Progress_Interface::STATUS_PASSED === $progress->get_status() && ! get_post_meta( $progress->get_quiz_id(), '_pass_required', true ) ) {
			// Completing a lesson can record a quiz pass without any answers.
			return (bool) get_comment_meta( $progress->get_id(), 'questions_asked', true );
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
		global $wpdb;

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

		// Comments share one start date for the lesson and quiz. Saving quiz progress can overwrite
		// the earlier lesson start, while HPPS keeps the two dates separately.
		if ( 'lesson' === $comments['type']
			&& null !== $comments['started_at']
			&& null !== $tables['started_at']
			&& $tables['started_at'] < $comments['started_at']
		) {
			$quiz_id       = (int) Sensei()->lesson->lesson_quizzes( $comments['post_id'] );
			$quiz_progress = $quiz_id ? ( new Tables_Based_Quiz_Progress_Repository( $wpdb ) )->get( $quiz_id, $comments['user_id'] ) : null;
			$quiz_start    = $quiz_progress ? $quiz_progress->get_started_at() : null;

			// A matching quiz start explains the difference, but the lost lesson start means
			// we cannot verify that the HPPS lesson date is correct. Report a warning instead.
			if ( $quiz_start && gmdate( 'Y-m-d H:i:s', $quiz_start->getTimestamp() ) === $comments['started_at'] ) {
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

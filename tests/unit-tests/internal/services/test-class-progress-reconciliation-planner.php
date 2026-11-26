<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName -- Follow the repository test-class filename convention.
/**
 * Tests for planning changes from stored progress data.
 *
 * @package sensei-tests
 */

namespace SenseiTest\Internal\Services;

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- These tests need real database copies with different values.

use Sensei\Internal\Services\Progress_Reconciliation_Planner;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Tables_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Tables_Based_Grade_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Tables_Based_Submission_Repository;

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-progress-reconciliation-test-helpers.php';

/**
 * Check course, lesson and quiz plans in both directions without writing repairs.
 *
 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner
 */
class Progress_Reconciliation_Planner_Test extends \WP_UnitTestCase {

	use \Sensei_Progress_Reconciliation_Test_Helpers;

	/**
	 * Create the database records used by each planning test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->set_up_reconciliation_fixtures();
	}

	/**
	 * Restore storage settings before WordPress rolls back the test records.
	 */
	public function tearDown(): void {
		$this->tear_down_reconciliation_fixtures();
		parent::tearDown();
	}

	/**
	 * Find course, lesson and submission identities from both stores.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::discover
	 */
	public function testDiscover_RecordsWereStoredInBothStores_ResumesWithinTheBatchLimit(): void {
		// Arrange.
		global $wpdb;
		$this->select_source( 'comments' );
		$expected = array();
		foreach ( range( 1, 3 ) as $unused ) {
			$course_id  = $this->post( 'course' );
			$expected[] = array(
				'type'       => 'course',
				'content_id' => $course_id,
				'student_id' => $this->student_id,
				'quiz_id'    => 0,
			);
			$this->progress( 'course', $course_id, 'comments', 'in-progress' );
			$this->progress( 'course', $course_id, 'tables', 'in-progress' );
		}
		$lesson_id = $this->post( 'lesson' );
		$quiz_id   = $this->post( 'quiz', $lesson_id );
		$this->progress( 'lesson', $lesson_id, 'comments', 'in-progress' );
		$submission_lesson = $this->post( 'lesson' );
		$submission_quiz   = $this->post( 'quiz', $submission_lesson );
		( new Tables_Based_Submission_Repository( $wpdb ) )->create( $submission_quiz, $this->student_id );
		$service = new Progress_Reconciliation_Planner();
		$cursor  = array();
		$seen    = array();
		$sizes   = array();

		// Act.
		for ( $attempt = 0; $attempt < 20; ++$attempt ) {
			$page    = $service->discover( $cursor, 1 );
			$cursor  = $page['cursor'];
			$sizes[] = count( $page['identities'] );
			foreach ( $page['identities'] as $identity ) {
				if ( $identity['student_id'] === $this->student_id ) {
					$seen[] = $identity;
				}
			}
			if ( $page['complete'] ) {
				break;
			}
		}

		// Assert.
		self::assertTrue( $page['complete'], 'A persisted cursor must reach scan completion.' );
		self::assertSame( 1, max( $sizes ), 'Each discovery call must respect the requested physical row bound.' );
		self::assertSame(
			array_merge(
				$expected,
				array(
					array(
						'type'       => 'lesson',
						'content_id' => $lesson_id,
						'student_id' => $this->student_id,
						'quiz_id'    => $quiz_id,
					),
				),
				$expected,
				array(
					array(
						'type'       => 'lesson',
						'content_id' => $submission_lesson,
						'student_id' => $this->student_id,
						'quiz_id'    => $submission_quiz,
					),
				)
			),
			$seen,
			'Discover course and lesson comments, table progress, and submissions without progress rows.'
		);
	}

	/**
	 * Do not change a shared comment when the lesson and quiz need different statuses.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_LessonAndQuizStatusesConflicted_PreservesTheSharedComment(): void {
		// Arrange.
		$this->select_source( 'tables' );
		$lesson_id = $this->post( 'lesson' );
		$quiz_id   = $this->post( 'quiz', $lesson_id );
		$this->progress( 'lesson', $lesson_id, 'comments', 'passed' );
		$this->progress( 'lesson', $lesson_id, 'tables', 'in-progress' );
		$this->progress( 'quiz', $quiz_id, 'tables', 'passed' );
		$before = $this->source_rows( 'comments', $lesson_id );

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'lesson', $lesson_id, $this->student_id, 'tables' );

		// Assert.
		self::assertSame( 'unresolved', $result['outcome'], wp_json_encode( $result ) );
		self::assertSame( $before, $this->source_rows( 'comments', $lesson_id ), 'Planning must not modify the shared comment.' );
		self::assertSame( array(), $result['changes'], 'Incompatible shared status and completion targets must not produce comment changes.' );
	}

	/**
	 * An absent completion must not become a dated completion through a status change.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_CourseCompletionWasAbsent_ProposesOnlyTheStart(): void {
		// Arrange.
		global $wpdb;
		$this->select_source( 'tables' );
		$course_id = $this->post( 'course' );
		$this->progress( 'course', $course_id, 'comments', 'in-progress' );
		$progress_id = $this->progress( 'course', $course_id, 'tables', 'complete' );
		$wpdb->update(
			$wpdb->prefix . 'sensei_lms_progress',
			array(
				'started_at'   => '2024-01-03 12:00:00',
				'completed_at' => null,
			),
			array( 'id' => $progress_id )
		);

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'course', $course_id, $this->student_id, 'tables' );

		// Assert.
		self::assertSame( array( array( 'start' => '2024-01-03 12:00:00' ) ), array_column( $result['changes'], 'data' ), 'Only the safe start change should be proposed.' );
		self::assertSame( 'unresolved', $result['outcome'], 'A completed comment needs a completion date.' );
		self::assertContains( 'Source status and completion cannot share one comment.', $result['unresolved'], 'Explain why the status and completion cannot be copied together.' );
	}

	/**
	 * A missing comment cannot represent a completed course with no completion date.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_MissingCourseCopyHadNoCompletion_WithholdsItsCreation(): void {
		// Arrange.
		global $wpdb;
		$this->select_source( 'tables' );
		$course_id   = $this->post( 'course' );
		$progress_id = $this->progress( 'course', $course_id, 'tables', 'complete' );
		$wpdb->update( $wpdb->prefix . 'sensei_lms_progress', array( 'completed_at' => null ), array( 'id' => $progress_id ) );

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'course', $course_id, $this->student_id, 'tables' );

		// Assert.
		self::assertSame( array(), $result['changes'], 'Creating a completed comment would invent its completion date.' );
		self::assertSame( 'unresolved', $result['outcome'], 'A missing copy with an unknown completion date needs review.' );
		self::assertContains( 'Source status and completion cannot share one comment.', $result['unresolved'], 'Explain why the status and completion cannot be copied together.' );
	}

	/**
	 * A failed optional quiz must not hide the completed lesson date.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_OptionalQuizFailed_WithholdsTheSharedStatusChange(): void {
		// Arrange.
		$this->select_source( 'tables' );
		$lesson_id = $this->post( 'lesson' );
		$quiz_id   = $this->post( 'quiz', $lesson_id );
		update_post_meta( $quiz_id, '_pass_required', '' );
		$this->progress( 'lesson', $lesson_id, 'comments', 'passed' );
		$this->progress( 'lesson', $lesson_id, 'tables', 'complete' );
		$this->progress( 'quiz', $quiz_id, 'tables', 'failed' );

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'lesson', $lesson_id, $this->student_id, 'tables' );

		// Assert.
		self::assertSame( array(), $result['changes'], 'Changing the shared status to failed would hide the lesson completion.' );
		self::assertSame( 'unresolved', $result['outcome'], 'The two completion states cannot share one comment.' );
		self::assertContains( 'Source status and completion cannot share one comment.', $result['unresolved'], 'Explain why the status and completion cannot be copied together.' );
	}

	/**
	 * A status must not expose a third completion time when the two source times disagree.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_LessonAndQuizCompletionTimesDiffered_WithholdsTheSharedStatusChange(): void {
		// Arrange.
		global $wpdb;
		$this->select_source( 'tables' );
		$lesson_id = $this->post( 'lesson' );
		$quiz_id   = $this->post( 'quiz', $lesson_id );
		$comment   = $this->progress( 'lesson', $lesson_id, 'comments', 'in-progress' );
		$this->progress( 'lesson', $lesson_id, 'tables', 'complete' );
		$quiz_progress = $this->progress( 'quiz', $quiz_id, 'tables', 'passed' );
		$wpdb->update( $wpdb->prefix . 'sensei_lms_progress', array( 'completed_at' => '2024-01-03 12:00:00' ), array( 'id' => $quiz_progress ) );
		$wpdb->update(
			$wpdb->comments,
			array(
				'comment_date'     => '2024-01-04 12:00:00',
				'comment_date_gmt' => '2024-01-04 12:00:00',
			),
			array( 'comment_ID' => $comment )
		);

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'lesson', $lesson_id, $this->student_id, 'tables' );

		// Assert.
		self::assertSame( array(), $result['changes'], 'A passed status would expose the destination date, which matches neither source completion.' );
		self::assertSame( 'unresolved', $result['outcome'], 'Conflicting source completion times must remain unresolved.' );
		self::assertContains( 'Source status and completion cannot share one comment.', $result['unresolved'], 'Explain why the status and completion cannot be copied together.' );
	}

	/**
	 * Feedback omitted from grade models must still prevent full equivalence.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_SourceFeedbackHadNoPoints_ReportsItAsUnresolved(): void {
		// Arrange.
		global $wpdb;
		$this->select_source( 'comments' );
		list( $lesson_id, $quiz_id, $question, $comment ) = $this->create_quiz_progress();
		update_comment_meta( $comment, 'questions_asked', (string) $question );
		update_comment_meta( $comment, 'quiz_answers', array( $question => 'answer' ) );
		update_comment_meta( $comment, 'quiz_answers_feedback', array( $question => 'surviving feedback' ) );
		$submission = ( new Tables_Based_Submission_Repository( $wpdb ) )->create( $quiz_id, $this->student_id );
		( new Tables_Based_Answer_Repository( $wpdb ) )->create( $submission, $question, 'answer' );

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'lesson', $lesson_id, $this->student_id, 'comments' );

		// Assert.
		self::assertFalse( $result['equivalent'], 'Feedback without points must remain part of the equivalence decision.' );
		self::assertContains( 'Feedback for question ' . $question . ' has no comment grade and is preserved for review.', $result['unresolved'], 'Identify the feedback that the grade model cannot represent.' );
		self::assertSame( array(), $result['changes'], 'Feedback must not acquire invented grade points.' );
	}

	/**
	 * Protect surviving feedback while still proposing an independent final-grade update.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_DestinationFeedbackHadNoPoints_PreservesItAndProposesTheFinalGrade(): void {
		// Arrange.
		global $wpdb;
		$this->select_source( 'tables' );
		list( $lesson_id, $quiz_id, $question, $comment ) = $this->create_quiz_progress();
		update_comment_meta( $comment, 'questions_asked', (string) $question );
		update_comment_meta( $comment, 'quiz_answers', array( $question => 'answer' ) );
		update_comment_meta( $comment, 'quiz_answers_feedback', array( $question => 'surviving feedback' ) );
		update_comment_meta( $comment, 'grade', 20 );
		$submission = ( new Tables_Based_Submission_Repository( $wpdb ) )->create( $quiz_id, $this->student_id, 80 );
		$answer     = ( new Tables_Based_Answer_Repository( $wpdb ) )->create( $submission, $question, 'answer' );
		( new Tables_Based_Grade_Repository( $wpdb ) )->create( $submission, $answer, $question, 1 );

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'lesson', $lesson_id, $this->student_id, 'tables' );

		// Assert.
		self::assertSame( array( 'submission' ), array_column( $result['changes'], 'kind' ), 'The grade proposal that would erase feedback must be withheld.' );
		self::assertSame( array( 'final_grade' => 80.0 ), $result['changes'][0]['data'], 'The independent final grade should still be proposed.' );
		self::assertContains( 'Feedback for question ' . $question . ' has no comment grade and is preserved for review.', $result['unresolved'], 'Report the feedback that prevented a full grade copy.' );
	}

	/**
	 * A missing submission marker must not hide surviving quiz collections.
	 *
	 * @dataProvider directions
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_QuizCollectionsHadNoSubmissionMarker_PreservesThemForReview( string $source ): void {
		// Arrange.
		$this->select_source( $source );
		list( $lesson_id, $quiz_id, $question, $comment ) = $this->create_quiz_progress();
		update_comment_meta( $comment, 'quiz_answers', array( $question => 'surviving answer' ) );
		update_comment_meta( $comment, 'quiz_grades', array( $question => 2 ) );
		update_comment_meta( $comment, 'quiz_answers_feedback', array( $question => 'surviving feedback' ) );

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'lesson', $lesson_id, $this->student_id, $source );

		// Assert.
		self::assertFalse( $result['equivalent'], 'A missing questions_asked marker must not hide surviving quiz collections.' );
		self::assertContains( 'Quiz data without a submission question marker is preserved for review.', $result['unresolved'], 'Explain why the incomplete submission cannot be copied.' );
		self::assertSame( array(), $result['changes'], 'The unexplained surviving collections must remain untouched.' );
	}

	/**
	 * A missing submission marker must not allow a null grade to replace a surviving zero.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_ZeroFinalGradeHadNoSubmissionMarker_WithholdsItsReplacement(): void {
		// Arrange.
		global $wpdb;
		$this->select_source( 'tables' );
		list( $lesson_id, $quiz_id, $question, $comment ) = $this->create_quiz_progress();
		update_comment_meta( $comment, 'grade', 0 );
		$submission = ( new Tables_Based_Submission_Repository( $wpdb ) )->create( $quiz_id, $this->student_id );
		( new Tables_Based_Answer_Repository( $wpdb ) )->create( $submission, $question, 'answer' );

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'lesson', $lesson_id, $this->student_id, 'tables' );

		// Assert.
		self::assertSame( array(), $result['changes'], 'The incomplete destination submission must not receive a null replacement for its zero grade.' );
		self::assertContains( 'Quiz data without a submission question marker is preserved for review.', $result['unresolved'], 'The surviving raw grade needs review before replacing its submission.' );
	}

	/**
	 * A missing source grade must not erase a destination grade, including zero.
	 *
	 * @dataProvider absent_final_grades
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_SourceFinalGradeWasAbsent_PreservesZeroAndReportsLogicalScope( string $source, array $comment_grades, ?float $table_grade, string $destination ): void {
		// Arrange.
		global $wpdb;
		$this->select_source( $source );
		$lesson_id = $this->post( 'lesson' );
		$quiz_id   = $this->post( 'quiz', $lesson_id );
		$question  = $this->post( 'question' );
		$comment   = $this->progress( 'lesson', $lesson_id, 'comments', 'in-progress' );
		$this->progress( 'lesson', $lesson_id, 'tables', 'in-progress' );
		update_comment_meta( $comment, 'questions_asked', (string) $question );
		update_comment_meta( $comment, 'quiz_answers', array( $question => 'answer' ) );
		foreach ( $comment_grades as $key => $value ) {
			update_comment_meta( $comment, $key, $value );
		}
		$submission = ( new Tables_Based_Submission_Repository( $wpdb ) )->create( $quiz_id, $this->student_id, $table_grade );
		( new Tables_Based_Answer_Repository( $wpdb ) )->create( $submission, $question, 'answer' );

		$before = $this->source_rows( $destination, $lesson_id );

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'lesson', $lesson_id, $this->student_id, $source );

		// Assert.
		self::assertSame( 'unresolved', $result['outcome'], wp_json_encode( $result ) );
		$submission_changes = array_values(
			array_filter(
				$result['changes'],
				static function ( array $change ): bool {
					return 'submission' === $change['kind'];
				}
			)
		);
		self::assertSame( array(), $submission_changes, 'An absent source final grade must not propose replacing the surviving zero grade.' );
		self::assertContains(
			array(
				'kind'    => 'final_grade',
				'quiz_id' => $quiz_id,
			),
			$result['destination_only'],
			'Deletion follow-up needs a logical final-grade scope attached to the canonical bundle.'
		);
		self::assertSame( $before, $this->source_rows( $destination, $lesson_id ), 'The stored destination grade must remain unchanged.' );
	}

	/**
	 * Do not propose copies for a student or content that was deleted.
	 *
	 * @dataProvider missing_identity_parts
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_ContentOrStudentWasMissing_PreservesOrphansForReview( string $missing ): void {
		// Arrange.
		global $wpdb;
		$this->select_source( 'comments' );
		$course_id = $this->post( 'course' );
		$this->progress( 'course', $course_id, 'comments', 'complete' );
		$ids = array(
			'posts' => $course_id,
			'users' => $this->student_id,
		);
		$wpdb->delete( $wpdb->$missing, array( 'ID' => $ids[ $missing ] ) );
		$before = $this->source_rows( 'comments', $course_id );

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'course', $course_id, $this->student_id, 'comments' );

		// Assert.
		self::assertSame( 'unresolved', $result['outcome'], wp_json_encode( $result ) );
		self::assertSame( $before, $this->source_rows( 'comments', $course_id ), 'Orphaned source data must remain untouched.' );
		self::assertSame( array(), $this->source_rows( 'tables', $course_id ), 'An orphan must not manufacture a destination copy.' );
		self::assertSame( array(), $result['changes'], 'Missing content or student identity must prevent orphan repair proposals.' );
	}

	/**
	 * Stop when the supplied source does not match the saved setting.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_SavedSourceDiffered_StopsWithoutWritingEitherStore(): void {
		// Arrange.
		$this->select_source( 'tables' );
		$course_id = $this->post( 'course' );
		$this->progress( 'course', $course_id, 'comments', 'complete' );
		$before = $this->source_rows( 'comments', $course_id );

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'course', $course_id, $this->student_id, 'comments' );

		// Assert.
		self::assertSame( 'conflicting', $result['outcome'], wp_json_encode( $result ) );
		self::assertSame( $before, $this->source_rows( 'comments', $course_id ), 'A changed source policy must not mutate comments.' );
		self::assertSame( array(), $this->source_rows( 'tables', $course_id ), 'A changed source policy must not copy to tables.' );
	}

	/**
	 * Database read failures return a failed result without proposing any copies.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_ProgressReadFailed_ReturnsFailedWithoutProposals(): void {
		// Arrange.
		global $wpdb;
		$this->select_source( 'comments' );
		$course_id = $this->post( 'course' );
		$this->progress( 'course', $course_id, 'comments', 'complete' );
		$comments_table  = $wpdb->comments;
		$suppress_errors = $wpdb->suppress_errors();
		// A missing table causes a real SQL failure without changing the stored fixtures.
		$wpdb->comments .= '_reconciliation_missing';

		// Act.
		try {
			$result = ( new Progress_Reconciliation_Planner() )->inspect( 'course', $course_id, $this->student_id, 'comments' );
		} finally {
			$wpdb->comments = $comments_table;
			$wpdb->suppress_errors( $suppress_errors );
		}

		// Assert.
		self::assertSame( 'failed', $result['outcome'], 'A persisted read failure must remain an operational failure result.' );
		self::assertSame( 'Could not read sensei_course_status comment during validation.', $result['error'], 'Keep the failed read identifiable.' );
		self::assertSame( array(), $result['changes'], 'A failed read must not produce copy proposals.' );
	}

	/**
	 * Unexpected reader type errors must reach the caller for diagnosis.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_StoredAnswerHadAnUnexpectedType_PropagatesTheTypeError(): void {
		// Arrange.
		$this->select_source( 'comments' );
		list( $lesson_id, $quiz_id, $question, $comment ) = $this->create_quiz_progress();
		update_comment_meta( $comment, 'questions_asked', (string) $question );
		update_comment_meta( $comment, 'quiz_answers', array( $question => array( 'unexpected value' ) ) );
		$error = null;

		// Act.
		try {
			( new Progress_Reconciliation_Planner() )->inspect( 'quiz', $quiz_id, $this->student_id, 'comments' );
		} catch ( \TypeError $exception ) {
			$error = $exception;
		}

		// Assert.
		self::assertInstanceOf( \TypeError::class, $error, 'Unexpected PHP errors must reach the caller instead of becoming failed results.' );
		self::assertStringContainsString( 'array given', $error->getMessage(), 'The propagated error must identify the unexpected stored type.' );
	}

	/**
	 * Date proposals use the persisted timezone even when this request cached older options.
	 *
	 * @dataProvider saved_timezones
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_TimezoneOptionsWereCached_ProposesDatesUsingSavedTimezone( string $source, string $timezone, float $offset, string $comment_start, string $table_start, string $field, string $expected ): void {
		// Arrange.
		global $wpdb;
		$this->select_source( $source );
		$original_timezone = get_option( 'timezone_string' );
		$original_offset   = get_option( 'gmt_offset' );
		update_option( 'timezone_string', 'UTC' );
		update_option( 'gmt_offset', 0 );
		$course_id = $this->post( 'course' );
		$comment   = $this->progress( 'course', $course_id, 'comments', 'in-progress' );
		$progress  = $this->progress( 'course', $course_id, 'tables', 'in-progress' );
		update_comment_meta( $comment, 'start', $comment_start );
		$wpdb->update( $wpdb->prefix . 'sensei_lms_progress', array( 'started_at' => $table_start ), array( 'id' => $progress ) );
		$wpdb->update( $wpdb->options, array( 'option_value' => $timezone ), array( 'option_name' => 'timezone_string' ) );
		$wpdb->update( $wpdb->options, array( 'option_value' => $offset ), array( 'option_name' => 'gmt_offset' ) );

		// Act.
		try {
			$result = ( new Progress_Reconciliation_Planner() )->inspect( 'course', $course_id, $this->student_id, $source );
		} finally {
			wp_cache_delete( 'alloptions', 'options' );
			wp_cache_delete( 'timezone_string', 'options' );
			wp_cache_delete( 'gmt_offset', 'options' );
			update_option( 'timezone_string', $original_timezone );
			update_option( 'gmt_offset', $original_offset );
		}

		// Assert.
		self::assertSame( $expected, $result['changes'][0]['data'][ $field ], 'Convert the proposed start using the saved timezone, including fractional offsets.' );
	}

	/**
	 * Keep destination progress when the source copy is missing.
	 *
	 * @dataProvider opposite_stores
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_SourceWasAbsent_PreservesDestinationOnlyProgress( string $source, string $destination ): void {
		// Arrange.
		$this->select_source( $source );
		$course_id = $this->post( 'course' );
		$this->progress( 'course', $course_id, $destination, 'complete' );
		$before = $this->source_rows( $destination, $course_id );

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'course', $course_id, $this->student_id, $source );

		// Assert.
		self::assertSame( 'unresolved', $result['outcome'], wp_json_encode( $result ) );
		self::assertFalse( $result['applied'], 'Missing deletion evidence must not produce a write.' );
		self::assertSame( array(), $result['changes'], 'Source absence must not propose replacing surviving progress.' );
		self::assertSame( $before, $this->source_rows( $destination, $course_id ), 'Every destination-only progress value must remain untouched.' );
	}

	/**
	 * Plan from the current database values, even if discovery and cached reads are old.
	 *
	 * @dataProvider changed_course_sources
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_CourseChangedAfterDiscovery_ProposesCurrentSourceWithoutWriting( string $source, string $post_column, array $changed_values, string $status_field ): void {
		// Arrange.
		global $wpdb;
		$this->select_source( $source );
		$course_id = $this->post( 'course' );
		$this->progress( 'course', $course_id, 'comments', 'in-progress' );
		$this->progress( 'course', $course_id, 'tables', 'in-progress' );
		Sensei()->course_progress_repository->get( $course_id, $this->student_id );
		$planner = new Progress_Reconciliation_Planner();
		$planner->discover( array(), 1 );
		$tables = array(
			'comments' => $wpdb->comments,
			'tables'   => $wpdb->prefix . 'sensei_lms_progress',
		);
		$wpdb->update(
			$tables[ $source ],
			$changed_values,
			array(
				$post_column => $course_id,
				'user_id'    => $this->student_id,
			)
		);
		$comments_before = $this->source_rows( 'comments', $course_id );
		$tables_before   = $this->source_rows( 'tables', $course_id );
		$actions         = did_action( 'sensei_course_status_updated' );

		// Act.
		$result = $planner->inspect( 'course', $course_id, $this->student_id, $source );

		// Assert.
		self::assertSame( 'complete', $result['changes'][0]['data'][ $status_field ], 'Planning must use the persisted change made after discovery.' );
		self::assertFalse( $result['supported_fields_verified'], 'A proposed supported repair remains unverified.' );
		self::assertSame( $comments_before, $this->source_rows( 'comments', $course_id ), 'Planning must leave comments unchanged.' );
		self::assertSame( $tables_before, $this->source_rows( 'tables', $course_id ), 'Planning must leave tables unchanged.' );
		self::assertSame( $actions, did_action( 'sensei_course_status_updated' ), 'Planning must not emit application-save actions.' );
	}

	/**
	 * Reversible trash state must not replace or erase saved completion progress.
	 *
	 * @dataProvider course_trash_states
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_CourseHadReversibleTrashState_PreservesProgressForRestoration( string $source, string $post_status, string $comment_status ): void {
		// Arrange.
		global $wpdb;
		$this->select_source( $source );
		$course_id = $this->post( 'course' );
		$comment   = $this->progress( 'course', $course_id, 'comments', 'complete' );
		$this->progress( 'course', $course_id, 'tables', 'complete' );
		wp_trash_post( $course_id );
		// WordPress updates the post and its comments separately during trash and restore.
		$wpdb->update( $wpdb->posts, array( 'post_status' => $post_status ), array( 'ID' => $course_id ) );
		$wpdb->update( $wpdb->comments, array( 'comment_approved' => $comment_status ), array( 'comment_ID' => $comment ) );
		$trashed = array( $this->source_rows( 'comments', $course_id ), $this->source_rows( 'tables', $course_id ) );
		$planner = new Progress_Reconciliation_Planner();

		// Act.
		$result           = $planner->inspect( 'course', $course_id, $this->student_id, $source );
		$after_inspection = array( $this->source_rows( 'comments', $course_id ), $this->source_rows( 'tables', $course_id ) );
		wp_untrash_post( $course_id );
		// Complete comment restoration when the post status was restored first.
		wp_untrash_post_comments( $course_id );
		$retry = $planner->inspect( 'course', $course_id, $this->student_id, $source );

		// Assert.
		self::assertSame( array(), $result['changes'], 'The temporary trash state must not produce progress copy proposals.' );
		self::assertSame( 'unresolved', $result['outcome'], 'Defer the course while either its post or comment is marked as trashed.' );
		self::assertSame( array( 'Trashed progress is preserved for restoration.' ), $result['unresolved'], 'Explain why the course is deferred.' );
		self::assertFalse( $result['supported_fields_verified'], 'Deferred progress has not been verified.' );
		self::assertSame( $trashed, $after_inspection, 'Inspection must preserve both stored copies and the reversible marker.' );
		self::assertSame( 'already_equivalent', $retry['outcome'], 'A retry after restoration must inspect the matching copies normally.' );
	}

	/**
	 * A trashed lesson must keep its shared quiz data out of copy proposals.
	 *
	 * @dataProvider directions
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_LessonWasTrashed_PreservesTheWholeQuizBundle( string $source ): void {
		// Arrange.
		global $wpdb;
		$this->select_source( $source );
		$lesson_id = $this->post( 'lesson' );
		$quiz_id   = $this->post( 'quiz', $lesson_id );
		$question  = $this->post( 'question' );
		$comment   = $this->progress( 'lesson', $lesson_id, 'comments', 'passed' );
		$this->progress( 'lesson', $lesson_id, 'tables', 'complete' );
		$this->progress( 'quiz', $quiz_id, 'tables', 'passed' );
		update_comment_meta( $comment, 'questions_asked', (string) $question );
		update_comment_meta( $comment, 'grade', 80 );
		update_comment_meta( $comment, 'quiz_answers', array( $question => 'comment answer' ) );
		update_comment_meta( $comment, 'quiz_grades', array( $question => 2 ) );
		update_comment_meta( $comment, 'quiz_answers_feedback', array( $question => 'comment feedback' ) );
		$submission = ( new Tables_Based_Submission_Repository( $wpdb ) )->create( $quiz_id, $this->student_id, 20 );
		$answer     = ( new Tables_Based_Answer_Repository( $wpdb ) )->create( $submission, $question, 'table answer' );
		( new Tables_Based_Grade_Repository( $wpdb ) )->create( $submission, $answer, $question, 1, 'table feedback' );
		wp_trash_post( $lesson_id );
		$trashed = array( $this->source_rows( 'comments', $lesson_id ), $this->source_rows( 'tables', $lesson_id ) );

		// Act.
		$result           = ( new Progress_Reconciliation_Planner() )->inspect( 'quiz', $quiz_id, $this->student_id, $source );
		$after_inspection = array( $this->source_rows( 'comments', $lesson_id ), $this->source_rows( 'tables', $lesson_id ) );

		// Assert.
		self::assertSame( array(), $result['changes'], 'Defer lesson progress and all related quiz proposals together.' );
		self::assertSame( 'unresolved', $result['outcome'], 'A quiz whose lesson is trashed must remain unresolved.' );
		self::assertSame( array( 'Trashed progress is preserved for restoration.' ), $result['unresolved'], 'Explain why the bundle is deferred.' );
		self::assertFalse( $result['supported_fields_verified'], 'The deferred quiz bundle has not been verified.' );
		self::assertSame( $trashed, $after_inspection, 'Inspection must preserve submissions, answers, grades and feedback in both stores.' );
	}

	/**
	 * A replaced quiz must not exchange historical data with the current lesson comment.
	 *
	 * @dataProvider directions
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_QuizWasTrashedAndReplaced_PreservesBothQuizCopies( string $source ): void {
		// Arrange.
		global $wpdb;
		$this->select_source( $source );
		$lesson_id = $this->post( 'lesson' );
		$old_quiz  = $this->post( 'quiz', $lesson_id );
		$question  = $this->post( 'question' );
		$this->progress( 'quiz', $old_quiz, 'tables', 'passed' );
		$submission = ( new Tables_Based_Submission_Repository( $wpdb ) )->create( $old_quiz, $this->student_id, 20 );
		( new Tables_Based_Answer_Repository( $wpdb ) )->create( $submission, $question, 'historical answer' );
		wp_trash_post( $old_quiz );
		$quiz_id = $this->post( 'quiz', $lesson_id );
		$comment = $this->progress( 'lesson', $lesson_id, 'comments', 'passed' );
		$this->progress( 'lesson', $lesson_id, 'tables', 'complete' );
		$this->progress( 'quiz', $quiz_id, 'tables', 'passed' );
		update_comment_meta( $comment, 'questions_asked', (string) $question );
		update_comment_meta( $comment, 'grade', 80 );
		update_comment_meta( $comment, 'quiz_answers', array( $question => 'current answer' ) );
		$submission = ( new Tables_Based_Submission_Repository( $wpdb ) )->create( $quiz_id, $this->student_id, 80 );
		( new Tables_Based_Answer_Repository( $wpdb ) )->create( $submission, $question, 'current answer' );
		$before = array( $this->source_rows( 'comments', $lesson_id ), $this->source_rows( 'tables', $lesson_id ) );

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'quiz', $old_quiz, $this->student_id, $source );

		// Assert.
		self::assertSame( array(), $result['changes'], 'Historical quiz data must not be copied into or out of the replacement comment.' );
		self::assertSame( 'unresolved', $result['outcome'], 'The superseded quiz has no independent comments copy.' );
		self::assertContains( 'The quiz and lesson do not have a consistent persisted mapping.', $result['unresolved'], 'Explain why the bundle cannot be copied.' );
		self::assertSame( $before, array( $this->source_rows( 'comments', $lesson_id ), $this->source_rows( 'tables', $lesson_id ) ), 'Both quiz copies must remain untouched.' );
	}

	/**
	 * A stale lesson link must not mix progress belonging to different lessons.
	 *
	 * @dataProvider directions
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_QuizMetadataPointedToAnotherLesson_PreservesBothLessons( string $source ): void {
		// Arrange.
		global $wpdb;
		$this->select_source( $source );
		$lesson_id = $this->post( 'lesson' );
		list( $other_lesson, $quiz_id, $question, $comment ) = $this->create_quiz_progress();
		update_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );
		$this->progress( 'lesson', $lesson_id, 'comments', 'in-progress' );
		$this->progress( 'lesson', $lesson_id, 'tables', 'in-progress' );
		update_comment_meta( $comment, 'questions_asked', (string) $question );
		update_comment_meta( $comment, 'grade', 80 );
		update_comment_meta( $comment, 'quiz_answers', array( $question => 'comment answer' ) );
		$submission = ( new Tables_Based_Submission_Repository( $wpdb ) )->create( $quiz_id, $this->student_id, 20 );
		( new Tables_Based_Answer_Repository( $wpdb ) )->create( $submission, $question, 'table answer' );
		$before = array( $this->source_rows( 'comments', $lesson_id ), $this->source_rows( 'comments', $other_lesson ), $this->source_rows( 'tables', $lesson_id ) );

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'lesson', $lesson_id, $this->student_id, $source );

		// Assert.
		self::assertSame( array(), $result['changes'], 'An inconsistent link must not propose changes for another lesson.' );
		self::assertSame( 'unresolved', $result['outcome'], 'The conflicting lesson and quiz identities need review.' );
		self::assertContains( 'The quiz and lesson do not have a consistent persisted mapping.', $result['unresolved'], 'Explain the conflicting identity.' );
		self::assertSame( $before, array( $this->source_rows( 'comments', $lesson_id ), $this->source_rows( 'comments', $other_lesson ), $this->source_rows( 'tables', $lesson_id ) ), 'Progress for both lessons must remain untouched.' );
	}

	/**
	 * A missing tables copy needs progress, answers, grades and feedback planned together.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_TableQuizCopyWasMissing_ProposesTheCompleteBundle(): void {
		// Arrange.
		$this->select_source( 'comments' );
		$lesson_id = $this->post( 'lesson' );
		$quiz_id   = $this->post( 'quiz', $lesson_id );
		$question  = $this->post( 'question' );
		$comment   = $this->progress( 'lesson', $lesson_id, 'comments', 'passed' );
		update_comment_meta( $comment, 'questions_asked', (string) $question );
		update_comment_meta( $comment, 'grade', 0 );
		update_comment_meta( $comment, 'quiz_answers', array( $question => 'saved answer' ) );
		update_comment_meta( $comment, 'quiz_grades', array( $question => 0 ) );
		update_comment_meta( $comment, 'quiz_answers_feedback', array( $question => 'saved feedback' ) );
		$before             = $this->source_rows( 'comments', $lesson_id );
		$destination_before = $this->source_rows( 'tables', $lesson_id );
		$expected_progress  = array( array( 'complete', '2024-01-01 12:00:00', '2024-01-02 12:00:00' ), array( 'passed', '2024-01-01 12:00:00', '2024-01-02 12:00:00' ) );

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'quiz', $quiz_id, $this->student_id, 'comments' );

		// Assert.
		$this->assert_quiz_copy_plan( $result, $lesson_id, $expected_progress );
		self::assertSame( $before, $this->source_rows( 'comments', $lesson_id ), 'Planning must leave its source unchanged.' );
		self::assertSame( $destination_before, $this->source_rows( 'tables', $lesson_id ), 'Planning must not create destination records.' );
	}

	/**
	 * A missing comments copy needs progress, answers, grades and feedback planned together.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_CommentQuizCopyWasMissing_ProposesTheCompleteBundle(): void {
		// Arrange.
		global $wpdb;
		$this->select_source( 'tables' );
		$lesson_id = $this->post( 'lesson' );
		$quiz_id   = $this->post( 'quiz', $lesson_id );
		$question  = $this->post( 'question' );
		$this->progress( 'lesson', $lesson_id, 'tables', 'complete' );
		$this->progress( 'quiz', $quiz_id, 'tables', 'passed' );
		$submission = ( new Tables_Based_Submission_Repository( $wpdb ) )->create( $quiz_id, $this->student_id, 0 );
		$answer     = ( new Tables_Based_Answer_Repository( $wpdb ) )->create( $submission, $question, 'saved answer' );
		( new Tables_Based_Grade_Repository( $wpdb ) )->create( $submission, $answer, $question, 0, 'saved feedback' );
		$before             = $this->source_rows( 'tables', $lesson_id );
		$destination_before = $this->source_rows( 'comments', $lesson_id );
		$expected_progress  = array( array( 'passed', '2024-01-01 12:00:00', '2024-01-02 12:00:00' ) );

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'quiz', $quiz_id, $this->student_id, 'tables' );

		// Assert.
		$this->assert_quiz_copy_plan( $result, $lesson_id, $expected_progress );
		self::assertSame( $before, $this->source_rows( 'tables', $lesson_id ), 'Planning must leave its source unchanged.' );
		self::assertSame( $destination_before, $this->source_rows( 'comments', $lesson_id ), 'Planning must not create destination records.' );
	}

	/**
	 * Differing source values can be copied without replacing extra destination children.
	 *
	 * @dataProvider opposite_stores
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_QuizChildrenDifferedAndDestinationHadExtras_ProposesOnlySourceChildren( string $source, string $destination ): void {
		// Arrange.
		global $wpdb;
		$this->select_source( $source );
		list( $lesson_id, $quiz_id, $question, $comment ) = $this->create_quiz_progress();
		$extra    = $this->post( 'question' );
		$answers  = array(
			$source      => array( $question => 'source answer' ),
			$destination => array(
				$question => 'old answer',
				$extra    => 'surviving answer',
			),
		);
		$points   = array(
			$source      => array( $question => 2 ),
			$destination => array(
				$question => 1,
				$extra    => 3,
			),
		);
		$feedback = array(
			$source      => array( $question => 'source feedback' ),
			$destination => array(
				$question => 'old feedback',
				$extra    => 'surviving feedback',
			),
		);
		update_comment_meta( $comment, 'questions_asked', implode( ',', array_keys( $answers['comments'] ) ) );
		update_comment_meta( $comment, 'grade', 80 );
		update_comment_meta( $comment, 'quiz_answers', $answers['comments'] );
		update_comment_meta( $comment, 'quiz_grades', $points['comments'] );
		update_comment_meta( $comment, 'quiz_answers_feedback', $feedback['comments'] );
		$submission = ( new Tables_Based_Submission_Repository( $wpdb ) )->create( $quiz_id, $this->student_id, 80 );
		foreach ( $answers['tables'] as $question_id => $value ) {
			$answer = ( new Tables_Based_Answer_Repository( $wpdb ) )->create( $submission, $question_id, $value );
			( new Tables_Based_Grade_Repository( $wpdb ) )->create( $submission, $answer, $question_id, $points['tables'][ $question_id ], $feedback['tables'][ $question_id ] );
		}
		$before  = array( $this->source_rows( 'comments', $lesson_id ), $this->source_rows( 'tables', $lesson_id ) );
		$planner = new Progress_Reconciliation_Planner();

		// Act.
		$result = $planner->inspect( 'quiz', $quiz_id, $this->student_id, $source );
		$retry  = $planner->inspect( 'quiz', $quiz_id, $this->student_id, $source );

		// Assert.
		self::assertSame(
			array(
				array(
					'kind'        => 'answers',
					'destination' => $destination,
					'data'        => array(
						'question_id' => $question,
						'value'       => 'source answer',
					),
					'quiz_id'     => $quiz_id,
					'student_id'  => $this->student_id,
					'post_id'     => $lesson_id,
				),
				array(
					'kind'        => 'grades',
					'destination' => $destination,
					'data'        => array(
						'question_id' => $question,
						'points'      => 2,
						'feedback'    => 'source feedback',
					),
					'quiz_id'     => $quiz_id,
					'student_id'  => $this->student_id,
					'post_id'     => $lesson_id,
				),
			),
			$result['changes'],
			'Only the source question receives answer and grade proposals; no collection replacement is proposed.'
		);
		self::assertSame(
			array(
				array(
					'kind'        => 'answer',
					'quiz_id'     => $quiz_id,
					'question_id' => $extra,
				),
				array(
					'kind'        => 'grade',
					'quiz_id'     => $quiz_id,
					'question_id' => $extra,
				),
			),
			$result['destination_only'],
			'Identify both extra children for preservation.'
		);
		self::assertSame( 'unresolved', $result['outcome'], 'Supported changes must not claim full equivalence while extra children survive.' );
		self::assertFalse( $result['supported_fields_verified'], 'The proposed repairs have not been applied or verified.' );
		self::assertSame( $result, $retry, 'Retrying the same persisted bundle must return the same plan.' );
		self::assertSame( $before, array( $this->source_rows( 'comments', $lesson_id ), $this->source_rows( 'tables', $lesson_id ) ), 'Planning and retrying must preserve both stores, including extra children.' );
	}

	/**
	 * A bad start date must not stop a valid status change from being proposed.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_SourceStartWasInvalid_ProposesStatusAndPreservesUnknownDate(): void {
		// Arrange.
		global $wpdb;
		$this->select_source( 'comments' );
		$course_id = $this->post( 'course' );
		$comment   = $this->progress( 'course', $course_id, 'comments', 'complete' );
		$this->progress( 'course', $course_id, 'tables', 'in-progress' );
		update_comment_meta( $comment, 'start', 'unknown historical date' );

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'course', $course_id, $this->student_id, 'comments' );

		// Assert.
		self::assertSame( 'unresolved', $result['outcome'], wp_json_encode( $result ) );
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT status, started_at FROM %i WHERE post_id = %d', $wpdb->prefix . 'sensei_lms_progress', $course_id ) );
		self::assertSame( 'complete', $result['changes'][0]['data']['status'], 'An unknown start must not block the status proposal.' );
		self::assertArrayNotHasKey( 'started_at', $result['changes'][0]['data'], 'An invalid source start must not be proposed.' );
		self::assertSame( 'in-progress', $row->status, 'Read-only planning must not apply its proposed status.' );
		self::assertSame( '2024-01-01 12:00:00', $row->started_at, 'The destination start must remain unchanged.' );
		self::assertSame( 'unknown historical date', get_comment_meta( $comment, 'start', true ), 'The source historical value must remain untouched.' );
	}

	/**
	 * When clocks go back, a local start time may match two UTC times; do not guess.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_SourceStartWasAmbiguousInCommentTimezone_ProposesOtherFields(): void {
		// Arrange.
		global $wpdb;
		$this->select_source( 'tables' );
		$original_timezone = get_option( 'timezone_string' );
		update_option( 'timezone_string', 'UTC' );
		// Another request can save a timezone while this request still has the old option cached.
		$wpdb->update( $wpdb->options, array( 'option_value' => 'America/New_York' ), array( 'option_name' => 'timezone_string' ) );
		$course_id = $this->post( 'course' );
		$comment   = $this->progress( 'course', $course_id, 'comments', 'in-progress' );
		$this->progress( 'course', $course_id, 'tables', 'complete' );
		$wpdb->update(
			$wpdb->prefix . 'sensei_lms_progress',
			array(
				'started_at'   => '2024-11-03 06:30:00',
				'completed_at' => '2024-11-04 12:00:00',
			),
			array(
				'post_id' => $course_id,
				'user_id' => $this->student_id,
			)
		);

		// Act.
		try {
			$result = ( new Progress_Reconciliation_Planner() )->inspect( 'course', $course_id, $this->student_id, 'tables' );
		} finally {
			wp_cache_delete( 'alloptions', 'options' );
			wp_cache_delete( 'timezone_string', 'options' );
			update_option( 'timezone_string', $original_timezone );
		}

		// Assert.
		$stored = $wpdb->get_row( $wpdb->prepare( 'SELECT comment_approved, comment_date_gmt FROM %i WHERE comment_ID = %d', $wpdb->comments, $comment ) );
		$start  = get_comment_meta( $comment, 'start', true );

		self::assertSame( 'unresolved', $result['outcome'], wp_json_encode( $result ) );
		self::assertSame( 'complete', $result['changes'][0]['data']['comment_approved'], 'The ambiguous start must not block the status proposal.' );
		self::assertArrayNotHasKey( 'start', $result['changes'][0]['data'], 'An ambiguous local start must not be proposed.' );
		self::assertSame( 'in-progress', $stored->comment_approved, 'Read-only planning must not apply its proposed status.' );
		self::assertSame( '2024-11-04 12:00:00', $result['changes'][0]['data']['comment_date_gmt'], 'The separately represented completion should still be proposed.' );
		self::assertSame( '2024-01-02 12:00:00', $stored->comment_date_gmt, 'Read-only planning must leave the destination completion unchanged.' );
		self::assertSame( '2024-01-01 12:00:00', $start, 'The existing local start must remain untouched.' );
	}

	/**
	 * A missing source quiz must preserve its surviving shared comment values.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_SourceQuizWasAbsent_PreservesTheSharedComment(): void {
		// Arrange.
		global $wpdb;
		$this->select_source( 'tables' );
		$lesson_id       = $this->post( 'lesson' );
		$quiz_id         = $this->post( 'quiz', $lesson_id );
		$question        = $this->post( 'question' );
		$comment         = $this->progress( 'lesson', $lesson_id, 'comments', 'passed' );
		$lesson_progress = $this->progress( 'lesson', $lesson_id, 'tables', 'in-progress' );
		$wpdb->update(
			$wpdb->prefix . 'sensei_lms_progress',
			array(
				'started_at'   => '2024-01-03 12:00:00',
				'completed_at' => null,
			),
			array( 'id' => $lesson_progress )
		);
		update_comment_meta( $comment, 'questions_asked', (string) $question );
		update_comment_meta( $comment, 'grade', 20 );
		$before        = $this->source_rows( 'comments', $lesson_id );
		$source_before = $this->source_rows( 'tables', $lesson_id );

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'lesson', $lesson_id, $this->student_id, 'tables' );

		// Assert.
		self::assertSame( array(), $result['changes'], 'The surviving quiz must not be replaced with lesson status or dates.' );
		self::assertContains(
			array(
				'kind'       => 'quiz_progress',
				'content_id' => $quiz_id,
			),
			$result['destination_only'],
			'Report the logical scope of the preserved quiz progress.'
		);
		self::assertSame( $before, $this->source_rows( 'comments', $lesson_id ), 'The shared comment, dates and grade must remain unchanged.' );
		self::assertSame( $source_before, $this->source_rows( 'tables', $lesson_id ), 'The source lesson and submission must remain unchanged.' );
		self::assertSame( 'unresolved', $result['outcome'], 'Preserved destination-only quiz data must remain unresolved.' );
	}

	/**
	 * A missing source quiz must preserve its surviving shared comment values.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_SourceQuizWasAbsent_ProposesOnlyTheIndependentFinalGrade(): void {
		// Arrange.
		global $wpdb;
		$this->select_source( 'tables' );
		$lesson_id       = $this->post( 'lesson' );
		$quiz_id         = $this->post( 'quiz', $lesson_id );
		$question        = $this->post( 'question' );
		$comment         = $this->progress( 'lesson', $lesson_id, 'comments', 'passed' );
		$lesson_progress = $this->progress( 'lesson', $lesson_id, 'tables', 'complete' );
		$wpdb->update(
			$wpdb->prefix . 'sensei_lms_progress',
			array(
				'started_at'   => '2024-01-03 12:00:00',
				'completed_at' => '2024-01-04 12:00:00',
			),
			array( 'id' => $lesson_progress )
		);
		update_comment_meta( $comment, 'questions_asked', (string) $question );
		update_comment_meta( $comment, 'grade', 20 );
		( new Tables_Based_Submission_Repository( $wpdb ) )->create( $quiz_id, $this->student_id, 80 );
		$before        = $this->source_rows( 'comments', $lesson_id );
		$source_before = $this->source_rows( 'tables', $lesson_id );

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'lesson', $lesson_id, $this->student_id, 'tables' );

		// Assert.
		self::assertSame( array( 'submission' ), array_column( $result['changes'], 'kind' ), 'A missing source quiz must prevent shared status and date proposals.' );
		self::assertSame( array( 'final_grade' => 80.0 ), $result['changes'][0]['data'], 'The independent final grade should still be proposed.' );
		self::assertContains(
			array(
				'kind'       => 'quiz_progress',
				'content_id' => $quiz_id,
			),
			$result['destination_only'],
			'Report the logical scope of the preserved quiz progress.'
		);
		self::assertSame( $before, $this->source_rows( 'comments', $lesson_id ), 'The shared comment, dates and grade must remain unchanged.' );
		self::assertSame( $source_before, $this->source_rows( 'tables', $lesson_id ), 'The source lesson and submission must remain unchanged.' );
		self::assertSame( 'unresolved', $result['outcome'], 'Preserved destination-only quiz data must remain unresolved.' );
	}

	/**
	 * Lesson activity alone must not create a quiz attempt in either store.
	 *
	 * @dataProvider directions
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::verify
	 */
	public function testVerify_OnlyLessonActivityWasRecorded_PreservesLegitimateDifferences( string $source ): void {
		// Arrange.
		$this->select_source( $source );
		$lesson_id = $this->post( 'lesson' );
		$this->post( 'quiz', $lesson_id );
		$this->progress( 'lesson', $lesson_id, 'comments', 'in-progress' );
		$this->progress( 'lesson', $lesson_id, 'tables', 'in-progress' );
		$before       = $this->source_rows( 'comments', $lesson_id );
		$table_before = $this->source_rows( 'tables', $lesson_id );

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->verify( 'lesson', $lesson_id, $this->student_id, $source );

		// Assert.
		self::assertSame( 'already_equivalent', $result['outcome'], wp_json_encode( $result ) );
		self::assertSame( $before, $this->source_rows( 'comments', $lesson_id ), 'Planning must leave the shared lesson comment unchanged.' );
		self::assertSame( $table_before, $this->source_rows( 'tables', $lesson_id ), 'Lesson-only activity must not create quiz progress.' );
	}

	/**
	 * Equal answers do not establish equal submission question identities.
	 *
	 * @dataProvider directions
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::verify
	 */
	public function testVerify_SubmittedQuestionIdsDiffered_ReportsUnresolvedIdentity( string $source ): void {
		// Arrange.
		global $wpdb;
		$this->select_source( $source );
		$lesson_id = $this->post( 'lesson' );
		$quiz_id   = $this->post( 'quiz', $lesson_id );
		$question  = $this->post( 'question' );
		$other     = $this->post( 'question' );
		$comment   = $this->progress( 'lesson', $lesson_id, 'comments', 'in-progress' );
		$this->progress( 'lesson', $lesson_id, 'tables', 'in-progress' );
		$this->progress( 'quiz', $quiz_id, 'tables', 'in-progress' );
		update_comment_meta( $comment, 'questions_asked', (string) $other );
		update_comment_meta( $comment, 'quiz_answers', array( $question => 'answer' ) );
		$submission = ( new Tables_Based_Submission_Repository( $wpdb ) )->create( $quiz_id, $this->student_id );
		( new Tables_Based_Answer_Repository( $wpdb ) )->create( $submission, $question, 'answer' );

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->verify( 'lesson', $lesson_id, $this->student_id, $source );

		// Assert.
		self::assertSame( 'unresolved', $result['outcome'], 'Different persisted question lists must not pass verification.' );
		self::assertFalse( $result['equivalent'], 'Matching answer values do not make different question lists equivalent.' );
		self::assertContains( 'Submitted question identities differ between comments and tables and are preserved for review.', $result['unresolved'], 'The caller must receive an explanation of the identity difference.' );
	}

	/**
	 * Visiting a quiz does not require creating shared lesson progress.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::verify
	 */
	public function testVerify_QuizWasOnlyVisited_PreservesLegitimateDifferences(): void {
		// Arrange.
		$this->select_source( 'tables' );
		$lesson_id = $this->post( 'lesson' );
		$quiz_id   = $this->post( 'quiz', $lesson_id );
		$this->progress( 'quiz', $quiz_id, 'tables', 'in-progress' );
		$before       = $this->source_rows( 'comments', $lesson_id );
		$table_before = $this->source_rows( 'tables', $lesson_id );

		// Act.
		$result = ( new Progress_Reconciliation_Planner() )->verify( 'lesson', $lesson_id, $this->student_id, 'tables' );

		// Assert.
		self::assertSame( 'already_equivalent', $result['outcome'], wp_json_encode( $result ) );
		self::assertSame( $before, $this->source_rows( 'comments', $lesson_id ), 'A quiz visit must not create a shared comment.' );
		self::assertSame( $table_before, $this->source_rows( 'tables', $lesson_id ), 'Verification must leave the recorded quiz visit unchanged.' );
	}

	/**
	 * Check what happens when the content or the student is missing.
	 *
	 * @return array
	 */
	public static function missing_identity_parts(): array {
		return array(
			'missing content' => array( 'posts' ),
			'missing student' => array( 'users' ),
		);
	}

	/**
	 * Cover both directions and the separate post/comment lifecycle updates.
	 *
	 * @return array
	 */
	public static function course_trash_states(): array {
		return array(
			'comments source, trashed course'     => array( 'comments', 'trash', 'post-trashed' ),
			'tables source, trashed course'       => array( 'tables', 'trash', 'post-trashed' ),
			'comment marker awaiting restoration' => array( 'comments', 'publish', 'post-trashed' ),
			'post trashed before comments marked' => array( 'tables', 'trash', 'complete' ),
		);
	}

	/**
	 * Named zones and signed fractional offsets must use the same saved-option rules.
	 *
	 * @return array
	 */
	public static function saved_timezones(): array {
		return array(
			'named zone, comments source'      => array( 'comments', 'America/New_York', -5.0, '2024-01-01 12:00:00', '2024-01-01 00:00:00', 'started_at', '2024-01-01 17:00:00' ),
			'positive offset, tables source'   => array( 'tables', '', 5.75, '2024-01-01 00:00:00', '2024-01-01 06:15:00', 'start', '2024-01-01 12:00:00' ),
			'negative offset, comments source' => array( 'comments', '', -3.5, '2024-01-01 12:00:00', '2024-01-01 00:00:00', 'started_at', '2024-01-01 15:30:00' ),
		);
	}

	/**
	 * Keep missing numeric grades distinct from a surviving zero in either store.
	 *
	 * @return array
	 */
	public static function absent_final_grades(): array {
		return array(
			'comments source without a grade' => array( 'comments', array(), 0.0, 'tables' ),
			'tables source without a grade'   => array( 'tables', array( 'grade' => 0 ), null, 'comments' ),
		);
	}

	/**
	 * Give each source the changed row values and expected destination status field.
	 *
	 * @return array
	 */
	public static function changed_course_sources(): array {
		return array(
			'comments changed after discovery' => array( 'comments', 'comment_post_ID', array( 'comment_approved' => 'complete' ), 'status' ),
			'tables changed after discovery'   => array(
				'tables',
				'post_id',
				array(
					'status'       => 'complete',
					'completed_at' => '2024-01-02 12:00:00',
				),
				'comment_approved',
			),
		);
	}

	/**
	 * Name the source and the opposite store whose data must survive.
	 *
	 * @return array
	 */
	public static function opposite_stores(): array {
		return array(
			'comments source' => array( 'comments', 'tables' ),
			'tables source'   => array( 'tables', 'comments' ),
		);
	}

	/**
	 * Create the shared progress rows used by several quiz metadata scenarios.
	 *
	 * @return int[] Lesson, quiz, question and comment IDs.
	 */
	private function create_quiz_progress(): array {
		$lesson_id = $this->post( 'lesson' );
		$quiz_id   = $this->post( 'quiz', $lesson_id );
		$question  = $this->post( 'question' );
		$comment   = $this->progress( 'lesson', $lesson_id, 'comments', 'in-progress' );
		$this->progress( 'lesson', $lesson_id, 'tables', 'in-progress' );
		$this->progress( 'quiz', $quiz_id, 'tables', 'in-progress' );

		return array( $lesson_id, $quiz_id, $question, $comment );
	}

	/**
	 * Check the common values required when copying a complete quiz bundle.
	 *
	 * @param array $result The planner result.
	 * @param int   $lesson_id The lesson that owns the quiz.
	 * @param array $expected_progress Expected status and date values.
	 */
	private function assert_quiz_copy_plan( array $result, int $lesson_id, array $expected_progress ): void {
		$changes          = array_column( $result['changes'], null, 'kind' );
		$progress_changes = array_filter(
			$result['changes'],
			static function ( array $change ): bool {
				return in_array( $change['kind'], array( 'progress', 'comment' ), true );
			}
		);
		$progress_values  = array_values(
			array_map(
				static function ( array $change ): array {
					return array(
						$change['data']['status'] ?? $change['data']['comment_approved'],
						$change['data']['started_at'] ?? $change['data']['start'],
						$change['data']['completed_at'] ?? $change['data']['comment_date_gmt'],
					);
				},
				$progress_changes
			)
		);

		self::assertSame( $expected_progress, $progress_values, 'The plan must include representable lesson and quiz status, start and completion values.' );
		self::assertSame( 'lesson', $result['identity']['type'], 'A quiz must be checked with its lesson.' );
		self::assertSame( $lesson_id, $result['identity']['content_id'], 'The plan must use the lesson stored for this quiz.' );
		self::assertSame( 'saved answer', $changes['answers']['data']['value'], 'The plan must include the missing answer.' );
		self::assertSame( 0, $changes['grades']['data']['points'], 'A zero question grade is a supported value.' );
		self::assertSame( 'saved feedback', $changes['grades']['data']['feedback'], 'The plan must include feedback.' );
		self::assertSame( 0.0, $changes['submission']['data']['final_grade'], 'The plan must include a zero final grade.' );
	}
}

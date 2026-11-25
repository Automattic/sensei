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
	 * Delete the test records before WordPress runs its own cleanup.
	 */
	public function tearDown(): void {
		$this->tear_down_reconciliation_fixtures();
		parent::tearDown();
	}

	/**
	 * Scan both stores in small batches and resume where the last batch stopped.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::discover
	 */
	public function testDiscover_MoreRecordsThanOnePageWereStored_ResumesUntilBothStoresWereRead(): void {
		$this->select_source( 'comments' );
		$expected = array();
		foreach ( range( 1, 3 ) as $unused ) {
			$course_id  = $this->post( 'course' );
			$expected[] = $course_id;
			$this->progress( 'course', $course_id, 'comments', 'in-progress' );
			$this->progress( 'course', $course_id, 'tables', 'in-progress' );
		}
		$service = new Progress_Reconciliation_Planner();
		$cursor  = array();
		$seen    = array();
		$sizes   = array();

		for ( $attempt = 0; $attempt < 20; ++$attempt ) {
			$page    = $service->discover( $cursor, 1 );
			$cursor  = $page['cursor'];
			$sizes[] = count( $page['identities'] );
			foreach ( $page['identities'] as $identity ) {
				if ( $identity['student_id'] === $this->student_id ) {
					$seen[] = $identity['content_id'];
				}
			}
			if ( $page['complete'] ) {
				break;
			}
		}

		self::assertTrue( $page['complete'], 'A persisted cursor must reach scan completion.' );
		self::assertSame( 1, max( $sizes ), 'Each discovery call must respect the requested physical row bound.' );
		self::assertSame( array_merge( $expected, $expected ), $seen, 'The comments and tables scans must both discover each persisted identity.' );
	}

	/**
	 * Do not change a shared comment when the lesson and quiz need different statuses.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_LessonAndQuizStatusesConflicted_PreservesTheSharedComment(): void {
		$this->select_source( 'tables' );
		$lesson_id = $this->post( 'lesson' );
		$quiz_id   = $this->post( 'quiz', $lesson_id );
		$this->progress( 'lesson', $lesson_id, 'comments', 'passed' );
		$this->progress( 'lesson', $lesson_id, 'tables', 'in-progress' );
		$this->progress( 'quiz', $quiz_id, 'tables', 'passed' );
		$before = $this->source_rows( 'comments', $lesson_id );

		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'lesson', $lesson_id, $this->student_id, 'tables' );

		self::assertSame( 'unresolved', $result['outcome'], wp_json_encode( $result ) );
		self::assertSame( $before, $this->source_rows( 'comments', $lesson_id ), 'Planning must not modify the shared comment.' );
		self::assertSame( array(), $result['changes'], 'Incompatible shared status and completion targets must not produce comment changes.' );
	}

	/**
	 * A status change must not invent or hide a completion the source cannot represent.
	 *
	 * @dataProvider unsupported_comment_completions
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_SourceCompletionWasNotRepresentable_WithholdsUnsafeStatusAndCompletion( string $type, bool $comment_exists, string $quiz_status = 'failed' ): void {
		global $wpdb;
		$this->select_source( 'tables' );
		$content_id = $this->post( $type );
		if ( $comment_exists ) {
			$comment = $this->progress( $type, $content_id, 'comments', 'lesson' === $type && 'failed' === $quiz_status ? 'passed' : 'in-progress' );
		}
		$progress_id = $this->progress( $type, $content_id, 'tables', 'complete' );
		if ( 'lesson' === $type ) {
			$quiz_id = $this->post( 'quiz', $content_id );
			update_post_meta( $quiz_id, '_pass_required', '' );
			$quiz_progress = $this->progress( 'quiz', $quiz_id, 'tables', $quiz_status );
			if ( 'passed' === $quiz_status ) {
				$wpdb->update( $wpdb->prefix . 'sensei_lms_progress', array( 'completed_at' => '2024-01-03 12:00:00' ), array( 'id' => $quiz_progress ) );
				$wpdb->update(
					$wpdb->comments,
					array(
						'comment_date'     => '2024-01-04 12:00:00',
						'comment_date_gmt' => '2024-01-04 12:00:00',
					),
					array( 'comment_ID' => $comment )
				);
			}
		} else {
			$wpdb->update(
				$wpdb->prefix . 'sensei_lms_progress',
				array(
					'started_at'   => '2024-01-03 12:00:00',
					'completed_at' => null,
				),
				array( 'id' => $progress_id )
			);
		}

		$result = ( new Progress_Reconciliation_Planner() )->inspect( $type, $content_id, $this->student_id, 'tables' );

		$expected = 'course' === $type && $comment_exists ? array( array( 'start' => '2024-01-03 12:00:00' ) ) : array();
		self::assertSame( $expected, array_column( $result['changes'], 'data' ), 'Withhold status and completion together, keeping a representable independent start on an existing comment.' );
		self::assertSame( 'unresolved', $result['outcome'], 'An unsupported completion must remain unresolved.' );
		self::assertNotEmpty( $result['unresolved'], 'The caller must receive the reason the comment cannot be copied.' );
	}

	/**
	 * Feedback surviving without points must stay visible and must not be overwritten.
	 *
	 * @dataProvider directions
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_CommentFeedbackSurvivedWithoutPoints_PreservesItForReview( string $source ): void {
		global $wpdb;
		$this->select_source( $source );
		$lesson_id = $this->post( 'lesson' );
		$quiz_id   = $this->post( 'quiz', $lesson_id );
		$question  = $this->post( 'question' );
		$comment   = $this->progress( 'lesson', $lesson_id, 'comments', 'in-progress' );
		$this->progress( 'lesson', $lesson_id, 'tables', 'in-progress' );
		$this->progress( 'quiz', $quiz_id, 'tables', 'in-progress' );
		update_comment_meta( $comment, 'questions_asked', (string) $question );
		update_comment_meta( $comment, 'quiz_answers', array( $question => 'answer' ) );
		update_comment_meta( $comment, 'quiz_answers_feedback', array( $question => 'surviving feedback' ) );
		update_comment_meta( $comment, 'grade', 20 );
		$submission = ( new Tables_Based_Submission_Repository( $wpdb ) )->create( $quiz_id, $this->student_id, 'tables' === $source ? 80 : 20 );
		$answer     = ( new Tables_Based_Answer_Repository( $wpdb ) )->create( $submission, $question, 'answer' );
		if ( 'tables' === $source ) {
			( new Tables_Based_Grade_Repository( $wpdb ) )->create( $submission, $answer, $question, 1 );
		}

		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'lesson', $lesson_id, $this->student_id, $source );

		self::assertFalse( $result['equivalent'], 'Feedback omitted from normalized grades must prevent full equivalence.' );
		self::assertContains( 'Feedback for question ' . $question . ' has no comment grade and is preserved for review.', $result['unresolved'], 'The caller must be told which surviving feedback cannot be copied safely.' );
		self::assertSame( 'tables' === $source ? array( 'submission' ) : array(), array_column( $result['changes'], 'kind' ), 'A grade must not erase the feedback, while an independent final-grade change remains supported.' );
		if ( 'tables' === $source ) {
			self::assertSame( array( 'final_grade' => 80.0 ), $result['changes'][0]['data'], 'Unresolved feedback must not block the final-grade proposal.' );
		}
	}

	/**
	 * A missing submission marker must not hide or overwrite surviving quiz metadata.
	 *
	 * @dataProvider quiz_data_without_submission_markers
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_QuizDataSurvivedWithoutSubmissionMarker_PreservesItForReview( string $source, array $collections, ?int $grade, bool $table_submission ): void {
		global $wpdb;
		$this->select_source( $source );
		$lesson_id = $this->post( 'lesson' );
		$quiz_id   = $this->post( 'quiz', $lesson_id );
		$question  = $this->post( 'question' );
		$comment   = $this->progress( 'lesson', $lesson_id, 'comments', 'in-progress' );
		$this->progress( 'lesson', $lesson_id, 'tables', 'in-progress' );
		$this->progress( 'quiz', $quiz_id, 'tables', 'in-progress' );
		if ( null !== $grade ) {
			update_comment_meta( $comment, 'grade', $grade );
		}
		foreach ( $collections as $key => $value ) {
			update_comment_meta( $comment, $key, array( $question => $value ) );
		}
		if ( $table_submission ) {
			$submission = ( new Tables_Based_Submission_Repository( $wpdb ) )->create( $quiz_id, $this->student_id );
			( new Tables_Based_Answer_Repository( $wpdb ) )->create( $submission, $question, 'answer' );
		}

		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'lesson', $lesson_id, $this->student_id, $source );

		self::assertFalse( $result['equivalent'], 'A missing questions_asked marker must not hide surviving quiz data from verification.' );
		self::assertContains( 'Quiz data without a submission question marker is preserved for review.', $result['unresolved'], 'The caller must be told why the incomplete submission cannot be copied.' );
		self::assertSame( array(), $result['changes'], 'Missing submission metadata must not cause a surviving final grade, including zero, or its children to be replaced.' );
	}

	/**
	 * A missing source grade must not erase a destination grade, including zero.
	 *
	 * @dataProvider directions
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_SourceFinalGradeWasAbsent_PreservesZeroAndReportsLogicalScope( string $source ): void {
		global $wpdb;
		$this->select_source( $source );
		$lesson_id = $this->post( 'lesson' );
		$quiz_id   = $this->post( 'quiz', $lesson_id );
		$question  = $this->post( 'question' );
		$comment   = $this->progress( 'lesson', $lesson_id, 'comments', 'in-progress' );
		$this->progress( 'lesson', $lesson_id, 'tables', 'in-progress' );
		update_comment_meta( $comment, 'questions_asked', (string) $question );
		update_comment_meta( $comment, 'quiz_answers', array( $question => 'answer' ) );
		if ( 'tables' === $source ) {
			update_comment_meta( $comment, 'grade', 0 );
		}
		$submission = ( new Tables_Based_Submission_Repository( $wpdb ) )->create( $quiz_id, $this->student_id, 'comments' === $source ? 0 : null );
		( new Tables_Based_Answer_Repository( $wpdb ) )->create( $submission, $question, 'answer' );

		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'lesson', $lesson_id, $this->student_id, $source );

		$destination_grade = 'comments' === $source
			? \Sensei\Internal\Migration\Validations\Validation_Records::get_tables_submission( $quiz_id, $this->student_id )->get_final_grade()
			: \Sensei\Internal\Migration\Validations\Validation_Records::get_comments_submission( $quiz_id, $this->student_id )->get_final_grade();
		self::assertSame( 'unresolved', $result['outcome'], wp_json_encode( $result ) );
		self::assertSame( 0.0, $destination_grade, 'Zero is a surviving numeric final grade.' );
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
	}

	/**
	 * Do not propose copies for a student or content that was deleted.
	 *
	 * @dataProvider missing_identity_parts
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_ContentOrStudentWasMissing_PreservesOrphansForReview( string $missing ): void {
		global $wpdb;
		$this->select_source( 'comments' );
		$course_id = $this->post( 'course' );
		$this->progress( 'course', $course_id, 'comments', 'complete' );
		if ( 'content' === $missing ) {
			$wpdb->delete( $wpdb->posts, array( 'ID' => $course_id ) );
		} else {
			$wpdb->delete( $wpdb->users, array( 'ID' => $this->student_id ) );
		}
		$before = $this->source_rows( 'comments', $course_id );

		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'course', $course_id, $this->student_id, 'comments' );

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
		$this->select_source( 'tables' );
		$course_id = $this->post( 'course' );
		$this->progress( 'course', $course_id, 'comments', 'complete' );
		$before = $this->source_rows( 'comments', $course_id );

		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'course', $course_id, $this->student_id, 'comments' );

		self::assertSame( 'conflicting', $result['outcome'], wp_json_encode( $result ) );
		self::assertSame( $before, $this->source_rows( 'comments', $course_id ), 'A changed source policy must not mutate comments.' );
		self::assertSame( array(), $this->source_rows( 'tables', $course_id ), 'A changed source policy must not copy to tables.' );
	}

	/**
	 * Keep destination progress when the source copy is missing.
	 *
	 * @dataProvider directions
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_SourceWasAbsent_PreservesDestinationOnlyProgress( string $source ): void {
		$this->select_source( $source );
		$course_id   = $this->post( 'course' );
		$destination = 'comments' === $source ? 'tables' : 'comments';
		$this->progress( 'course', $course_id, $destination, 'complete' );
		$before = $this->source_rows( $destination, $course_id );

		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'course', $course_id, $this->student_id, $source );

		self::assertSame( 'unresolved', $result['outcome'], wp_json_encode( $result ) );
		self::assertFalse( $result['applied'], 'Missing deletion evidence must not produce a write.' );
		self::assertSame( array(), $result['changes'], 'Source absence must not propose replacing surviving progress.' );
		self::assertSame( $before, $this->source_rows( $destination, $course_id ), 'Every destination-only progress value must remain untouched.' );
	}

	/**
	 * Plan from the current database values, even if discovery and cached reads are old.
	 *
	 * @dataProvider directions
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_CourseChangedAfterDiscovery_ProposesCurrentSourceWithoutWriting( string $source ): void {
		global $wpdb;
		$this->select_source( $source );
		$course_id = $this->post( 'course' );
		$this->progress( 'course', $course_id, 'comments', 'in-progress' );
		$this->progress( 'course', $course_id, 'tables', 'in-progress' );
		Sensei()->course_progress_repository->get( $course_id, $this->student_id );
		$planner = new Progress_Reconciliation_Planner();
		$planner->discover( array(), 1 );
		if ( 'comments' === $source ) {
			$wpdb->update(
				$wpdb->comments,
				array( 'comment_approved' => 'complete' ),
				array(
					'comment_post_ID' => $course_id,
					'user_id'         => $this->student_id,
				)
			);
		} else {
			$wpdb->update(
				$wpdb->prefix . 'sensei_lms_progress',
				array(
					'status'       => 'complete',
					'completed_at' => '2024-01-02 12:00:00',
				),
				array(
					'post_id' => $course_id,
					'user_id' => $this->student_id,
				)
			);
		}
		$comments_before = $this->source_rows( 'comments', $course_id );
		$tables_before   = $this->source_rows( 'tables', $course_id );
		$actions         = did_action( 'sensei_course_status_updated' );

		$result = $planner->inspect( 'course', $course_id, $this->student_id, $source );

		$status_field = 'comments' === $source ? 'status' : 'comment_approved';
		self::assertSame( 'complete', $result['changes'][0]['data'][ $status_field ], 'Planning must use the persisted change made after discovery.' );
		self::assertFalse( $result['supported_fields_verified'], 'A proposed supported repair remains unverified.' );
		self::assertSame( $comments_before, $this->source_rows( 'comments', $course_id ), 'Planning must leave comments unchanged.' );
		self::assertSame( $tables_before, $this->source_rows( 'tables', $course_id ), 'Planning must leave tables unchanged.' );
		self::assertSame( $actions, did_action( 'sensei_course_status_updated' ), 'Planning must not emit application-save actions.' );
	}

	/**
	 * A missing quiz copy needs progress, answers, grades and feedback planned together.
	 *
	 * @dataProvider directions
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_WholeQuizCopyWasMissing_ProposesTheSharedBundleWithoutWriting( string $source ): void {
		global $wpdb;
		$this->select_source( $source );
		$lesson_id = $this->post( 'lesson' );
		$quiz_id   = $this->post( 'quiz', $lesson_id );
		$question  = $this->post( 'question' );
		if ( 'comments' === $source ) {
			$comment = $this->progress( 'lesson', $lesson_id, 'comments', 'passed' );
			update_comment_meta( $comment, 'questions_asked', (string) $question );
			update_comment_meta( $comment, 'grade', 0 );
			update_comment_meta( $comment, 'quiz_answers', array( $question => 'saved answer' ) );
			update_comment_meta( $comment, 'quiz_grades', array( $question => 0 ) );
			update_comment_meta( $comment, 'quiz_answers_feedback', array( $question => 'saved feedback' ) );
		} else {
			$this->progress( 'lesson', $lesson_id, 'tables', 'complete' );
			$this->progress( 'quiz', $quiz_id, 'tables', 'passed' );
			$submission = ( new Tables_Based_Submission_Repository( $wpdb ) )->create( $quiz_id, $this->student_id, 0 );
			$answer     = ( new Tables_Based_Answer_Repository( $wpdb ) )->create( $submission, $question, 'saved answer' );
			( new Tables_Based_Grade_Repository( $wpdb ) )->create( $submission, $answer, $question, 0, 'saved feedback' );
		}
		$before             = $this->source_rows( $source, $lesson_id );
		$destination        = 'comments' === $source ? 'tables' : 'comments';
		$destination_before = $this->source_rows( $destination, $lesson_id );

		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'quiz', $quiz_id, $this->student_id, $source );

		$changes           = array_column( $result['changes'], null, 'kind' );
		$progress_changes  = array_filter(
			$result['changes'],
			static function ( array $change ): bool {
				return in_array( $change['kind'], array( 'progress', 'comment' ), true );
			}
		);
		$progress_values   = array_values(
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
		$expected_progress = 'comments' === $source
			? array(
				array( 'complete', '2024-01-01 12:00:00', '2024-01-02 12:00:00' ),
				array( 'passed', '2024-01-01 12:00:00', '2024-01-02 12:00:00' ),
			)
			: array( array( 'passed', '2024-01-01 12:00:00', '2024-01-02 12:00:00' ) );
		self::assertSame( $expected_progress, $progress_values, 'The plan must include representable lesson and quiz status, start and completion values.' );
		self::assertSame( 'lesson', $result['identity']['type'], 'Quiz planning must canonicalize to its shared lesson bundle.' );
		self::assertSame( $lesson_id, $result['identity']['content_id'], 'The canonical lesson must use its persisted mapping.' );
		self::assertSame( 'saved answer', $changes['answers']['data']['value'], 'The plan must include the missing answer.' );
		self::assertSame( 0, $changes['grades']['data']['points'], 'A zero question grade is a supported value.' );
		self::assertSame( 'saved feedback', $changes['grades']['data']['feedback'], 'The plan must include feedback.' );
		self::assertSame( 0.0, $changes['submission']['data']['final_grade'], 'The plan must include a zero final grade.' );
		self::assertSame( $before, $this->source_rows( $source, $lesson_id ), 'Planning must not change its source.' );
		self::assertSame( $destination_before, $this->source_rows( $destination, $lesson_id ), 'Planning must not create any destination records.' );
	}

	/**
	 * A bad start date must not stop a valid status change from being proposed.
	 *
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_SourceStartWasInvalid_ProposesStatusAndPreservesUnknownDate(): void {
		global $wpdb;
		$this->select_source( 'comments' );
		$course_id = $this->post( 'course' );
		$comment   = $this->progress( 'course', $course_id, 'comments', 'complete' );
		$this->progress( 'course', $course_id, 'tables', 'in-progress' );
		update_comment_meta( $comment, 'start', 'unknown historical date' );

		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'course', $course_id, $this->student_id, 'comments' );

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
		global $wpdb;
		$this->select_source( 'tables' );
		$original_timezone = get_option( 'timezone_string' );
		update_option( 'timezone_string', 'America/New_York' );
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

		try {
			$result = ( new Progress_Reconciliation_Planner() )->inspect( 'course', $course_id, $this->student_id, 'tables' );
			$stored = $wpdb->get_row( $wpdb->prepare( 'SELECT comment_approved, comment_date_gmt FROM %i WHERE comment_ID = %d', $wpdb->comments, $comment ) );
			$start  = get_comment_meta( $comment, 'start', true );
		} finally {
			update_option( 'timezone_string', $original_timezone );
		}

		self::assertSame( 'unresolved', $result['outcome'], wp_json_encode( $result ) );
		self::assertSame( 'complete', $result['changes'][0]['data']['comment_approved'], 'The ambiguous start must not block the status proposal.' );
		self::assertArrayNotHasKey( 'start', $result['changes'][0]['data'], 'An ambiguous local start must not be proposed.' );
		self::assertSame( 'in-progress', $stored->comment_approved, 'Read-only planning must not apply its proposed status.' );
		self::assertSame( '2024-11-04 12:00:00', $result['changes'][0]['data']['comment_date_gmt'], 'The separately represented completion should still be proposed.' );
		self::assertSame( '2024-01-02 12:00:00', $stored->comment_date_gmt, 'Read-only planning must leave the destination completion unchanged.' );
		self::assertSame( '2024-01-01 12:00:00', $start, 'The existing local start must remain untouched.' );
	}

	/**
	 * Do not overwrite a surviving quiz just because its source copy is missing.
	 *
	 * @dataProvider destination_only_quiz_cases
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::inspect
	 */
	public function testInspect_SourceQuizProgressWasAbsent_PreservesSharedFieldsAndProposesIndependentGrade(
		string $lesson_status,
		bool $source_has_submission
	): void {
		global $wpdb;
		$this->select_source( 'tables' );
		$lesson_id = $this->post( 'lesson' );
		$quiz_id   = $this->post( 'quiz', $lesson_id );
		$question  = $this->post( 'question' );
		$comment   = $this->progress( 'lesson', $lesson_id, 'comments', 'passed' );
		$this->progress( 'lesson', $lesson_id, 'tables', $lesson_status );
		$wpdb->update(
			$wpdb->prefix . 'sensei_lms_progress',
			array(
				'started_at'   => '2024-01-03 12:00:00',
				'completed_at' => 'complete' === $lesson_status ? '2024-01-04 12:00:00' : null,
			),
			array(
				'post_id' => $lesson_id,
				'user_id' => $this->student_id,
			)
		);
		update_comment_meta( $comment, 'questions_asked', (string) $question );
		update_comment_meta( $comment, 'grade', 20 );
		if ( $source_has_submission ) {
			( new Tables_Based_Submission_Repository( $wpdb ) )->create( $quiz_id, $this->student_id, 80 );
		}
		$before = $this->source_rows( 'tables', $lesson_id );

		$result = ( new Progress_Reconciliation_Planner() )->inspect( 'lesson', $lesson_id, $this->student_id, 'tables' );

		$stored = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT comment_approved, comment_date, comment_date_gmt FROM %i WHERE comment_ID = %d',
				$wpdb->comments,
				$comment
			),
			ARRAY_A
		);
		self::assertSame(
			array(
				'comment_approved' => 'passed',
				'comment_date'     => '2024-01-02 12:00:00',
				'comment_date_gmt' => '2024-01-02 12:00:00',
			),
			$stored,
			'Source-absent quiz status and completion must survive a conflicting lesson repair.'
		);
		self::assertSame( '2024-01-01 12:00:00', get_comment_meta( $comment, 'start', true ), 'The surviving quiz start must not become the source lesson start.' );
		self::assertSame( 'unresolved', $result['outcome'], wp_json_encode( $result ) );
		self::assertContains(
			array(
				'kind'       => 'quiz_progress',
				'content_id' => $quiz_id,
			),
			$result['destination_only'],
			'The unresolved quiz needs its logical progress scope.'
		);
		self::assertSame(
			array(),
			array_values(
				array_filter(
					$result['changes'],
					static function ( array $change ): bool {
						return 'comment' === $change['kind'];
					}
				)
			),
			'A source-absent quiz must prevent shared status and date proposals.'
		);
		$submissions = array_values(
			array_filter(
				$result['changes'],
				static function ( array $change ): bool {
					return 'submission' === $change['kind'];
				}
			)
		);
		self::assertSame( $source_has_submission ? array( array( 'final_grade' => 80.0 ) ) : array(), array_column( $submissions, 'data' ), 'An independent supported grade must remain proposed when present.' );
		self::assertSame(
			20.0,
			(float) get_comment_meta( $comment, 'grade', true ),
			'Read-only planning must not apply the independent grade proposal.'
		);
		self::assertSame( $before, $this->source_rows( 'tables', $lesson_id ), 'Preserving the destination quiz must not write to its source bundle.' );
	}

	/**
	 * Lesson activity alone must not create a quiz attempt in either store.
	 *
	 * @dataProvider directions
	 * @covers \Sensei\Internal\Services\Progress_Reconciliation_Planner::verify
	 */
	public function testVerify_OnlyLessonActivityWasRecorded_PreservesLegitimateDifferences( string $source ): void {
		$this->select_source( $source );
		$lesson_id = $this->post( 'lesson' );
		$this->post( 'quiz', $lesson_id );
		$this->progress( 'lesson', $lesson_id, 'comments', 'in-progress' );
		$this->progress( 'lesson', $lesson_id, 'tables', 'in-progress' );
		$before       = $this->source_rows( 'comments', $lesson_id );
		$table_before = $this->source_rows( 'tables', $lesson_id );

		$result = ( new Progress_Reconciliation_Planner() )->verify( 'lesson', $lesson_id, $this->student_id, $source );

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

		$result = ( new Progress_Reconciliation_Planner() )->verify( 'lesson', $lesson_id, $this->student_id, $source );

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
		$this->select_source( 'tables' );
		$lesson_id = $this->post( 'lesson' );
		$quiz_id   = $this->post( 'quiz', $lesson_id );
		$this->progress( 'quiz', $quiz_id, 'tables', 'in-progress' );
		$before       = $this->source_rows( 'comments', $lesson_id );
		$table_before = $this->source_rows( 'tables', $lesson_id );

		$result = ( new Progress_Reconciliation_Planner() )->verify( 'lesson', $lesson_id, $this->student_id, 'tables' );

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
			'missing content' => array( 'content' ),
			'missing student' => array( 'student' ),
		);
	}

	/**
	 * Cover an absent completion on creation and update, and a conflicting shared completion.
	 *
	 * @return array
	 */
	public static function unsupported_comment_completions(): array {
		return array(
			'completed course without a completion date'   => array( 'course', true ),
			'missing course comment without a completion date' => array( 'course', false ),
			'completed lesson with a failed optional quiz' => array( 'lesson', true ),
			'lesson and quiz completion timestamps conflict' => array( 'lesson', true, 'passed' ),
		);
	}

	/**
	 * Cover overlooked source data and an unmodeled destination final grade.
	 *
	 * @return array
	 */
	public static function quiz_data_without_submission_markers(): array {
		$collections = array(
			'quiz_answers'          => 'surviving answer',
			'quiz_grades'           => 2,
			'quiz_answers_feedback' => 'surviving feedback',
		);
		return array(
			'unmodeled comments source'               => array( 'comments', $collections, null, false ),
			'unmodeled comments destination'          => array( 'tables', $collections, null, false ),
			'zero grade without children or a marker' => array( 'tables', array(), 0, true ),
		);
	}

	/**
	 * Cover lesson statuses and dates that would overwrite the surviving quiz.
	 *
	 * @return array
	 */
	public static function destination_only_quiz_cases(): array {
		return array(
			'lesson status differs without a source submission' => array(
				'in-progress',
				false,
			),
			'lesson dates differ with independent quiz values' => array(
				'complete',
				true,
			),
		);
	}
}

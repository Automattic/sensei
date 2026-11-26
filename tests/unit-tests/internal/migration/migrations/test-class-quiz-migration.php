<?php

namespace SenseiTest\Internal\Migration\Migrations;

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-progress-insert-failure-helpers.php';

use Sensei\Internal\Migration\Migrations\Quiz_Migration;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Tables_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Tables_Based_Grade_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Tables_Based_Submission_Repository;
use Sensei\Internal\Migration\Migration_Job_Scheduler;
use Sensei\Internal\Migration\Validations\Quiz_Submission_Validation;
use Sensei\Internal\Migration\Validations\Validation_Records;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Comments_Based_Submission_Repository;
use Sensei_Factory;
use Sensei_Utils;

/**
 * Class Quiz_Migration_Test
 *
 * @covers \Sensei\Internal\Migration\Migrations\Quiz_Migration
 */
class Quiz_Migration_Test extends \WP_UnitTestCase {
	use \Sensei_Progress_Insert_Failure_Helpers;

	/**
	 * Comment boundary before this test creates its source fixtures.
	 *
	 * @var int
	 */
	private $initial_comment_id;

	/**
	 * Migration instance.
	 *
	 * @var \Sensei\Internal\Migration\Migrations\Quiz_Migration
	 */
	private $migration;

	protected $factory;

	protected function setUp(): void {
		parent::setUp();

		$this->migration = new Quiz_Migration();
		$this->factory   = new Sensei_Factory();
		$this->cleanup_custom_tables();

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->initial_comment_id = (int) $wpdb->get_var( "SELECT MAX(comment_ID) FROM {$wpdb->comments}" );
		update_option( Quiz_Migration::LAST_COMMENT_ID_OPTION_NAME, $this->initial_comment_id );
	}

	protected function tearDown(): void {
		$this->restore_progress_storage_mode();

		parent::tearDown();
	}

	public function testGetErrors_MigrationDidntRun_ReturnsEmptyArray(): void {
		/* Act. */
		$actual = $this->migration->get_errors();

		/* Assert. */
		$this->assertEmpty( $actual );
	}

	public function testRun_NoQuizSubmission_ReturnsZero(): void {
		/* Arrange. */
		$expected = 0;

		/* Act. */
		$actual = $this->migration->run( $dry_run = false ); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found

		/* Assert. */
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Migrating one source quiz creates one submission.
	 *
	 * @covers \Sensei\Internal\Migration\Migrations\Quiz_Migration::run
	 */
	public function testRun_HasQuizSubmission_ReturnsMatchingNumberOfInserts(): void {
		/* Arrange. */
		$this->create_quiz_data();

		/* Act. */
		$this->migration->run( $dry_run = false ); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found

		/* Assert. */
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$submission_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sensei_lms_quiz_submissions" );
		$this->assertSame( '1', $submission_count );
	}

	/**
	 * A migration batch processes only its configured number of submissions.
	 *
	 * @covers \Sensei\Internal\Migration\Migrations\Quiz_Migration::run
	 */
	public function testRun_HasMoreQuizSubmissionsThanTheBatchSize_ReturnsNumberMatchingTheBatchSize(): void {
		/* Arrange. */
		$migration = new Quiz_Migration( 1 );

		$this->create_quiz_data();
		$this->create_quiz_data();

		/* Act. */
		$migration->run( $dry_run = false ); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found

		/* Assert. */
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$submission_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sensei_lms_quiz_submissions" );
		$this->assertSame( '1', $submission_count );
	}

	/**
	 * Migration copies the source submission, answers, grades and feedback.
	 *
	 * @covers \Sensei\Internal\Migration\Migrations\Quiz_Migration::run
	 */
	public function testRun_HasQuizSubmission_CreatesQuizDataInCustomTables(): void {
		/* Arrange. */
		[ $user_id, $lesson_id, $quiz_id, $question_id, $answers, $grades, $feedback ] = $this->create_quiz_data();
		$this->set_progress_storage_mode( 'tables' );
		$repository = new Tables_Based_Submission_Repository( $GLOBALS['wpdb'] );
		$repository->get( $quiz_id, $user_id );

		/* Act. */
		$this->migration->run( $dry_run = false ); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found

		/* Assert. */
		$expected = array(
			'submission' => array(
				'quiz_id'     => $quiz_id,
				'user_id'     => $user_id,
				'final_grade' => 12.34,
			),
			'answers'    => array(
				array(
					'question_id' => $question_id,
					'value'       => $answers[ $question_id ],
				),
			),
			'grades'     => array(
				array(
					'question_id' => $question_id,
					'points'      => $grades[ $question_id ],
					'feedback'    => $feedback[ $question_id ],
				),
			),
		);
		$this->assertSame( $expected, $this->get_quiz_data( $quiz_id, $user_id ) );
	}

	/**
	 * Saved answers keep zero distinct from no grade, and invalid grades remain unresolved.
	 *
	 * @covers \Sensei\Internal\Migration\Migrations\Quiz_Migration::run
	 * @dataProvider historical_final_grades
	 * @param string|null $source_grade Stored source grade, or null when its metadata is absent.
	 * @param float|null  $expected_grade Expected destination grade.
	 * @param string[]    $migration_errors Expected migration error formats.
	 * @param string[]    $validation_errors Expected validation error messages.
	 */
	public function testRun_SavedAnswersWithHistoricalGrade_PreservesGradeMeaning(
		?string $source_grade,
		?float $expected_grade,
		array $migration_errors,
		array $validation_errors
	): void {
		[ $student_id, , $quiz_id ] = $this->create_quiz_data();
		$comments_repository        = new Comments_Based_Submission_Repository();
		$comment_id                 = $comments_repository->get( $quiz_id, $student_id )->get_id();
		if ( null === $source_grade ) {
			delete_comment_meta( $comment_id, 'grade' );
		} else {
			update_comment_meta( $comment_id, 'grade', $source_grade );
		}
		update_option( Migration_Job_Scheduler::COMPLETED_OPTION_NAME, true );
		$validation = new Quiz_Submission_Validation();
		$repository = new Tables_Based_Submission_Repository( $GLOBALS['wpdb'] );

		$this->migration->run( false );
		$submission = $repository->get( $quiz_id, $student_id );
		$validation->run();
		$run_errors = $validation->get_errors();
		$inspection = $validation->inspect( $quiz_id, $student_id );
		wp_cache_delete( $comment_id, 'comment_meta' );

		self::assertSame( $expected_grade, $submission->get_final_grade(), 'The copied grade must retain the stored meaning.' );
		self::assertSame( $source_grade ?? '', get_comment_meta( $comment_id, 'grade', true ), 'Migration must retain the original source value.' );
		self::assertSame( null !== $source_grade, metadata_exists( 'comment', $comment_id, 'grade' ), 'An absent source grade must remain absent.' );
		self::assertCount( count( $migration_errors ), $this->migration->get_errors(), 'Migration must report each invalid source value.' );
		foreach ( $migration_errors as $message ) {
			self::assertStringContainsString( $message, implode( ' ', $this->migration->get_errors() ), 'The migration diagnostic must identify the unresolved grade.' );
		}
		self::assertSame(
			$validation_errors,
			array_map(
				static function ( $error ): string {
					return $error->get_message();
				},
				$run_errors
			),
			'Full validation must keep invalid source values unresolved.'
		);
		foreach ( $run_errors as $error ) {
			self::assertSame( $source_grade, $error->get_data()['stored_grade'], 'The unresolved diagnostic must retain the original invalid value.' );
		}
		self::assertSame(
			$validation_errors,
			array_map(
				static function ( $error ): string {
					return $error->get_message();
				},
				$inspection['errors']
			),
			'Targeted inspection must report the same invalid source values.'
		);
	}

	/**
	 * Historical grade values with distinct migration outcomes.
	 *
	 * @return array
	 */
	public static function historical_final_grades(): array {
		return array(
			'numeric zero'  => array( '0', 0.0, array(), array() ),
			'missing grade' => array( null, null, array(), array() ),
			'blank grade'   => array( '', null, array(), array() ),
			'invalid grade' => array(
				'NAN',
				null,
				array( 'Comments-based quiz submission has an invalid final grade.' ),
				array( 'Comments-based quiz submission has an invalid final grade.' ),
			),
		);
	}

	/**
	 * Incomplete source history stays available for investigation without new table rows.
	 *
	 * @covers \Sensei\Internal\Migration\Migrations\Quiz_Migration::run
	 * @dataProvider incomplete_submissions
	 * @param mixed  $answers Stored answer metadata, or null when absent.
	 * @param bool   $questions_asked Whether the discoverability key survives.
	 * @param string $message Expected unresolved source diagnostic.
	 */
	public function testRun_IncompleteSubmissionWasStored_LeavesItUncopied( $answers, bool $questions_asked, string $message ): void {
		[ $student_id, , $quiz_id, $question_id ] = $this->create_quiz_data();
		$comment_id                               = ( new Comments_Based_Submission_Repository() )->get( $quiz_id, $student_id )->get_id();
		update_comment_meta( $comment_id, 'grade', '100' );
		update_comment_meta( $comment_id, 'quiz_grades', array( $question_id => 2 ) );
		update_comment_meta( $comment_id, 'quiz_answers_feedback', array( $question_id => 'retained feedback' ) );
		if ( null === $answers ) {
			delete_comment_meta( $comment_id, 'quiz_answers' );
		} else {
			update_comment_meta( $comment_id, 'quiz_answers', $answers );
		}
		if ( ! $questions_asked ) {
			delete_comment_meta( $comment_id, 'questions_asked' );
		}
		$before = get_comment_meta( $comment_id );
		update_option( Migration_Job_Scheduler::COMPLETED_OPTION_NAME, true );
		$validation = new Quiz_Submission_Validation();

		$this->migration->run( false );
		$validation->run();
		$messages = array_map(
			static function ( $error ): string {
				return $error->get_message();
			},
			$validation->get_errors()
		);
		$preview  = ( new \Sensei_Progress_Recovery_Preview() )->inspect( 'quiz', $quiz_id, $student_id, 'comments' );
		wp_cache_delete( $comment_id, 'comment_meta' );
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Check all destination children, including any unreachable rows.
		$child_count = $wpdb->get_var( "SELECT (SELECT COUNT(*) FROM {$wpdb->prefix}sensei_lms_quiz_answers) + (SELECT COUNT(*) FROM {$wpdb->prefix}sensei_lms_quiz_grades)" );

		self::assertNull( ( new Tables_Based_Submission_Repository( $GLOBALS['wpdb'] ) )->get( $quiz_id, $student_id ), 'Incomplete history must not create a normal submission.' );
		self::assertSame( '0', $child_count, 'Incomplete history must not create answers or orphaned grades.' );
		self::assertSame( $before, get_comment_meta( $comment_id ), 'All historical fields must remain in comments.' );
		self::assertContains( $message, $messages, 'Validation must identify why the source remains unresolved.' );
		self::assertNotEmpty( $this->migration->get_errors(), 'Migration must report the incomplete copy.' );
		self::assertSame( 'unresolved', $preview['records'][0]['submission']['status'], 'The preview must not describe incomplete history as equivalent or absent.' );
		self::assertSame( array(), $preview['records'][0]['submission']['changes'], 'The preview must not propose copying an incomplete submission.' );
	}

	/**
	 * Source metadata presence does not establish retained answer history.
	 *
	 * @return array
	 */
	public static function incomplete_submissions(): array {
		return array(
			'missing answers'         => array( null, true, 'Comments-based quiz submission is incomplete: saved answers are missing or malformed.' ),
			'empty answers'           => array( array(), true, 'Comments-based quiz submission is incomplete: saved answers are missing or malformed.' ),
			'malformed answers'       => array( 'broken answer map', true, 'Comments-based quiz submission is incomplete: saved answers are missing or malformed.' ),
			'missing questions asked' => array( array( 123 => 'saved answer' ), false, 'Comments-based quiz submission is incomplete: questions asked are missing.' ),
		);
	}

	/**
	 * Question data needs real answer links and points; other valid children still migrate.
	 *
	 * @covers \Sensei\Internal\Migration\Migrations\Quiz_Migration::run
	 * @dataProvider unsupported_question_data
	 * @param bool        $has_answer Whether the second answer survives.
	 * @param string|null $points Stored second-question points, or null when absent.
	 * @param string      $message Expected unresolved source diagnostic.
	 */
	public function testRun_QuestionDataCannotBeRepresented_PreservesOnlySupportedChildren( bool $has_answer, ?string $points, string $message ): void {
		[ $student_id, , $quiz_id ] = $this->create_quiz_data( 2 );
		$comment_id                 = ( new Comments_Based_Submission_Repository() )->get( $quiz_id, $student_id )->get_id();
		$answers                    = get_comment_meta( $comment_id, 'quiz_answers', true );
		$question_ids               = array_keys( $answers );
		$question_id                = $question_ids[0];
		$second_id                  = $question_ids[1];
		$comment_id                 = ( new Comments_Based_Submission_Repository() )->get( $quiz_id, $student_id )->get_id();
		$grades                     = array( $question_id => 7 );
		if ( ! $has_answer ) {
			unset( $answers[ $second_id ] );
		}
		if ( null !== $points ) {
			$grades[ $second_id ] = $points;
		}
		update_comment_meta( $comment_id, 'quiz_answers', $answers );
		update_comment_meta( $comment_id, 'quiz_grades', $grades );
		update_comment_meta( $comment_id, 'quiz_answers_feedback', array( $second_id => 'retained feedback' ) );
		$before = get_comment_meta( $comment_id );
		update_option( Migration_Job_Scheduler::COMPLETED_OPTION_NAME, true );
		$validation = new Quiz_Submission_Validation();

		$this->migration->run( false );
		$validation->run();
		$messages = array_map(
			static function ( $error ): string {
				return $error->get_message();
			},
			$validation->get_errors()
		);
		$copied   = $this->get_quiz_data( $quiz_id, $student_id );
		wp_cache_delete( $comment_id, 'comment_meta' );

		self::assertSame(
			array(
				array(
					'question_id' => $question_id,
					'points'      => 7,
					'feedback'    => '',
				),
			),
			$copied['grades'],
			'Only the grade linked to a saved answer with numeric points may be copied.'
		);
		self::assertSame( $before, get_comment_meta( $comment_id ), 'Unsupported source data must survive intact.' );
		self::assertContains( $message, $messages, 'The incomplete child copy must remain unresolved.' );
		self::assertNotEmpty( $this->migration->get_errors(), 'Migration must report each unrepresentable source field.' );
		self::assertSame( array(), iterator_to_array( Validation_Records::get_grades_without_answers() ), 'No copied grade may lose its real answer link.' );
	}

	/**
	 * Distinct ways a grade or feedback cannot be represented safely.
	 *
	 * @return array
	 */
	public static function unsupported_question_data(): array {
		return array(
			'grade without answer'    => array( false, '2', 'Comments-based question grade cannot be copied without its saved answer.' ),
			'feedback without points' => array( true, null, 'Comments-based question feedback cannot be copied without numeric points and a saved answer.' ),
			'invalid question points' => array( true, 'NAN', 'Comments-based question grade has invalid points.' ),
		);
	}

	/**
	 * Duplicate comments are ambiguous even when one has answers and another does not.
	 *
	 * @covers \Sensei\Internal\Migration\Migrations\Quiz_Migration::run
	 */
	public function testRun_DuplicateLessonCommentsWereStored_ReportsAmbiguityWithoutCopying(): void {
		[ $student_id, $lesson_id, $quiz_id ] = $this->create_quiz_data();
		$duplicate_id                         = wp_insert_comment(
			array(
				'comment_post_ID'  => $lesson_id,
				'user_id'          => $student_id,
				'comment_type'     => 'sensei_lesson_status',
				'comment_approved' => 'passed',
			)
		);
		update_comment_meta( $duplicate_id, 'grade', '100' );
		update_option( Migration_Job_Scheduler::COMPLETED_OPTION_NAME, true );
		$validation = new Quiz_Submission_Validation();

		$this->migration->run( false );
		$validation->run();
		$messages = array_map(
			static function ( $error ): string {
				return $error->get_message();
			},
			$validation->get_errors()
		);

		self::assertSame( array( 'Comments-based quiz submission is ambiguous: duplicate lesson comments or metadata exist.' ), $messages, 'Duplicates must be reported once as ambiguous, never missing.' );
		self::assertNull( ( new Tables_Based_Submission_Repository( $GLOBALS['wpdb'] ) )->get( $quiz_id, $student_id ), 'Migration must not select or combine duplicate source copies.' );
		self::assertSame( '100', get_comment_meta( $duplicate_id, 'grade', true ), 'The duplicate source grade must remain untouched.' );
	}

	/**
	 * Migration refreshes cached quiz data when filling missing answers or grades.
	 *
	 * @covers \Sensei\Internal\Migration\Migrations\Quiz_Migration::run
	 * @dataProvider partial_quizzes
	 */
	public function testRun_PartialQuizCached_InsertsMissingData( array $missing_tables, string $retained_table ): void {
		/* Arrange. */
		global $wpdb;
		list( $user_id, $lesson_id, $quiz_id, , $answers, $grades, $feedback ) = $this->create_migrated_quiz_data( 2 );

		// phpcs:disable WordPress.DB -- Model saved parents with missing quiz data and verify persisted IDs.
		$wpdb->update( $wpdb->prefix . 'sensei_lms_quiz_submissions', array( 'final_grade' => 98.76 ), array( 'quiz_id' => $quiz_id ) );

		$retained_ids = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}{$retained_table} ORDER BY id" );
		foreach ( $missing_tables as $table ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}{$table} WHERE question_id = %d", array_key_last( $answers ) ) );
		}

		// Replace fixture caches with the partial data before migration.
		$this->set_progress_storage_mode( 'tables' );
		foreach ( array( 'sensei_answers_', 'quiz_grades_', 'sensei_answers_feedback_' ) as $prefix ) {
			delete_transient( $prefix . $user_id . '_' . $lesson_id );
		}
		Sensei()->quiz->get_user_answers( $lesson_id, $user_id );
		Sensei()->quiz->get_user_grades( $lesson_id, $user_id );
		Sensei()->quiz->get_user_answers_feedback( $lesson_id, $user_id );

		// Restore the existing row without refreshing its cached miss.
		$submission = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}sensei_lms_quiz_submissions", ARRAY_A );
		$repository = new Tables_Based_Submission_Repository( $wpdb );
		$repository->delete( $repository->get( $quiz_id, $user_id ) );
		$repository->get( $quiz_id, $user_id );
		$wpdb->insert( $wpdb->prefix . 'sensei_lms_quiz_submissions', $submission );

		/* Act. */
		$this->migration->run( false );

		/* Assert. */
		$this->assertSame( $answers, Sensei()->quiz->get_user_answers( $lesson_id, $user_id ), 'Public reads should include the restored answers.' );
		$this->assertSame( $grades, Sensei()->quiz->get_user_grades( $lesson_id, $user_id ), 'Public reads should include the restored grades.' );
		$this->assertSame( $feedback, Sensei()->quiz->get_user_answers_feedback( $lesson_id, $user_id ), 'Public reads should include the restored feedback.' );
		$final_grade = $wpdb->get_var( $wpdb->prepare( "SELECT final_grade FROM {$wpdb->prefix}sensei_lms_quiz_submissions WHERE quiz_id = %d", $quiz_id ) );
		$this->assertSame( '98.76', $final_grade, 'Missing quiz data should migrate without overwriting the existing final grade.' );
		$this->assertSame( $retained_ids, $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}{$retained_table} ORDER BY id" ), 'Previously saved records should retain their IDs.' );
		// phpcs:enable WordPress.DB
	}

	/**
	 * Writes can stop after the submission or after an answer.
	 *
	 * @return array Missing data and the parent table whose IDs must survive.
	 */
	public static function partial_quizzes(): array {
		return array(
			'answer and grade missing' => array( array( 'sensei_lms_quiz_grades', 'sensei_lms_quiz_answers' ), 'sensei_lms_quiz_submissions' ),
			'grade missing'            => array( array( 'sensei_lms_quiz_grades' ), 'sensei_lms_quiz_answers' ),
		);
	}

	/**
	 * A source grade must not be attached to a different preserved answer.
	 *
	 * @covers \Sensei\Internal\Migration\Migrations\Quiz_Migration::run
	 */
	public function testRun_ExistingAnswerMismatched_SkipsGradeAndReportsConflict(): void {
		/* Arrange. */
		global $wpdb;
		list( $user_id, $lesson_id, , $question_id ) = $this->create_migrated_quiz_data();
		$this->set_progress_storage_mode( 'comments-only' );
		Sensei()->quiz->save_user_answers( array( $question_id => 'Source answer B' ), array(), $lesson_id, $user_id );
		Sensei()->quiz->set_user_grades( array( $question_id => 7 ), $lesson_id, $user_id );
		$this->restore_progress_storage_mode();

		// phpcs:disable WordPress.DB -- Model a preserved answer that differs from the migration source.
		$wpdb->query( "DELETE FROM {$wpdb->prefix}sensei_lms_quiz_grades" );
		$expected_answer = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}sensei_lms_quiz_answers", ARRAY_A );

		/* Act. */
		$this->migration->run( false );

		/* Assert. */
		$this->assertSame( $expected_answer, $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}sensei_lms_quiz_answers", ARRAY_A ), 'The preserved answer and its ID must remain unchanged.' );
		$this->assertSame( array(), $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sensei_lms_quiz_grades", ARRAY_A ), 'A grade for a different source answer must not be inserted.' );
		// phpcs:enable WordPress.DB
		$this->assertSame(
			array( sprintf( 'Quiz answer conflict for submission ID %1$d and question ID %2$d: the saved answer differs from the source answer.', $expected_answer['submission_id'], $expected_answer['question_id'] ) ),
			$this->migration->get_errors(),
			'The mismatched answer must be reported as a migration conflict.'
		);
	}

	/**
	 * Failed parent inserts must not attach grades through existing or stale IDs.
	 *
	 * @covers \Sensei\Internal\Migration\Migrations\Quiz_Migration::run
	 * @dataProvider failing_quiz_inserts
	 */
	public function testRun_QuizInsertFailed_DoesNotInsertGradesUsingExistingIds( string $table ): void {
		global $wpdb;
		$this->create_migrated_quiz_data();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "DELETE FROM {$wpdb->prefix}sensei_lms_quiz_grades" );

		$this->with_failed_insert(
			$wpdb->prefix . $table,
			function () {
				$this->migration->run( false );
			}
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->assertSame( array(), $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sensei_lms_quiz_grades", ARRAY_A ), 'A failed insert must not attach grades to an existing parent.' );
		$this->assertStringContainsString( 'sensei_missing_progress_table', implode( ' ', $this->migration->get_errors() ), 'The insert failure should be reported.' );
	}

	/**
	 * Submission and answer failures must each stop dependent grade writes.
	 *
	 * @return array Parent tables whose inserts must fail.
	 */
	public static function failing_quiz_inserts(): array {
		return array(
			'submission insert failed' => array( 'sensei_lms_quiz_submissions' ),
			'answer insert failed'     => array( 'sensei_lms_quiz_answers' ),
		);
	}

	/**
	 * Reaching the time budget preserves forward progress for the next batch.
	 *
	 * @covers \Sensei\Internal\Migration\Migrations\Quiz_Migration::run
	 */
	public function testRun_TimeExceeded_AdvancesCursorToLastProcessedComment(): void {
		/* Arrange. */
		$this->create_quiz_data();
		$this->create_quiz_data();
		$this->create_quiz_data();

		update_option( Quiz_Migration::LAST_COMMENT_ID_OPTION_NAME, 0 );

		$migration = new Quiz_Migration( 100 );
		// Set a zero time budget so it stops after the first comment.
		$migration->set_time_budget( 0.0 );

		/* Act. */
		$migration->run( false );

		/* Assert. */
		// Cursor should have advanced to the first processed comment,
		// guaranteeing forward progress even when time is exceeded.
		$cursor = (int) get_option( Quiz_Migration::LAST_COMMENT_ID_OPTION_NAME );
		$this->assertGreaterThan( 0, $cursor );
	}

	/**
	 * Create source comments without also populating the migration destination.
	 *
	 * @param int $question_count Number of questions to create.
	 * @return array Quiz fixture IDs and source values.
	 */
	private function create_quiz_data( int $question_count = 1 ) {
		$user_id      = 1;
		$lesson_id    = $this->factory->lesson->create();
		$quiz_id      = $this->factory->quiz->create(
			array(
				'post_parent' => $lesson_id,
				'meta_input'  => array(
					'_quiz_lesson' => $lesson_id,
				),
			)
		);
		$question_ids = $this->factory->question->create_many( $question_count, array( 'quiz_id' => $quiz_id ) );
		$question_id  = $question_ids[0];
		$answers      = $this->factory->generate_user_quiz_answers( $quiz_id );
		$grades       = $this->factory->generate_user_quiz_grades( $answers );
		$feedback     = $this->factory->generate_user_answers_feedback( $quiz_id );

		$this->set_progress_storage_mode( 'comments-only' );
		try {
			Sensei()->quiz->save_user_answers( $answers, array(), $lesson_id, $user_id );
			Sensei()->quiz->set_user_grades( $grades, $lesson_id, $user_id );
			Sensei()->quiz->save_user_answers_feedback( $feedback, $lesson_id, $user_id );
			Sensei_Utils::sensei_grade_quiz( $quiz_id, 12.34, $user_id );
		} finally {
			$this->restore_progress_storage_mode();
		}

		return array( $user_id, $lesson_id, $quiz_id, $question_id, $answers, $grades, $feedback );
	}

	/**
	 * Populate both stores, then rewind the migration for another pass.
	 *
	 * @param int $question_count Number of questions to create.
	 * @return array Quiz fixture IDs and source values.
	 */
	private function create_migrated_quiz_data( int $question_count = 1 ): array {
		$fixture = $this->create_quiz_data( $question_count );
		$this->migration->run( false );
		update_option( Quiz_Migration::LAST_COMMENT_ID_OPTION_NAME, $this->initial_comment_id );

		return $fixture;
	}

	private function get_quiz_data( int $quiz_id, int $user_id ) {
		global $wpdb;

		$submission_repository = new Tables_Based_Submission_Repository( $wpdb );
		$answer_repository     = new Tables_Based_Answer_Repository( $wpdb );
		$grade_repository      = new Tables_Based_Grade_Repository( $wpdb );

		$submission = $submission_repository->get( $quiz_id, $user_id );
		if ( ! $submission ) {
			return array();
		}

		$quiz_data = array(
			'submission' => array(
				'quiz_id'     => $submission->get_quiz_id(),
				'user_id'     => $submission->get_user_id(),
				'final_grade' => $submission->get_final_grade(),
			),
		);

		$answers = $answer_repository->get_all( $submission->get_id() );
		foreach ( $answers as $answer ) {
			$quiz_data['answers'][] = array(
				'question_id' => $answer->get_question_id(),
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				'value'       => maybe_unserialize( base64_decode( $answer->get_value() ) ),
			);
		}

		$grades = $grade_repository->get_all( $submission->get_id() );
		foreach ( $grades as $grade ) {
			$quiz_data['grades'][] = array(
				'question_id' => $grade->get_question_id(),
				'points'      => $grade->get_points(),
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				'feedback'    => maybe_unserialize( base64_decode( $grade->get_feedback() ) ),
			);
		}

		return $quiz_data;
	}

	private function cleanup_custom_tables() {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "DELETE FROM {$wpdb->prefix}sensei_lms_progress" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}sensei_lms_quiz_grades" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}sensei_lms_quiz_answers" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}sensei_lms_quiz_submissions" );
		// phpcs:enable
	}
}

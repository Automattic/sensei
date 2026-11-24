<?php

namespace SenseiTest\Internal\Migration\Migrations;

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-progress-insert-failure-helpers.php';

use Sensei\Internal\Migration\Migrations\Quiz_Migration;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Tables_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Tables_Based_Grade_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Tables_Based_Submission_Repository;
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

		/* Act. */
		$this->migration->run( $dry_run = false ); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found

		/* Assert. */
		$expected = [
			'submission' => [
				'quiz_id'     => $quiz_id,
				'user_id'     => $user_id,
				'final_grade' => 12.34,
			],
			'answers'    => [
				[
					'question_id' => $question_id,
					'value'       => $answers[ $question_id ],
				],
			],
			'grades'     => [
				[
					'question_id' => $question_id,
					'points'      => $grades[ $question_id ],
					'feedback'    => $feedback[ $question_id ],
				],
			],
		];
		$this->assertSame( $expected, $this->get_quiz_data( $quiz_id, $user_id ) );
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
		list( $user_id, , $quiz_id ) = $this->create_migrated_quiz_data();

		$expected = $this->get_quiz_data( $quiz_id, $user_id );

		// phpcs:disable WordPress.DB -- Model saved parents with missing quiz data and verify persisted IDs.
		$wpdb->update( $wpdb->prefix . 'sensei_lms_quiz_submissions', array( 'final_grade' => 98.76 ), array( 'quiz_id' => $quiz_id ) );
		$expected['submission']['final_grade'] = 98.76;

		$retained_ids = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}{$retained_table} ORDER BY id" );
		foreach ( $missing_tables as $table ) {
			$wpdb->query( "DELETE FROM {$wpdb->prefix}{$table}" );
		}

		wp_cache_flush();
		$this->set_progress_storage_mode( 'tables' );
		$this->get_quiz_data( $quiz_id, $user_id );

		/* Act. */
		$this->migration->run( false );

		/* Assert. */
		$this->assertSame( $expected, $this->get_quiz_data( $quiz_id, $user_id ), 'Missing quiz data should migrate without overwriting the existing final grade.' );
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
			'submission saved' => array( array( 'sensei_lms_quiz_grades', 'sensei_lms_quiz_answers' ), 'sensei_lms_quiz_submissions' ),
			'answer saved'     => array( array( 'sensei_lms_quiz_grades' ), 'sensei_lms_quiz_answers' ),
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
	 * @return array Quiz fixture IDs and source values.
	 */
	private function create_quiz_data() {
		$user_id     = 1;
		$lesson_id   = $this->factory->lesson->create();
		$quiz_id     = $this->factory->quiz->create(
			[
				'post_parent' => $lesson_id,
				'meta_input'  => [
					'_quiz_lesson' => $lesson_id,
				],
			]
		);
		$question_id = $this->factory->question->create( [ 'quiz_id' => $quiz_id ] );
		$answers     = $this->factory->generate_user_quiz_answers( $quiz_id );
		$grades      = $this->factory->generate_user_quiz_grades( $answers );
		$feedback    = $this->factory->generate_user_answers_feedback( $quiz_id );

		$this->set_progress_storage_mode( 'comments-only' );
		try {
			Sensei()->quiz->save_user_answers( $answers, array(), $lesson_id, $user_id );
			Sensei()->quiz->set_user_grades( $grades, $lesson_id, $user_id );
			Sensei()->quiz->save_user_answers_feedback( $feedback, $lesson_id, $user_id );
			Sensei_Utils::sensei_grade_quiz( $quiz_id, 12.34, $user_id );
		} finally {
			$this->restore_progress_storage_mode();
		}

		return [ $user_id, $lesson_id, $quiz_id, $question_id, $answers, $grades, $feedback ];
	}

	/**
	 * Populate both stores, then rewind the migration for another pass.
	 *
	 * @return array Quiz fixture IDs and source values.
	 */
	private function create_migrated_quiz_data(): array {
		$fixture = $this->create_quiz_data();
		$this->migration->run( false );
		wp_cache_flush();
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
			return [];
		}

		$quiz_data = [
			'submission' => [
				'quiz_id'     => $submission->get_quiz_id(),
				'user_id'     => $submission->get_user_id(),
				'final_grade' => $submission->get_final_grade(),
			],
		];

		$answers = $answer_repository->get_all( $submission->get_id() );
		foreach ( $answers as $answer ) {
			$quiz_data['answers'][] = [
				'question_id' => $answer->get_question_id(),
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				'value'       => maybe_unserialize( base64_decode( $answer->get_value() ) ),
			];
		}

		$grades = $grade_repository->get_all( $submission->get_id() );
		foreach ( $grades as $grade ) {
			$quiz_data['grades'][] = [
				'question_id' => $grade->get_question_id(),
				'points'      => $grade->get_points(),
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				'feedback'    => maybe_unserialize( base64_decode( $grade->get_feedback() ) ),
			];
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

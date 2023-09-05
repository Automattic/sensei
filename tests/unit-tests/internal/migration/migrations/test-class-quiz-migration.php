<?php

namespace SenseiTest\Internal\Migration\Migrations;

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
	}

	protected function tearDown(): void {
		parent::tearDown();

		$this->cleanup_custom_tables();
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

	public function testRun_HasQuizSubmission_ReturnsMatchingNumberOfInserts(): void {
		/* Arrange. */
		$this->create_quiz_data();

		$this->cleanup_custom_tables();

		/* Act. */
		$this->migration->run( $dry_run = false ); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found

		/* Assert. */
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$submission_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sensei_lms_quiz_submissions" );
		$this->assertEquals( 1, $submission_count );
	}

	public function testRun_HasMoreQuizSubmissionsThanTheBatchSize_ReturnsNumberMatchingTheBatchSize(): void {
		/* Arrange. */
		$migration = new Quiz_Migration( 1 );

		$this->create_quiz_data();
		$this->create_quiz_data();

		$this->cleanup_custom_tables();

		/* Act. */
		$migration->run( $dry_run = false ); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found

		/* Assert. */
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$submission_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sensei_lms_quiz_submissions" );
		$this->assertEquals( 1, $submission_count );
	}

	public function testRun_HasQuizSubmission_CreatesQuizDataInCustomTables(): void {
		/* Arrange. */
		[ $user_id, $lesson_id, $quiz_id, $question_id, $answers, $grades, $feedback ] = $this->create_quiz_data();

		$this->cleanup_custom_tables();

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

		Sensei()->quiz->save_user_answers( $answers, [], $lesson_id, $user_id );
		Sensei()->quiz->set_user_grades( $grades, $lesson_id, $user_id );
		Sensei()->quiz->save_user_answers_feedback( $feedback, $lesson_id, $user_id );
		Sensei_Utils::sensei_grade_quiz( $quiz_id, 12.34, $user_id );

		return [ $user_id, $lesson_id, $quiz_id, $question_id, $answers, $grades, $feedback ];
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
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}sensei_lms_progress" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}sensei_lms_quiz_grades" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}sensei_lms_quiz_answers" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}sensei_lms_quiz_submissions" );
		// phpcs:enable
	}
}

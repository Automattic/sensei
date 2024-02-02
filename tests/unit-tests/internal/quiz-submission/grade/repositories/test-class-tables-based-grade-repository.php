<?php

namespace SenseiTest\Internal\Quiz_Submission\Grade\Repositories;

use Sensei\Internal\Quiz_Submission\Answer\Models\Tables_Based_Answer;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Tables_Based_Grade_Repository;
use Sensei\Internal\Quiz_Submission\Grade\Models\Tables_Based_Grade;
use Sensei\Internal\Quiz_Submission\Submission\Models\Tables_Based_Submission;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Tables_Based_Submission_Repository;

/**
 * Tests for Tables_Based_Grade_Repository class.
 *
 * @covers \Sensei\Internal\Quiz_Submission\Grade\Repositories\Tables_Based_Grade_Repository
 */
class Tables_Based_Grade_Repository_Test extends \WP_UnitTestCase {
	protected $factory;

	public function setUp(): void {
		parent::setup();
		$this->factory = new \Sensei_Factory();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testCreate_ParamsGiven_ReturnsGrade(): void {
		/* Arrange */
		$submission = $this->createMock( Tables_Based_Submission::class );
		$answer     = new Tables_Based_Answer( 2, 3, 4, 'value', new \DateTimeImmutable(), new \DateTimeImmutable() );
		$wpdb       = $this->createMock( \wpdb::class );
		$repository = new Tables_Based_Grade_Repository( $wpdb );

		/* Act */
		$grade = $repository->create( $submission, $answer, 3, 4, 'feedback' );

		/* Assert */
		$expected = [
			'answer_id'   => 2,
			'question_id' => 3,
			'points'      => 4,
			'feedback'    => 'feedback',
		];
		self::assertSame( $expected, $this->export_grade( $grade ) );
	}


	public function testCreate_ParamsGiven_InsertsData(): void {
		/* Arrange */
		$submission = $this->createMock( Tables_Based_Submission::class );
		$answer     = new Tables_Based_Answer( 2, 3, 4, 'value', new \DateTimeImmutable(), new \DateTimeImmutable() );
		$wpdb       = $this->createMock( \wpdb::class );
		$repository = new Tables_Based_Grade_Repository( $wpdb );

		/* Expect & Act */
		$wpdb->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->equalTo( 'sensei_lms_quiz_grades' ),
				$this->callback(
					function ( $data ) {
						$this->assertArrayHasKey( 'answer_id', $data );
						$this->assertArrayHasKey( 'question_id', $data );
						$this->assertArrayHasKey( 'points', $data );
						$this->assertArrayHasKey( 'feedback', $data );
						$this->assertArrayHasKey( 'created_at', $data );

						return true;
					}
				)
			);
		$repository->create( $submission, $answer, 3, 4, 'feedback' );
	}

	public function testIntegrationCreate_ParamsGiven_ReturnsGrade(): void {
		/* Arrange */
		global $wpdb;
		$repository = new Tables_Based_Grade_Repository( $wpdb );
		$submission = $this->createMock( Tables_Based_Submission::class );
		$answer     = new Tables_Based_Answer( 2, 3, 4, 'value', new \DateTimeImmutable(), new \DateTimeImmutable() );

		/* Act */
		$grade = $repository->create( $submission, $answer, 3, 4, 'feedback' );

		/* Assert */
		$expected = [
			'answer_id'   => 2,
			'question_id' => 3,
			'points'      => 4,
			'feedback'    => 'feedback',
		];
		self::assertSame( $expected, $this->export_grade( $grade ) );

		/* Cleanup */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( "{$wpdb->prefix}sensei_lms_quiz_grades", [ 'id' => $grade->get_id() ] );
	}

	public function testSaveMany_GradesGiven_UpdatesData(): void {
		/* Arrange */
		$wpdb       = $this->createMock( \wpdb::class );
		$submission = $this->createMock( Tables_Based_Submission::class );
		$submission->method( 'get_id' )->willReturn( 6 );
		$grades     = [
			new Tables_Based_Grade( 1, 2, 3, 4, 'feedback', new \DateTimeImmutable(), new \DateTimeImmutable() ),
			new Tables_Based_Grade( 5, 6, 7, 8, 'feedback2', new \DateTimeImmutable(), new \DateTimeImmutable() ),
		];
		$repository = new Tables_Based_Grade_Repository( $wpdb );

		/* Expect & Act */
		$wpdb
			->expects( $this->exactly( 2 ) )
			->method( 'update' )
			->with(
				'sensei_lms_quiz_grades',
				$this->callback(
					function ( $data ) {
						$this->assertArrayHasKey( 'points', $data );
						$this->assertArrayHasKey( 'feedback', $data );
						$this->assertArrayHasKey( 'updated_at', $data );

						return true;
					}
				),
				$this->callback(
					function ( $where ) {
						$this->assertArrayHasKey( 'id', $where );

						return true;
					}
				)
			);
		$repository->save_many( $submission, $grades );
	}

	public function testIntegrationSaveMany_GradesGiven_UpdatesData(): void {
		/* Arrange */
		global $wpdb;

		$question_id = 4;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$created = $this->create_grade_with_submission_and_answer( $question_id, 5, 'feedback' );

		$repository = new Tables_Based_Grade_Repository( $wpdb );
		$grades     = [
			new Tables_Based_Grade( $created['grade_id'], $created['answer_id'], $question_id, 4, 'feedback2', new \DateTimeImmutable(), new \DateTimeImmutable() ),
		];
		$submission = $this->createMock( Tables_Based_Submission::class );
		$submission->method( 'get_id' )->willReturn( $created['submission_id'] );

		/* Act */
		$repository->save_many( $submission, $grades );

		/* Assert */
		$expected = [
			'points'   => '4',
			'feedback' => 'feedback2',
		];
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$query = $wpdb->prepare(
			"SELECT points, feedback FROM {$wpdb->prefix}sensei_lms_quiz_grades WHERE id = %d",
			$created['grade_id']
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$actual = $wpdb->get_row( $query, ARRAY_A );
		self::assertSame( $expected, $actual );

		/* Cleanup */
		$this->cleanup( $created );
	}

	public function testDeleteAll_SubmissionIdGiven_ExecutesDeleteQuery(): void {
		/* Arrange */
		$submission = $this->createMock( Tables_Based_Submission::class );
		$submission
			->method( 'get_id' )
			->willReturn( 6 );

		$wpdb = $this->createMock( \wpdb::class );
		$wpdb
			->method( 'prepare' )
			->willReturnMap(
				[
					[
						'SELECT id FROM sensei_lms_quiz_answers WHERE submission_id = %d',
						6,
						'query1',
					],
					[
						'DELETE FROM sensei_lms_quiz_grades WHERE answer_id IN (%d, %d)',
						1,
						2,
						'query2',
					],
				]
			);
		$wpdb->method( 'get_col' )->with( 'query1' )->willReturn( [ 1, 2 ] );
		$repository = new Tables_Based_Grade_Repository( $wpdb );

		/* Expect & Act */
		$wpdb->expects( $this->once() )
			->method( 'query' )
			->with( 'query2' );
		$repository->delete_all( $submission );
	}

	public function testIntegrationDeleteAll_SubmissionIdGiven_ExecutesDeleteQuery(): void {
		/* Arrange */
		global $wpdb;

		$question_id = 4;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$created = $this->create_grade_with_submission_and_answer( $question_id, 5, 'feedback' );

		$submission_repository = new Tables_Based_Submission_Repository( $wpdb );
		$submission            = $submission_repository->get( 1, 2 );

		$repository = new Tables_Based_Grade_Repository( $wpdb );

		/* Act */
		$repository->delete_all( $submission );

		/* Assert */
		$query = $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}sensei_lms_quiz_grades WHERE answer_id = %d",
			$created['answer_id']
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$actual = $wpdb->get_col( $query );
		self::assertEmpty( $actual );

		/* Cleanup */
		$this->cleanup( $created );
	}

	public function testGetAll_ParamsGiven_ReturnsGrades(): void {
		/* Arrange */
		$wpdb = $this->createMock( \wpdb::class );
		$wpdb
			->method( 'prepare' )
			->willReturnMap(
				[
					[
						'SELECT id FROM sensei_lms_quiz_answers WHERE submission_id = %d',
						6,
						'query1',
					],
					[
						'SELECT * FROM sensei_lms_quiz_grades WHERE answer_id IN (%d, %d)',
						1,
						2,
						'query2',
					],
				]
			);
		$wpdb->method( 'get_col' )->with( 'query1' )->willReturn( [ 1, 2 ] );
		$wpdb->method( 'get_results' )->with( 'query2' )->willReturn(
			[
				(object) [
					'id'          => 1,
					'answer_id'   => 2,
					'question_id' => 3,
					'points'      => 4,
					'feedback'    => 'feedback',
					'created_at'  => '2020-01-01 00:00:00',
					'updated_at'  => '2020-01-01 00:00:00',
				],
				(object) [
					'id'          => 2,
					'answer_id'   => 3,
					'question_id' => 4,
					'points'      => 5,
					'feedback'    => 'feedback2',
					'created_at'  => '2020-01-01 00:00:00',
					'updated_at'  => '2020-01-01 00:00:00',

				],
			]
		);

		$repository = new Tables_Based_Grade_Repository( $wpdb );

		/* Act */
		$grades = $repository->get_all( 6 );

		/* Assert */
		$expected = [
			[
				'answer_id'   => 2,
				'question_id' => 3,
				'points'      => 4,
				'feedback'    => 'feedback',
			],
			[
				'answer_id'   => 3,
				'question_id' => 4,
				'points'      => 5,
				'feedback'    => 'feedback2',
			],
		];
		$this->assertSame( $expected, $this->export_grades( $grades ) );
	}

	public function testIntegrationGetAll_ParamsGiven_ReturnsGrades(): void {
		/* Arrange */
		global $wpdb;

		$question_id = 4;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$created = $this->create_grade_with_submission_and_answer( $question_id, 5, 'feedback' );

		$repository = new Tables_Based_Grade_Repository( $wpdb );

		/* Act */
		$grades = $repository->get_all( $created['submission_id'] );

		/* Assert */
		$expected = [
			[
				'answer_id'   => $created['answer_id'],
				'question_id' => $question_id,
				'points'      => 5,
				'feedback'    => 'feedback',
			],
		];
		$this->assertSame( $expected, $this->export_grades( $grades ) );

		/* Cleanup */
		$this->cleanup( $created );
	}

	private function create_grade_with_submission_and_answer( $question_id, $points, $feedback ): array {
		global $wpdb;
		$date = ( new \DateTimeImmutable() )->format( 'Y-m-d H:i:s' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . 'sensei_lms_quiz_submissions',
			[
				'quiz_id'     => 1,
				'user_id'     => 2,
				'final_grade' => 3,
				'created_at'  => $date,
				'updated_at'  => $date,
			],
			[
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
			]
		);
		$submission_id = $wpdb->insert_id;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . 'sensei_lms_quiz_answers',
			[
				'submission_id' => $submission_id,
				'question_id'   => $question_id,
				'value'         => 'value',
				'created_at'    => $date,
				'updated_at'    => $date,
			],
			[
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
			]
		);
		$answer_id = $wpdb->insert_id;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . 'sensei_lms_quiz_grades',
			[
				'answer_id'   => $answer_id,
				'question_id' => $question_id,
				'points'      => $points,
				'feedback'    => $feedback,
				'created_at'  => $date,
				'updated_at'  => $date,
			],
			[
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
			]
		);
		$grade_id = $wpdb->insert_id;

		return [
			'submission_id' => $submission_id,
			'answer_id'     => $answer_id,
			'grade_id'      => $grade_id,
		];
	}

	private function cleanup( array $ids ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->delete( "{$wpdb->prefix}sensei_lms_quiz_grades", [ 'id' => $ids['grade_id'] ] );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->delete( "{$wpdb->prefix}sensei_lms_quiz_answers", [ 'id' => $ids['answer_id'] ] );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->delete( "{$wpdb->prefix}sensei_lms_quiz_submissions", [ 'id' => $ids['submission_id'] ] );
	}

	private function export_grades( array $grades ): array {
		return array_map(
			function ( $grade ) {
				return $this->export_grade( $grade );
			},
			$grades
		);
	}

	private function export_grade( Tables_Based_Grade $grade ) {
		return [
			'answer_id'   => $grade->get_answer_id(),
			'question_id' => $grade->get_question_id(),
			'points'      => $grade->get_points(),
			'feedback'    => $grade->get_feedback(),
		];
	}
}

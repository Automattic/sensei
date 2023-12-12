<?php

namespace SenseiTest\Internal\Quiz_Submission\Submission\Repositories;

use DateTimeImmutable;
use DateTimeZone;
use Sensei\Internal\Quiz_Submission\Submission\Models\Tables_Based_Submission;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Tables_Based_Submission_Repository;
use wpdb;

/**
 * Class Tables_Based_Submission_Repository_Test
 *
 * @covers \Sensei\Internal\Quiz_Submission\Submission\Repositories\Tables_Based_Submission_Repository
 */
class Tables_Based_Submission_Repository_Test extends \WP_UnitTestCase {

	protected $factory;

	public function setUp(): void {
		parent::setup();
		$this->factory = new \Sensei_Factory();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testCreate_WhenCalled_InsertsToWpdb(): void {
		/* Arrange. */
		$wpdb       = $this->createMock( wpdb::class );
		$repository = new Tables_Based_Submission_Repository( $wpdb );

		/* Expect & Act. */
		$wpdb
			->expects( $this->once() )
			->method( 'insert' )
			->with(
				'sensei_lms_quiz_submissions',
				$this->callback(
					function( $array ) {
						return 1 === $array['quiz_id']
							&& 2 === $array['user_id']
							&& 12.34 === $array['final_grade'];
					}
				),
				[
					'%d',
					'%d',
					'%f',
					'%s',
					'%s',
				]
			);

		$repository->create( 1, 2, 12.34 );
	}

	public function testCreate_WithNoFinalGradeProvided_ReturnsSubmissionWithNullGrade(): void {
		/* Arrange. */
		$wpdb            = $this->createMock( wpdb::class );
		$wpdb->insert_id = 3;
		$repository      = new Tables_Based_Submission_Repository( $wpdb );

		/* Act. */
		$submission = $repository->create( 1, 2 );

		/* Assert. */
		$expected = [
			'id'          => 3,
			'quiz_id'     => 1,
			'user_id'     => 2,
			'final_grade' => null,
		];

		$this->assertSame( $expected, $this->export_submission( $submission ) );
	}

	public function testCreate_WithFinalGradeProvided_ReturnsSubmissionWithGrade(): void {
		/* Arrange. */
		$wpdb            = $this->createMock( wpdb::class );
		$wpdb->insert_id = 3;
		$repository      = new Tables_Based_Submission_Repository( $wpdb );

		/* Act. */
		$submission = $repository->create( 1, 2, 12.34 );

		/* Assert. */
		$expected = [
			'id'          => 3,
			'quiz_id'     => 1,
			'user_id'     => 2,
			'final_grade' => 12.34,
		];

		$this->assertSame( $expected, $this->export_submission( $submission ) );
	}

	public function testGetOrCreate_WhenSubmissionExists_ReturnsExistingSubmission(): void {
		/* Arrange. */
		$repository_mock = $this->getMockBuilder( Tables_Based_Submission_Repository::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get', 'create' ] )
			->getMock();

		/* Act & Assert. */
		$repository_mock
			->expects( $this->once() )
			->method( 'get' )
			->willReturn( $this->createMock( Tables_Based_Submission::class ) );

		$repository_mock
			->expects( $this->never() )
			->method( 'create' );

		$submission = $repository_mock->get_or_create( 1, 2 );

		$this->assertInstanceOf( Tables_Based_Submission::class, $submission );
	}

	public function testGetOrCreate_WhenSubmissionDoesNotExist_ReturnsNewSubmission(): void {
		/* Arrange. */
		$repository_mock = $this->getMockBuilder( Tables_Based_Submission_Repository::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get', 'create' ] )
			->getMock();

		/* Act & Assert. */
		$repository_mock
			->expects( $this->once() )
			->method( 'get' )
			->willReturn( null );

		$repository_mock
			->expects( $this->once() )
			->method( 'create' )
			->willReturn( $this->createMock( Tables_Based_Submission::class ) );

		$submission = $repository_mock->get_or_create( 1, 2 );

		$this->assertInstanceOf( Tables_Based_Submission::class, $submission );
	}

	public function testGet_WhenNotFound_ReturnsNull(): void {
		/* Arrange. */
		$wpdb = $this->createMock( wpdb::class );
		$wpdb
			->method( 'get_row' )
			->willReturn( null );
		$repository = new Tables_Based_Submission_Repository( $wpdb );

		/* Act. */
		$submission = $repository->get( 1, 2 );

		/* Assert. */
		$this->assertNull( $submission );
	}

	public function testGet_WhenFound_ReturnsSubmission() {
		/* Arrange. */
		$wpdb = $this->createMock( wpdb::class );
		$wpdb
			->method( 'get_row' )
			->willReturn(
				(object) [
					'id'          => 3,
					'quiz_id'     => 1,
					'user_id'     => 2,
					'final_grade' => 12.34,
					'created_at'  => '2022-01-01 00:00:00',
					'updated_at'  => '2022-01-02 00:00:00',
				]
			);
		$repository = new Tables_Based_Submission_Repository( $wpdb );

		/* Act. */
		$submission = $repository->get( 1, 2 );

		/* Assert. */
		$expected = [
			'id'          => 3,
			'quiz_id'     => 1,
			'user_id'     => 2,
			'final_grade' => 12.34,
			'created_at'  => '2022-01-01 00:00:00',
			'updated_at'  => '2022-01-02 00:00:00',
		];

		$this->assertSame( $expected, $this->export_submission_with_dates( $submission ) );
	}

	public function testGet_WithDbSubmissionFound_ReturnsSubmission(): void {
		/* Arrange. */
		global $wpdb;

		$date = ( new DateTimeImmutable() )->format( 'Y-m-d H:i:s' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . 'sensei_lms_quiz_submissions',
			[
				'quiz_id'     => 1,
				'user_id'     => 2,
				'final_grade' => 12.34,
				'created_at'  => $date,
				'updated_at'  => $date,
			],
			[
				'%d',
				'%d',
				'%f',
				'%s',
				'%s',
			]
		);
		$submission_id = $wpdb->insert_id;
		$repository    = new Tables_Based_Submission_Repository( $wpdb );

		/* Act. */
		$submission = $repository->get( 1, 2 );

		/* Assert. */
		$expected = [
			'id'          => $submission_id,
			'quiz_id'     => 1,
			'user_id'     => 2,
			'final_grade' => 12.34,
		];
		self::assertSame( $expected, $this->export_submission( $submission ) );
	}

	public function testGetQuestionIds_WhenHasQuestions_ReturnsTheQuestionIds() {
		/* Arrange. */
		global $wpdb;

		$repository = new Tables_Based_Submission_Repository( $wpdb );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . 'sensei_lms_quiz_answers',
			[
				'submission_id' => 1,
				'question_id'   => 1,
			]
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . 'sensei_lms_quiz_answers',
			[
				'submission_id' => 1,
				'question_id'   => 2,
			]
		);

		/* Act. */
		$question_ids = $repository->get_question_ids( 1 );

		/* Assert. */
		$this->assertSame( [ 1, 2 ], $question_ids );
	}

	public function testGetQuestionIds_WhenNoQuestions_ReturnsEmptyArray() {
		/* Arrange. */
		global $wpdb;

		$repository = new Tables_Based_Submission_Repository( $wpdb );

		/* Act. */
		$question_ids = $repository->get_question_ids( 1 );

		/* Assert. */
		$this->assertSame( [], $question_ids );
	}

	public function testSave_WhenCalled_UpdatesTheDatabase() {
		/* Arrange. */
		$wpdb       = $this->createMock( wpdb::class );
		$timezone   = new DateTimeZone( 'UTC' );
		$submission = new Tables_Based_Submission(
			1,
			2,
			3,
			12.34,
			new DateTimeImmutable( '2022-01-01 00:00:01', $timezone ),
			new DateTimeImmutable( '2022-01-02 00:00:01', $timezone )
		);
		$repository = new Tables_Based_Submission_Repository( $wpdb );

		/* Expect & Act. */
		$wpdb
			->expects( self::once() )
			->method( 'update' )
			->with(
				'sensei_lms_quiz_submissions',
				$this->callback(
					function ( $data ) use ( $timezone ) {
						return 12.34 === $data['final_grade']
							&& $data['updated_at'];
					}
				),
				[
					'id' => 1,
				],
				[
					'%f',
					'%s',
				],
				[
					'%d',
				]
			);

		$repository->save( $submission );
	}

	public function testDelete_WhenCalled_DeletesFromTheDatabase(): void {
		/* Arrange. */
		$wpdb       = $this->createMock( wpdb::class );
		$submission = new Tables_Based_Submission(
			1,
			2,
			3,
			12.34,
			new DateTimeImmutable( '2022-01-01 00:00:01', wp_timezone() ),
			new DateTimeImmutable( '2022-01-02 00:00:01', wp_timezone() )
		);
		$repository = new Tables_Based_Submission_Repository( $wpdb );

		/* Expect & Act. */
		$wpdb
			->expects( self::once() )
			->method( 'delete' )
			->with(
				'sensei_lms_quiz_submissions',
				[
					'quiz_id' => 2,
					'user_id' => 3,
				],
				[
					'%d',
					'%d',
				]
			);

		$repository->delete( $submission );
	}

	private function export_submission( Tables_Based_Submission $submission ): array {
		return [
			'id'          => $submission->get_id(),
			'quiz_id'     => $submission->get_quiz_id(),
			'user_id'     => $submission->get_user_id(),
			'final_grade' => $submission->get_final_grade(),
		];
	}

	private function export_submission_with_dates( Tables_Based_Submission $submission ): array {
		return array_merge(
			$this->export_submission( $submission ),
			[
				'created_at' => $submission->get_created_at()->format( 'Y-m-d H:i:s' ),
				'updated_at' => $submission->get_updated_at()->format( 'Y-m-d H:i:s' ),
			]
		);
	}
}

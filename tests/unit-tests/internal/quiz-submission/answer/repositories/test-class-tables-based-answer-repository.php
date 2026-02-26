<?php

namespace SenseiTest\Internal\Quiz_Submission\Answer\Repositories;

use DateTimeImmutable;
use Sensei\Internal\Quiz_Submission\Answer\Models\Tables_Based_Answer;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Tables_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Models\Submission_Interface;
use wpdb;

/**
 * Class Tables_Based_Answer_Repository_Test
 *
 * @covers \Sensei\Internal\Quiz_Submission\Answer\Repositories\Tables_Based_Answer_Repository
 */
class Tables_Based_Answer_Repository_Test extends \WP_UnitTestCase {
	protected $factory;

	public function setUp(): void {
		parent::setup();
		$this->factory = new \Sensei_Factory();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
		\Sensei\Internal\Services\Progress_Storage_Settings::reset_cache_enabled();
		remove_filter( 'sensei_hpps_cache_enabled', '__return_true' );
		remove_filter( 'sensei_hpps_cache_enabled', '__return_false' );
		wp_cache_flush();
	}

	public function testCreate_WhenCalled_InsertsToWpdb(): void {
		/* Arrange. */
		$submission = $this->createMock( Submission_Interface::class );
		$submission->method( 'get_id' )->willReturn( 1 );
		$wpdb       = $this->createMock( wpdb::class );
		$repository = new Tables_Based_Answer_Repository( $wpdb );

		/* Expect & Act. */
		$wpdb
			->expects( $this->once() )
			->method( 'insert' )
			->with(
				'sensei_lms_quiz_answers',
				$this->callback(
					function ( $data ) {
						return 1 === $data['submission_id']
							&& 2 === $data['question_id']
							&& 'value' === $data['value'];
					}
				),
				[
					'%d',
					'%d',
					'%s',
					'%s',
					'%s',
				]
			);

		$repository->create( $submission, 2, 'value' );
	}

	public function testCreate_WhenCalled_ReturnsAnswer(): void {
		/* Arrange. */
		$submission = $this->createMock( Submission_Interface::class );
		$submission->method( 'get_id' )->willReturn( 1 );
		$wpdb            = $this->createMock( wpdb::class );
		$wpdb->insert_id = 3;
		$repository      = new Tables_Based_Answer_Repository( $wpdb );

		/* Act. */
		$answer = $repository->create( $submission, 2, 'value' );

		/* Assert. */
		$expected = [
			'id'            => 3,
			'submission_id' => 1,
			'question_id'   => 2,
			'value'         => 'value',
		];

		$this->assertSame( $expected, $this->export_answer( $answer ) );
	}

	public function testGetAll_WhenHasNoAnswers_ReturnsEmptyArray(): void {
		/* Arrange. */
		$wpdb = $this->createMock( wpdb::class );
		$wpdb
			->method( 'get_results' )
			->willReturn( [] );
		$repository = new Tables_Based_Answer_Repository( $wpdb );

		/* Act. */
		$answers = $repository->get_all( 1 );

		/* Assert. */
		$this->assertEmpty( $answers );
	}

	public function testGetAll_WhenHasAnswers_ReturnsAnswers(): void {
		/* Arrange. */
		$wpdb = $this->createMock( wpdb::class );
		$wpdb
			->method( 'get_results' )
			->willReturn(
				[
					(object) [
						'id'            => 3,
						'submission_id' => 1,
						'question_id'   => 2,
						'value'         => 'value 1',
						'created_at'    => '2022-01-01 00:00:00',
						'updated_at'    => '2022-01-02 00:00:00',
					],
					(object) [
						'id'            => 4,
						'submission_id' => 1,
						'question_id'   => 3,
						'value'         => 'value 2',
						'created_at'    => '2022-01-02 00:00:00',
						'updated_at'    => '2022-01-03 00:00:00',
					],
				]
			);
		$repository = new Tables_Based_Answer_Repository( $wpdb );

		/* Act. */
		$answers = $repository->get_all( 1 );

		/* Assert. */
		$expected = [
			[
				'id'            => 3,
				'submission_id' => 1,
				'question_id'   => 2,
				'value'         => 'value 1',
				'created_at'    => '2022-01-01 00:00:00',
				'updated_at'    => '2022-01-02 00:00:00',
			],
			[
				'id'            => 4,
				'submission_id' => 1,
				'question_id'   => 3,
				'value'         => 'value 2',
				'created_at'    => '2022-01-02 00:00:00',
				'updated_at'    => '2022-01-03 00:00:00',
			],
		];

		$this->assertSame( $expected, array_map( [ $this, 'export_answer_with_dates' ], $answers ) );
	}

	public function testGetAll_WhenHasAnswersInDB_ReturnsAnswers(): void {
		/* Arrange. */
		global $wpdb;

		$date = ( new DateTimeImmutable() )->format( 'Y-m-d H:i:s' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . 'sensei_lms_quiz_answers',
			[
				'submission_id' => 1,
				'question_id'   => 2,
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
		$answer_id  = $wpdb->insert_id;
		$repository = new Tables_Based_Answer_Repository( $wpdb );

		/* Act. */
		$answers = $repository->get_all( 1 );

		/* Assert. */
		$expected = [
			[
				'id'            => $answer_id,
				'submission_id' => 1,
				'question_id'   => 2,
				'value'         => 'value',
			],
		];
		self::assertSame( $expected, array_map( [ $this, 'export_answer' ], $answers ) );
	}

	public function testDeleteAll_WhenCalled_DeletesAllFromTheDatabase(): void {
		/* Arrange. */
		$submission = $this->createMock( Submission_Interface::class );
		$submission->method( 'get_id' )->willReturn( 1 );

		$wpdb       = $this->createMock( wpdb::class );
		$repository = new Tables_Based_Answer_Repository( $wpdb );

		/* Expect & Act. */
		$wpdb
			->expects( self::once() )
			->method( 'delete' )
			->with(
				'sensei_lms_quiz_answers',
				[
					'submission_id' => 1,
				],
				[
					'%d',
				]
			);

		$repository->delete_all( $submission );
	}

	public function testGetAll_CacheEnabled_ReturnsCachedValueOnSecondCall(): void {
		/* Arrange. */
		global $wpdb;
		wp_cache_flush();
		add_filter( 'sensei_hpps_cache_enabled', '__return_true' );
		\Sensei\Internal\Services\Progress_Storage_Settings::reset_cache_enabled();

		$date = ( new DateTimeImmutable() )->format( 'Y-m-d H:i:s' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . 'sensei_lms_quiz_answers',
			[
				'submission_id' => 1,
				'question_id'   => 2,
				'value'         => 'value',
				'created_at'    => $date,
				'updated_at'    => $date,
			],
			[ '%d', '%d', '%s', '%s', '%s' ]
		);

		$repository = new Tables_Based_Answer_Repository( $wpdb );

		/* Act. */
		$answers1 = $repository->get_all( 1 );
		$answers2 = $repository->get_all( 1 );

		/* Assert. */
		self::assertCount( 1, $answers1 );
		self::assertCount( 1, $answers2 );
	}

	public function testGetAll_CacheEnabled_CachesEmptyResult(): void {
		/* Arrange. */
		global $wpdb;
		wp_cache_flush();
		add_filter( 'sensei_hpps_cache_enabled', '__return_true' );
		\Sensei\Internal\Services\Progress_Storage_Settings::reset_cache_enabled();

		$repository = new Tables_Based_Answer_Repository( $wpdb );

		/* Act. */
		$result1 = $repository->get_all( 999 );
		$result2 = $repository->get_all( 999 );

		/* Assert. */
		self::assertEmpty( $result1 );
		self::assertEmpty( $result2 );
	}

	public function testDeleteAll_CacheEnabled_InvalidatesCache(): void {
		/* Arrange. */
		global $wpdb;
		wp_cache_flush();
		add_filter( 'sensei_hpps_cache_enabled', '__return_true' );
		\Sensei\Internal\Services\Progress_Storage_Settings::reset_cache_enabled();

		$submission = $this->createMock( Submission_Interface::class );
		$submission->method( 'get_id' )->willReturn( 1 );

		$date = ( new DateTimeImmutable() )->format( 'Y-m-d H:i:s' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . 'sensei_lms_quiz_answers',
			[
				'submission_id' => 1,
				'question_id'   => 2,
				'value'         => 'value',
				'created_at'    => $date,
				'updated_at'    => $date,
			],
			[ '%d', '%d', '%s', '%s', '%s' ]
		);

		$repository = new Tables_Based_Answer_Repository( $wpdb );

		/* Warm cache. */
		$repository->get_all( 1 );

		/* Act. */
		$repository->delete_all( $submission );
		$result = $repository->get_all( 1 );

		/* Assert. */
		self::assertEmpty( $result );
	}

	public function testGetAll_CacheDisabled_DoesNotCache(): void {
		/* Arrange. */
		global $wpdb;
		wp_cache_flush();
		add_filter( 'sensei_hpps_cache_enabled', '__return_false' );
		\Sensei\Internal\Services\Progress_Storage_Settings::reset_cache_enabled();

		$repository = new Tables_Based_Answer_Repository( $wpdb );
		$repository->get_all( 1 );

		/* Assert. */
		$cached = wp_cache_get( '1', 'sensei_quiz_answers' );
		self::assertFalse( $cached );
	}

	public function testCreate_CacheEnabled_InvalidatesGetAllCache(): void {
		/* Arrange. */
		global $wpdb;
		wp_cache_flush();
		add_filter( 'sensei_hpps_cache_enabled', '__return_true' );
		\Sensei\Internal\Services\Progress_Storage_Settings::reset_cache_enabled();

		$date = ( new \DateTimeImmutable() )->format( 'Y-m-d H:i:s' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . 'sensei_lms_quiz_answers',
			[
				'submission_id' => 1,
				'question_id'   => 2,
				'value'         => 'value',
				'created_at'    => $date,
				'updated_at'    => $date,
			],
			[ '%d', '%d', '%s', '%s', '%s' ]
		);

		$repository = new Tables_Based_Answer_Repository( $wpdb );

		/* Warm cache. */
		$answers = $repository->get_all( 1 );
		self::assertCount( 1, $answers );

		/* Act — create a new answer for the same submission. */
		$submission = $this->createMock( Submission_Interface::class );
		$submission->method( 'get_id' )->willReturn( 1 );
		$repository->create( $submission, 3, 'new value' );

		/* Assert — get_all should return fresh data including the new answer. */
		$fresh = $repository->get_all( 1 );
		self::assertCount( 2, $fresh );
	}

	private function export_answer( Tables_Based_Answer $answer ): array {
		return [
			'id'            => $answer->get_id(),
			'submission_id' => $answer->get_submission_id(),
			'question_id'   => $answer->get_question_id(),
			'value'         => $answer->get_value(),
		];
	}

	private function export_answer_with_dates( Tables_Based_Answer $answer ): array {
		return array_merge(
			$this->export_answer( $answer ),
			[
				'created_at' => $answer->get_created_at()->format( 'Y-m-d H:i:s' ),
				'updated_at' => $answer->get_updated_at()->format( 'Y-m-d H:i:s' ),
			]
		);
	}
}

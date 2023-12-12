<?php

namespace SenseiTest\Internal\Student_Progress\Lesson_Progress\Repositories;

use DateTimeImmutable;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress_Interface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Tables_Based_Lesson_Progress;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Tables_Based_Lesson_Progress_Repository;
use Sensei_Course;
use wpdb;

/**
 * Tests for Tables_Based_Lesson_Progress_Repository.
 *
 * @covers \Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Tables_Based_Lesson_Progress_Repository
 */
class Tables_Based_Lesson_Progress_Repository_Test extends \WP_UnitTestCase {
	/**
	 * Sensei factory.
	 *
	 * @var \Sensei_Factory
	 */
	protected $factory;

	public function setUp(): void {
		parent::setUp();
		$this->factory = new \Sensei_Factory();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testCreate_ParamsGiven_InsertsToWpdb(): void {
		/* Arrange. */
		$wpdb       = $this->createMock( wpdb::class );
		$repository = new Tables_Based_Lesson_Progress_Repository( $wpdb );

		/* Expect & Act. */
		$wpdb
			->expects( $this->once() )
			->method( 'insert' )
			->with(
				'sensei_lms_progress',
				$this->callback(
					function( $array ) {
						return isset( $array['post_id'], $array['user_id'], $array['type'], $array['status'] )
							&& array_key_exists( 'parent_post_id', $array )
							&& 1 === $array['post_id']
							&& 2 === $array['user_id']
							&& 'lesson' === $array['type']
							&& 'in-progress' === $array['status']
							&& is_null( $array['parent_post_id'] );
					}
				),
				[
					'%d',
					'%d',
					null,
					'%s',
					'%s',
					'%s',
					null,
					'%s',
					'%s',
				]
			);
		$repository->create( 1, 2 );
	}

	public function testCreate_ParamsGiven_ReturnsMatchingCourseProgress(): void {
		/* Arrange. */
		$wpdb            = $this->createMock( wpdb::class );
		$wpdb->insert_id = 3;
		$repository      = new Tables_Based_Lesson_Progress_Repository( $wpdb );

		/* Act. */
		$progress = $repository->create( 1, 2 );

		/* Assert. */
		$expected = [
			'id'        => 3,
			'lesson_id' => 1,
			'user_id'   => 2,
			'status'    => 'in-progress',
		];
		self::assertSame( $expected, $this->export_progress( $progress ) );
	}

	public function testGet_NotFound_ReturnsNull(): void {
		/* Arrange. */
		$wpdb = $this->createMock( wpdb::class );
		$wpdb
			->method( 'get_row' )
			->willReturn( null );
		$repository = new Tables_Based_Lesson_Progress_Repository( $wpdb );

		/* Act. */
		$progress = $repository->get( 1, 2 );

		/* Assert. */
		self::assertNull( $progress );
	}

	public function testGet_WithRealDbProgressFound_ReturnsMatchingProgress(): void {
		/* Arrange. */
		$date = ( new DateTimeImmutable() )->format( 'Y-m-d H:i:s' );
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . 'sensei_lms_progress',
			[
				'post_id'        => 1,
				'user_id'        => 2,
				'parent_post_id' => null,
				'type'           => 'lesson',
				'status'         => 'in-progress',
				'started_at'     => $date,
				'completed_at'   => null,
				'created_at'     => $date,
				'updated_at'     => $date,
			],
			[
				'%d',
				'%d',
				null,
				'%s',
				'%s',
				'%s',
				null,
				'%s',
				'%s',
			]
		);
		$lesson_progress_id = $wpdb->insert_id;

		$repository = new Tables_Based_Lesson_Progress_Repository( $wpdb );

		/* Act. */
		$progress = $repository->get( 1, 2 );

		/* Assert. */
		$expected = [
			'id'        => $lesson_progress_id,
			'lesson_id' => 1,
			'user_id'   => 2,
			'status'    => 'in-progress',
		];
		self::assertSame( $expected, $this->export_progress( $progress ) );
	}

	public function testGet_ProgressFound_ReturnsMatchingProgress(): void {
		/* Arrange. */
		$wpdb = $this->createMock( wpdb::class );
		$wpdb
			->method( 'get_row' )
			->willReturn(
				(object) [
					'id'             => 3,
					'post_id'        => 1,
					'user_id'        => 2,
					'parent_post_id' => null,
					'type'           => 'lesson',
					'status'         => 'in-progress',
					'created_at'     => '2022-01-01 00:00:00',
					'updated_at'     => '2022-01-02 00:00:00',
					'started_at'     => '2022-01-03 00:00:00',
					'completed_at'   => '2022-01-04 00:00:00',
				]
			);
		$repository = new Tables_Based_Lesson_Progress_Repository( $wpdb );

		/* Act. */
		$progress = $repository->get( 1, 2 );

		/* Assert. */
		$expected = [
			'id'           => 3,
			'lesson_id'    => 1,
			'user_id'      => 2,
			'status'       => 'in-progress',
			'created_at'   => '2022-01-01 00:00:00',
			'updated_at'   => '2022-01-02 00:00:00',
			'started_at'   => '2022-01-03 00:00:00',
			'completed_at' => '2022-01-04 00:00:00',
		];
		self::assertSame( $expected, $this->export_progress_with_dates( $progress ) );
	}

	public function testHas_NotFound_ReturnsFalse(): void {
		/* Arrange. */
		$wpdb = $this->createMock( wpdb::class );
		$wpdb
			->method( 'get_var' )
			->willReturn( 0 );
		$repository = new Tables_Based_Lesson_Progress_Repository( $wpdb );

		/* Act. */
		$has = $repository->has( 1, 2 );

		/* Assert. */
		self::assertFalse( $has );
	}

	public function testHas_ProgressFound_ReturnsFalse(): void {
		/* Arrange. */
		$wpdb = $this->createMock( wpdb::class );
		$wpdb
			->method( 'get_var' )
			->willReturn( 2 );
		$repository = new Tables_Based_Lesson_Progress_Repository( $wpdb );

		/* Act. */
		$has = $repository->has( 1, 2 );

		/* Assert. */
		self::assertTrue( $has );
	}

	public function testHas_WithRealDbAndProgressFound_ReturnsFalse(): void {
		/* Arrange. */
		$date = ( new DateTimeImmutable() )->format( 'Y-m-d H:i:s' );

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . 'sensei_lms_progress',
			[
				'post_id'        => 1,
				'user_id'        => 2,
				'parent_post_id' => null,
				'type'           => 'lesson',
				'status'         => 'in-progress',
				'started_at'     => $date,
				'completed_at'   => null,
				'created_at'     => $date,
				'updated_at'     => $date,
			],
			[
				'%d',
				'%d',
				null,
				'%s',
				'%s',
				'%s',
				null,
				'%s',
				'%s',
			]
		);

		$repository = new Tables_Based_Lesson_Progress_Repository( $wpdb );

		/* Act. */
		$has = $repository->has( 1, 2 );

		/* Assert. */
		self::assertTrue( $has );
	}

	public function testSave_ProgressGiven_CallsWpdbUpdate(): void {
		/* Arrange. */
		$wpdb       = $this->createMock( wpdb::class );
		$progress   = new Tables_Based_Lesson_Progress(
			1,
			2,
			3,
			'complete',
			new DateTimeImmutable( '2022-01-01 00:00:01', wp_timezone() ),
			new DateTimeImmutable( '2022-01-02 00:00:01', wp_timezone() ),
			new DateTimeImmutable( '2022-01-03 00:00:01', wp_timezone() ),
			new DateTimeImmutable( '2022-01-04 00:00:01', wp_timezone() )
		);
		$repository = new Tables_Based_Lesson_Progress_Repository( $wpdb );

		/* Expect & Act. */
		$wpdb
			->expects( self::once() )
			->method( 'update' )
			->with(
				'sensei_lms_progress',
				$this->callback(
					function ( $data ) {
						return isset( $data['status'], $data['started_at'], $data['completed_at'] )
							&& 'complete' === $data['status']
							&& '2022-01-01 00:00:01' === $data['started_at']
							&& '2022-01-02 00:00:01' === $data['completed_at'];
					}
				),
				[
					'id' => 1,
				],
				[
					'%s',
					'%s',
					'%s',
					'%s',
				],
				[
					'%d',
				]
			);
		$repository->save( $progress );
	}

	public function testSave_NonTablesBasedProgressGiven_ThrowsException(): void {
		/* Arrange. */
		$progress   = $this->createMock( Lesson_Progress_Interface::class );
		$repository = new Tables_Based_Lesson_Progress_Repository( $this->createMock( wpdb::class ) );

		/* Expect & Act. */
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Expected Tables_Based_Lesson_Progress, got ' . get_class( $progress ) . '.' );
		$repository->save( $progress );
	}

	public function testDelete_ProgressGiven_CallsWpdbDelete(): void {
		/* Arrange. */
		$wpdb       = $this->createMock( wpdb::class );
		$progress   = new Tables_Based_Lesson_Progress(
			1,
			2,
			3,
			'complete',
			new DateTimeImmutable( '2022-01-01 00:00:01', wp_timezone() ),
			new DateTimeImmutable( '2022-01-02 00:00:01', wp_timezone() ),
			new DateTimeImmutable( '2022-01-03 00:00:01', wp_timezone() ),
			new DateTimeImmutable( '2022-01-04 00:00:01', wp_timezone() )
		);
		$repository = new Tables_Based_Lesson_Progress_Repository( $wpdb );

		/* Expect & Act. */
		$wpdb
			->expects( self::once() )
			->method( 'delete' )
			->with(
				'sensei_lms_progress',
				[
					'post_id' => 2,
					'user_id' => 3,
					'type'    => 'lesson',
				],
				[
					'%d',
					'%d',
					'%s',
				]
			);
		$repository->delete( $progress );
	}

	public function testDeleteForLesson_LessonIdGiven_CallsWpdbDelete(): void {
		/* Arrange. */
		$wpdb       = $this->createMock( wpdb::class );
		$repository = new Tables_Based_Lesson_Progress_Repository( $wpdb );

		/* Expect & Act. */
		$wpdb
			->expects( self::once() )
			->method( 'delete' )
			->with(
				'sensei_lms_progress',
				[
					'post_id' => 2,
					'type'    => 'lesson',
				],
				[
					'%d',
					'%s',
				]
			);
		$repository->delete_for_lesson( 2 );
	}

	public function testDeleteForUser_UserIdGiven_CallsWpdbDelete(): void {
		/* Arrange. */
		$wpdb       = $this->createMock( wpdb::class );
		$repository = new Tables_Based_Lesson_Progress_Repository( $wpdb );

		/* Expect & Act. */
		$wpdb
			->expects( self::once() )
			->method( 'delete' )
			->with(
				'sensei_lms_progress',
				[
					'user_id' => 2,
					'type'    => 'lesson',
				],
				[
					'%d',
					'%s',
				]
			);
		$repository->delete_for_user( 2 );
	}

	public function testCount_ParamsGiven_ReturnsMatchingValue(): void {
		/* Arrange. */
		$course = $this->createMock( Sensei_Course::class );
		$course->method( 'course_lessons' )->with( 1, 'publish', 'ids' )->willReturn( [ 2, 3, 4 ] );

		$initial_course  = Sensei()->course;
		Sensei()->course = $course;

		$wpdb = $this->createMock( wpdb::class );
		$wpdb
			->method( 'prepare' )
			->with(
				'SELECT COUNT(*) FROM sensei_lms_progress WHERE post_id IN (2,3,4) AND user_id = %d AND type = %s',
				2,
				'lesson'
			)
			->willReturn( 'query' );
		$wpdb
			->method( 'get_var' )
			->with( 'query' )
			->willReturn( 5 );

		$repository = new Tables_Based_Lesson_Progress_Repository( $wpdb );

		/* Act. */
		$actual = $repository->count( 1, 2 );

		/* Assert. */
		self::assertSame( 5, $actual );

		Sensei()->course = $initial_course;
	}

	public function testIntegrationFind_ArgumentsGiven_ReturnsMatchingProgress(): void {
		/* Arrange. */
		global $wpdb;
		$lesson_ids = $this->factory->lesson->create_many( 5 );
		$user_id    = $this->factory->user->create();

		$repository       = new Tables_Based_Lesson_Progress_Repository( $wpdb );
		$created_progress = [];
		foreach ( $lesson_ids as $lesson_id ) {
			$created_progress[] = $repository->create( $lesson_id, $user_id );
		}

		$expected = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$progress = $created_progress[ $i ];
			$progress->complete();
			$repository->save( $progress );
			$expected[] = $this->export_progress( $progress );
		}

		/* Act. */
		$found_progress = $repository->find(
			array(
				'user_id' => $user_id,
				'status'  => 'complete',
			)
		);
		$actual         = array_map( array( $this, 'export_progress' ), $found_progress );

		/* Assert. */
		self::assertSame( $expected, $actual );
	}

	private function export_progress( Lesson_Progress_Interface $progress ): array {
		return [
			'id'        => $progress->get_id(),
			'lesson_id' => $progress->get_lesson_id(),
			'user_id'   => $progress->get_user_id(),
			'status'    => $progress->get_status(),
		];
	}

	private function export_progress_with_dates( ?Lesson_Progress_Interface $progress ): array {
		return [
			'id'           => $progress->get_id(),
			'lesson_id'    => $progress->get_lesson_id(),
			'user_id'      => $progress->get_user_id(),
			'status'       => $progress->get_status(),
			'created_at'   => $progress->get_created_at()->format( 'Y-m-d H:i:s' ),
			'updated_at'   => $progress->get_updated_at()->format( 'Y-m-d H:i:s' ),
			'started_at'   => $progress->get_started_at()->format( 'Y-m-d H:i:s' ),
			'completed_at' => $progress->get_completed_at()->format( 'Y-m-d H:i:s' ),
		];
	}
}

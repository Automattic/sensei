<?php

namespace SenseiTest\Internal\Student_Progress\Course_Progress\Repositories;

use DateTimeImmutable;
use Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress_Interface;
use Sensei\Internal\Student_Progress\Course_Progress\Models\Tables_Based_Course_Progress;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Tables_Based_Course_Progress_Repository;
use wpdb;

/**
 * Tests for Tables_Based_Course_Progress_Repository.
 *
 * @covers \Sensei\Internal\Student_Progress\Course_Progress\Repositories\Tables_Based_Course_Progress_Repository
 */
class Tables_Based_Course_Progress_Repository_Test extends \WP_UnitTestCase {
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
		\Sensei\Internal\Services\Progress_Storage_Settings::reset_cache_enabled();
		remove_filter( 'sensei_hpps_cache_enabled', '__return_true' );
		remove_filter( 'sensei_hpps_cache_enabled', '__return_false' );
		wp_cache_flush();
	}

	public function testCreate_ParamsGiven_InsertsToWpdb(): void {
		/* Arrange. */
		$wpdb       = $this->createMock( wpdb::class );
		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );

		/* Expect & Act. */
		$wpdb
			->expects( $this->once() )
			->method( 'insert' )
			->with(
				'sensei_lms_progress',
				$this->callback(
					function ( $data ) {
						return isset( $data['post_id'], $data['user_id'], $data['type'], $data['status'] )
							&& array_key_exists( 'parent_post_id', $data )
							&& 1 === $data['post_id']
							&& 2 === $data['user_id']
							&& 'course' === $data['type']
							&& 'in-progress' === $data['status']
							&& is_null( $data['parent_post_id'] );
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
		$repository      = new Tables_Based_Course_Progress_Repository( $wpdb );

		/* Act. */
		$progress = $repository->create( 1, 2 );

		/* Assert. */
		$expected = [
			'id'        => 3,
			'course_id' => 1,
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
		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );

		/* Act. */
		$progress = $repository->get( 1, 2 );

		/* Assert. */
		self::assertNull( $progress );
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
					'type'           => 'course',
					'status'         => 'in-progress',
					'created_at'     => '2022-01-01 00:00:00',
					'updated_at'     => '2022-01-02 00:00:00',
					'started_at'     => '2022-01-03 00:00:00',
					'completed_at'   => '2022-01-04 00:00:00',
				]
			);
		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );

		/* Act. */
		$progress = $repository->get( 1, 2 );

		/* Assert. */
		$expected = [
			'id'           => 3,
			'course_id'    => 1,
			'user_id'      => 2,
			'status'       => 'in-progress',
			'created_at'   => '2022-01-01 00:00:00',
			'updated_at'   => '2022-01-02 00:00:00',
			'started_at'   => '2022-01-03 00:00:00',
			'completed_at' => '2022-01-04 00:00:00',
		];
		self::assertSame( $expected, $this->export_progress_with_dates( $progress ) );
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
				'type'           => 'course',
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
		$course_progress_id = $wpdb->insert_id;

		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );

		/* Act. */
		$progress = $repository->get( 1, 2 );

		/* Assert. */
		$expected = [
			'id'        => $course_progress_id,
			'course_id' => 1,
			'user_id'   => 2,
			'status'    => 'in-progress',
		];
		self::assertSame( $expected, $this->export_progress( $progress ) );
	}

	public function testHas_NotFound_ReturnsFalse(): void {
		/* Arrange. */
		$wpdb = $this->createMock( wpdb::class );
		$wpdb
			->method( 'get_row' )
			->willReturn( null );
		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );

		/* Act. */
		$has = $repository->has( 1, 2 );

		/* Assert. */
		self::assertFalse( $has );
	}

	public function testHas_ProgressFound_ReturnsTrue(): void {
		/* Arrange. */
		$wpdb = $this->createMock( wpdb::class );
		$wpdb
			->method( 'get_row' )
			->willReturn(
				(object) [
					'id'           => 3,
					'post_id'      => 1,
					'user_id'      => 2,
					'type'         => 'course',
					'status'       => 'in-progress',
					'created_at'   => '2022-01-01 00:00:00',
					'updated_at'   => '2022-01-02 00:00:00',
					'started_at'   => '2022-01-03 00:00:00',
					'completed_at' => '2022-01-04 00:00:00',
				]
			);
		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );

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
				'type'           => 'course',
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

		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );

		/* Act. */
		$has = $repository->has( 1, 2 );

		/* Assert. */
		self::assertTrue( $has );
	}

	public function testSave_NonTablesBasedProgressGiven_TrowsException(): void {
		/* Arrange. */
		$progress   = $this->createMock( Course_Progress_Interface::class );
		$repository = new Tables_Based_Course_Progress_Repository( $this->createMock( wpdb::class ) );

		/* Expect & Act. */
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Expected Tables_Based_Course_Progress, got ' . get_class( $progress ) . '.' );
		$repository->save( $progress );
	}

	public function testSave_ProgressGiven_CallsWpdbUpdate(): void {
		/* Arrange. */
		$wpdb       = $this->createMock( wpdb::class );
		$progress   = new Tables_Based_Course_Progress(
			1,
			2,
			3,
			'complete',
			new DateTimeImmutable( '2022-01-01 00:00:01', wp_timezone() ),
			new DateTimeImmutable( '2022-01-02 00:00:01', wp_timezone() ),
			new DateTimeImmutable( '2022-01-03 00:00:01', wp_timezone() ),
			new DateTimeImmutable( '2022-01-04 00:00:01', wp_timezone() )
		);
		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );

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

	public function testDelete_ProgressGiven_CallsWpdbDelete(): void {
		/* Arrange. */
		$wpdb       = $this->createMock( wpdb::class );
		$progress   = new Tables_Based_Course_Progress(
			1,
			2,
			3,
			'complete',
			new DateTimeImmutable( '2022-01-01 00:00:01', wp_timezone() ),
			new DateTimeImmutable( '2022-01-02 00:00:01', wp_timezone() ),
			new DateTimeImmutable( '2022-01-03 00:00:01', wp_timezone() ),
			new DateTimeImmutable( '2022-01-04 00:00:01', wp_timezone() )
		);
		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );

		/* Expect & Act. */
		$wpdb
			->expects( self::once() )
			->method( 'delete' )
			->with(
				'sensei_lms_progress',
				[
					'post_id' => 2,
					'user_id' => 3,
					'type'    => 'course',
				],
				[
					'%d',
					'%d',
					'%s',
				]
			);
		$repository->delete( $progress );
	}

	public function testDeleteForCourse_CourseIdGiven_CallsWpdbDelete(): void {
		/* Arrange. */
		$wpdb       = $this->createMock( wpdb::class );
		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );

		/* Expect & Act. */
		$wpdb
			->expects( self::once() )
			->method( 'delete' )
			->with(
				'sensei_lms_progress',
				[
					'post_id' => 2,
					'type'    => 'course',
				],
				[
					'%d',
					'%s',
				]
			);
		$repository->delete_for_course( 2 );
	}

	public function testDeleteForUser_UserIdGiven_CallsWpdbDelete(): void {
		/* Arrange. */
		$wpdb       = $this->createMock( wpdb::class );
		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );

		/* Expect & Act. */
		$wpdb
			->expects( self::once() )
			->method( 'delete' )
			->with(
				'sensei_lms_progress',
				[
					'user_id' => 2,
					'type'    => 'course',
				],
				[
					'%d',
					'%s',
				]
			);
		$repository->delete_for_user( 2 );
	}

	public function testIntegrationFind_ArgumentsGiven_ReturnsMatchingProgress(): void {
		/* Arrange. */
		global $wpdb;
		$course_ids = $this->factory->course->create_many( 5 );
		$user_id    = $this->factory->user->create();

		$repository       = new Tables_Based_Course_Progress_Repository( $wpdb );
		$created_progress = [];
		foreach ( $course_ids as $course_id ) {
			$created_progress[] = $repository->create( $course_id, $user_id );
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
		usort(
			$actual,
			function ( $a, $b ) {
				return $a['course_id'] <=> $b['course_id'];
			}
		);

		/* Assert. */
		self::assertSame( $expected, $actual );
	}

	public function testCreate_CacheEnabled_FailedInsertDoesNotCache(): void {
		/* Arrange. */
		$wpdb            = $this->createMock( wpdb::class );
		$wpdb->insert_id = 0;
		add_filter( 'sensei_hpps_cache_enabled', '__return_true' );
		\Sensei\Internal\Services\Progress_Storage_Settings::reset_cache_enabled();

		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );
		wp_cache_flush();

		/* Act. */
		$repository->create( 1, 2 );

		/* Assert — nothing should be cached when insert fails (no prefix marker created). */
		$cache_prefix = wp_cache_get( 'sensei_sensei_course_progress_cache_prefix', 'sensei_course_progress' );
		self::assertFalse( $cache_prefix );
	}

	public function testGet_CacheEnabled_ReturnsCachedValueOnSecondCall(): void {
		/* Arrange. */
		global $wpdb;
		wp_cache_flush();
		add_filter( 'sensei_hpps_cache_enabled', '__return_true' );
		\Sensei\Internal\Services\Progress_Storage_Settings::reset_cache_enabled();

		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );
		$progress   = $repository->create( 1, 2 );

		/* Act - second call should use cache. */
		$cached_progress = $repository->get( 1, 2 );

		/* Assert. */
		self::assertSame( $progress->get_id(), $cached_progress->get_id() );
	}

	public function testGet_CacheEnabled_CachesNullAsNotFound(): void {
		/* Arrange. */
		global $wpdb;
		wp_cache_flush();
		add_filter( 'sensei_hpps_cache_enabled', '__return_true' );
		\Sensei\Internal\Services\Progress_Storage_Settings::reset_cache_enabled();

		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );

		/* Act - first call caches __not_found__, second returns null from cache. */
		$result1 = $repository->get( 999, 999 );
		$result2 = $repository->get( 999, 999 );

		/* Assert. */
		self::assertNull( $result1 );
		self::assertNull( $result2 );
	}

	public function testSave_CacheEnabled_InvalidatesCache(): void {
		/* Arrange. */
		global $wpdb;
		wp_cache_flush();
		add_filter( 'sensei_hpps_cache_enabled', '__return_true' );
		\Sensei\Internal\Services\Progress_Storage_Settings::reset_cache_enabled();

		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );
		$progress   = $repository->create( 1, 2 );

		/* Warm cache. */
		$repository->get( 1, 2 );

		/* Act. */
		$progress->complete();
		$repository->save( $progress );
		$fresh = $repository->get( 1, 2 );

		/* Assert - should get fresh data, not stale cache. */
		self::assertSame( 'complete', $fresh->get_status() );
	}

	public function testDelete_CacheEnabled_InvalidatesCache(): void {
		/* Arrange. */
		global $wpdb;
		wp_cache_flush();
		add_filter( 'sensei_hpps_cache_enabled', '__return_true' );
		\Sensei\Internal\Services\Progress_Storage_Settings::reset_cache_enabled();

		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );
		$progress   = $repository->create( 1, 2 );

		/* Warm cache. */
		$repository->get( 1, 2 );

		/* Act. */
		$repository->delete( $progress );
		$result = $repository->get( 1, 2 );

		/* Assert. */
		self::assertNull( $result );
	}

	public function testGet_CacheDisabled_DoesNotCache(): void {
		/* Arrange. */
		global $wpdb;
		wp_cache_flush();
		add_filter( 'sensei_hpps_cache_enabled', '__return_false' );
		\Sensei\Internal\Services\Progress_Storage_Settings::reset_cache_enabled();

		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );
		$repository->create( 1, 2 );

		/* Act. */
		$repository->get( 1, 2 );

		/* Assert - verify no cache prefix marker was created for this group. */
		$cache_prefix = wp_cache_get( 'sensei_sensei_course_progress_cache_prefix', 'sensei_course_progress' );
		self::assertFalse( $cache_prefix );
	}

	public function testHas_CacheEnabled_DelegatesToGet(): void {
		/* Arrange. */
		global $wpdb;
		wp_cache_flush();
		add_filter( 'sensei_hpps_cache_enabled', '__return_true' );
		\Sensei\Internal\Services\Progress_Storage_Settings::reset_cache_enabled();

		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );
		$repository->create( 1, 2 );

		/* Act. */
		$has = $repository->has( 1, 2 );

		/* Assert. */
		self::assertTrue( $has );
	}

	public function testDeleteForCourse_CacheEnabled_InvalidatesCacheGroup(): void {
		/* Arrange. */
		global $wpdb;
		wp_cache_flush();
		add_filter( 'sensei_hpps_cache_enabled', '__return_true' );
		\Sensei\Internal\Services\Progress_Storage_Settings::reset_cache_enabled();

		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );
		$repository->create( 1, 2 );

		/* Warm cache. */
		$repository->get( 1, 2 );

		/* Act. */
		$repository->delete_for_course( 1 );
		$result = $repository->get( 1, 2 );

		/* Assert - should be null since data was deleted, and cache was invalidated. */
		self::assertNull( $result );
	}

	public function testDeleteForUser_CacheEnabled_InvalidatesCacheGroup(): void {
		/* Arrange. */
		global $wpdb;
		wp_cache_flush();
		add_filter( 'sensei_hpps_cache_enabled', '__return_true' );
		\Sensei\Internal\Services\Progress_Storage_Settings::reset_cache_enabled();

		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );
		$repository->create( 1, 2 );

		/* Warm cache. */
		$repository->get( 1, 2 );

		/* Act. */
		$repository->delete_for_user( 2 );
		$result = $repository->get( 1, 2 );

		/* Assert. */
		self::assertNull( $result );
	}

	public function testGet_CacheEnabled_CreateOverwritesNotFoundSentinel(): void {
		/* Arrange. */
		global $wpdb;
		wp_cache_flush();
		add_filter( 'sensei_hpps_cache_enabled', '__return_true' );
		\Sensei\Internal\Services\Progress_Storage_Settings::reset_cache_enabled();

		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );

		/* Cache __not_found__ sentinel. */
		$result = $repository->get( 1, 2 );
		self::assertNull( $result );

		/* Act — create overwrites the sentinel. */
		$created = $repository->create( 1, 2 );
		$fresh   = $repository->get( 1, 2 );

		/* Assert — get() should return the created object, not null. */
		self::assertNotNull( $fresh );
		self::assertSame( $created->get_id(), $fresh->get_id() );
	}

	public function testFind_CacheEnabled_WarmsIndividualCaches(): void {
		/* Arrange. */
		global $wpdb;
		wp_cache_flush();
		add_filter( 'sensei_hpps_cache_enabled', '__return_true' );
		\Sensei\Internal\Services\Progress_Storage_Settings::reset_cache_enabled();

		$user_id    = $this->factory->user->create();
		$course_ids = $this->factory->course->create_many( 3 );
		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );

		foreach ( $course_ids as $course_id ) {
			$repository->create( $course_id, $user_id );
		}
		wp_cache_flush();

		/* Act. */
		$repository->find( array( 'user_id' => $user_id ) );

		/* Assert - individual caches should be warm. */
		$result = $repository->get( $course_ids[0], $user_id );
		self::assertNotNull( $result );
		self::assertSame( $course_ids[0], $result->get_course_id() );
	}

	private function export_progress( Course_Progress_Interface $progress ): array {
		return [
			'id'        => $progress->get_id(),
			'course_id' => $progress->get_course_id(),
			'user_id'   => $progress->get_user_id(),
			'status'    => $progress->get_status(),
		];
	}

	private function export_progress_with_dates( ?Course_Progress_Interface $progress ): array {
		return [
			'id'           => $progress->get_id(),
			'course_id'    => $progress->get_course_id(),
			'user_id'      => $progress->get_user_id(),
			'status'       => $progress->get_status(),
			'created_at'   => $progress->get_created_at()->format( 'Y-m-d H:i:s' ),
			'updated_at'   => $progress->get_updated_at()->format( 'Y-m-d H:i:s' ),
			'started_at'   => $progress->get_started_at()->format( 'Y-m-d H:i:s' ),
			'completed_at' => $progress->get_completed_at()->format( 'Y-m-d H:i:s' ),
		];
	}
}

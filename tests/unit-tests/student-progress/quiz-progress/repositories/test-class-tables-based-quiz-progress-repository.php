<?php

namespace SenseiTest\Student_Progress\Quiz_Progress\Repositories;

use Sensei\Student_Progress\Quiz_Progress\Models\Quiz_Progress;
use Sensei\Student_Progress\Quiz_Progress\Repositories\Tables_Based_Quiz_Progress_Repository;

/**
 * Tests for Tables_Based_Quiz_Progress_Repository.
 *
 * @covers \Sensei\Student_Progress\Quiz_Progress\Repositories\Tables_Based_Quiz_Progress_Repository
 */
class Tables_Based_Quiz_Progress_Repository_Test extends \WP_UnitTestCase {
	public function testCreate_ParamsGiven_InsertsToWpdb(): void {
		/* Arrange. */
		$wpdb       = $this->createMock( \wpdb::class );
		$repository = new Tables_Based_Quiz_Progress_Repository( $wpdb );

		/* Expect & Act. */
		$wpdb
			->expects( $this->once() )
			->method( 'insert' )
			->with(
				'sensei_lms_progress',
				$this->callback(
					function( $array ) {
						if ( ! isset( $array['post_id'] ) || $array['post_id'] !== 1 ) {
							return false;
						}
						if ( ! isset( $array['user_id'] ) || $array['user_id'] !== 2 ) {
							return false;
						}
						if ( ! array_key_exists( 'parent_post_id', $array ) || ! is_null( $array['parent_post_id'] ) ) {
							return false;
						}
						if ( ! isset( $array['type'] ) || $array['type'] !== 'quiz' ) {
							return false;
						}
						if ( ! isset( $array['status'] ) || $array['status'] !== 'in-progress' ) {
							return false;
						}
						return true;
					}
				),
				[
					'%d',
					'%d',
					null,
					'%s',
					'%s',
					'%d',
					null,
					'%d',
					'%d',
				]
			);
		$repository->create( 1, 2 );
	}

	public function testCreate_ParamsGiven_ReturnsMatchingCourseProgress(): void {
		/* Arrange. */
		$wpdb            = $this->createMock( \wpdb::class );
		$wpdb->insert_id = 3;
		$repository      = new Tables_Based_Quiz_Progress_Repository( $wpdb );

		/* Act. */
		$progress = $repository->create( 1, 2 );

		/* Assert. */
		$expected = [
			'id'      => 3,
			'quiz_id' => 1,
			'user_id' => 2,
			'status'  => 'in-progress',
		];
		self::assertSame( $expected, $this->export_progress( $progress ) );
	}

	public function testGet_NotFound_ReturnsNull(): void {
		/* Arrange. */
		$wpdb = $this->createMock( \wpdb::class );
		$wpdb
			->method( 'get_row' )
			->willReturn( null );
		$repository = new Tables_Based_Quiz_Progress_Repository( $wpdb );

		/* Act. */
		$progress = $repository->get( 1, 2 );

		/* Assert. */
		self::assertNull( $progress );
	}

	public function testGet_ProgressFound_ReturnsMatchingProgress(): void {
		/* Arrange. */
		$wpdb = $this->createMock( \wpdb::class );
		$wpdb
			->method( 'get_row' )
			->willReturn(
				(object) [
					'id'             => 3,
					'post_id'        => 1,
					'user_id'        => 2,
					'parent_post_id' => null,
					'type'           => 'quiz',
					'status'         => 'in-progress',
					'created_at'     => ( new \DateTimeImmutable( '2022-01-01 00:00:00', wp_timezone() ) )->getTimestamp(),
					'updated_at'     => ( new \DateTimeImmutable( '2022-01-02 00:00:00', wp_timezone() ) )->getTimestamp(),
					'started_at'     => ( new \DateTimeImmutable( '2022-01-03 00:00:00', wp_timezone() ) )->getTimestamp(),
					'completed_at'   => ( new \DateTimeImmutable( '2022-01-04 00:00:00', wp_timezone() ) )->getTimestamp(),
				]
			);
		$repository = new Tables_Based_Quiz_Progress_Repository( $wpdb );

		/* Act. */
		$progress = $repository->get( 1, 2 );

		/* Assert. */
		$expected = [
			'id'           => 3,
			'quiz_id'      => 1,
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
		$wpdb = $this->createMock( \wpdb::class );
		$wpdb
			->method( 'get_var' )
			->willReturn( 0 );
		$repository = new Tables_Based_Quiz_Progress_Repository( $wpdb );

		/* Act. */
		$has = $repository->has( 1, 2 );

		/* Assert. */
		self::assertFalse( $has );
	}

	public function testHas_ProgressFound_ReturnsFalse(): void {
		/* Arrange. */
		$wpdb = $this->createMock( \wpdb::class );
		$wpdb
			->method( 'get_var' )
			->willReturn( 2 );
		$repository = new Tables_Based_Quiz_Progress_Repository( $wpdb );

		/* Act. */
		$has = $repository->has( 1, 2 );

		/* Assert. */
		self::assertTrue( $has );
	}

	public function testSave_ProgressGiven_CallsWpdbUpdate(): void {
		/* Arrange. */
		$wpdb       = $this->createMock( \wpdb::class );
		$progress   = new Quiz_Progress(
			1,
			2,
			3,
			'complete',
			new \DateTimeImmutable( '@1', wp_timezone() ),
			new \DateTimeImmutable( '@2', wp_timezone() ),
			new \DateTimeImmutable( '@3', wp_timezone() ),
			new \DateTimeImmutable( '@4', wp_timezone() )
		);
		$repository = new Tables_Based_Quiz_Progress_Repository( $wpdb );

		/* Expect & Act. */
		$wpdb
			->expects( self::once() )
			->method( 'update' )
			->with(
				'sensei_lms_progress',
				$this->callback(
					function ( $data ) {
						if ( ! isset( $data['status'] ) || 'complete' !== $data['status'] ) {
							return false;
						}
						if ( ! isset( $data['started_at'] ) || 1 !== $data['started_at'] ) {
							return false;
						}
						if ( ! isset( $data['completed_at'] ) || 2 !== $data['completed_at'] ) {
							return false;
						}
						return true;
					}
				),
				[
					'id' => 1,
				],
				[
					'%s',
					'%d',
					'%d',
					'%d',
				],
				[
					'%d',
				]
			);
		$repository->save( $progress );
	}

	private function export_progress( Quiz_Progress $progress ): array {
		return [
			'id'      => $progress->get_id(),
			'quiz_id' => $progress->get_quiz_id(),
			'user_id' => $progress->get_user_id(),
			'status'  => $progress->get_status(),
		];
	}

	private function export_progress_with_dates( ?Quiz_Progress $progress ): array {
		return [
			'id'           => $progress->get_id(),
			'quiz_id'      => $progress->get_quiz_id(),
			'user_id'      => $progress->get_user_id(),
			'status'       => $progress->get_status(),
			'created_at'   => $progress->get_created_at()->format( 'Y-m-d H:i:s' ),
			'updated_at'   => $progress->get_updated_at()->format( 'Y-m-d H:i:s' ),
			'started_at'   => $progress->get_started_at()->format( 'Y-m-d H:i:s' ),
			'completed_at' => $progress->get_completed_at()->format( 'Y-m-d H:i:s' ),
		];
	}
}

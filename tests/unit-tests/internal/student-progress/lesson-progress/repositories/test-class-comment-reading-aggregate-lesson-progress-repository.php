<?php

namespace SenseiTest\Internal\Student_Progress\Lesson_Progress\Repositories;

use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Comments_Based_Lesson_Progress;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress_Interface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Tables_Based_Lesson_Progress;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comment_Reading_Aggregate_Lesson_Progress_Repository;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comments_Based_Lesson_Progress_Repository;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Tables_Based_Lesson_Progress_Repository;

/**
 * Tests for Comment_Reading_Aggregate_Lesson_Progress_Repository.
 *
 * @covers \Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comment_Reading_Aggregate_Lesson_Progress_Repository
 */
class Comment_Reading_Aggregate_Lesson_Progress_Repository_Test extends \WP_UnitTestCase {
	public function testCreate_UseTablesOn_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );
		$repository     = new Comment_Reading_Aggregate_Lesson_Progress_Repository( $comments_based, $tables_based, true );

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'create' )
			->with( 1, 2 );
		$repository->create( 1, 2 );
	}

	public function testCreate_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );
		$repository     = new Comment_Reading_Aggregate_Lesson_Progress_Repository( $comments_based, $tables_based, true );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'create' )
			->with( 1, 2 );
		$repository->create( 1, 2 );
	}

	public function testGet_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );
		$repository     = new Comment_Reading_Aggregate_Lesson_Progress_Repository( $comments_based, $tables_based, false );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'get' )
			->with( 1, 2 );
		$repository->get( 1, 2 );
	}

	public function testHas_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );
		$repository     = new Comment_Reading_Aggregate_Lesson_Progress_Repository( $comments_based, $tables_based, false );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'has' )
			->with( 1, 2 );
		$repository->has( 1, 2 );
	}

	public function testSave_WhenUsedTables_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$progress       = $this->create_comments_based_lesson_progress();
		$found_progress = $this->create_tables_based_lesson_progress();
		$comments_based = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );

		$tables_based = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );
		$tables_based
			->method( 'get' )
			->willReturn( $found_progress );

		$repository = new Comment_Reading_Aggregate_Lesson_Progress_Repository( $comments_based, $tables_based );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'save' )
			->with( $progress );
		$repository->save( $progress );
	}

	public function testSave_TablesBasedProgressFound_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$progress       = $this->create_comments_based_lesson_progress();
		$found_progress = $this->create_tables_based_lesson_progress();

		$comments_based = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );
		$tables_based
			->method( 'get' )
			->with( 2, 3 )
			->willReturn( $found_progress );

		$repository = new Comment_Reading_Aggregate_Lesson_Progress_Repository( $comments_based, $tables_based );

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->callback(
					function ( Lesson_Progress_Interface $progress_to_save ) use ( $progress, $found_progress ) {
						self::assertNotSame( $progress, $progress_to_save, 'We should create a new progress based on a found one: not using passed for saving.' );
						self::assertNotSame( $found_progress, $progress_to_save, 'We should create a new progress based on a found one: not the found one itself.' );
						return true;
					}
				)
			);
		$repository->save( $progress );
	}

	public function testSave_TablesBasedProgressFound_ConvertsTimeToUtc(): void {
		/* Arrange. */
		$progress = $this->create_comments_based_lesson_progress( new \DateTimeImmutable( '2020-01-01 03:00:00', new \DateTimeZone( 'GMT+03:00' ) ) );

		$found_progress = $this->create_tables_based_lesson_progress();

		$comments_based = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );
		$tables_based
			->method( 'get' )
			->with( 2, 3 )
			->willReturn( $found_progress );

		$repository = new Comment_Reading_Aggregate_Lesson_Progress_Repository( $comments_based, $tables_based );

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->callback(
					function ( Lesson_Progress_Interface $progress_to_save ) {
						return '2020-01-01 00:00:00' === $progress_to_save->get_started_at()->format( 'Y-m-d H:i:s' );
					}
				)
			);
		$repository->save( $progress );
	}
	public function testSave_TablesBasedProgressNotFound_CreatesTablesBasedProgress(): void {
		/* Arrange. */
		$progress         = $this->create_comments_based_lesson_progress();
		$created_progress = $this->create_tables_based_lesson_progress();

		$comments_based = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );
		$tables_based
			->method( 'get' )
			->with( 2, 3 )
			->willReturn( null );

		$repository = new Comment_Reading_Aggregate_Lesson_Progress_Repository( $comments_based, $tables_based );

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'create' )
			->with( 2, 3 )
			->willReturn( $created_progress );
		$repository->save( $progress );
	}

	public function testDelete_Always_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$progress = $this->create_comments_based_lesson_progress();

		$comments_based = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Comment_Reading_Aggregate_Lesson_Progress_Repository( $comments_based, $tables_based );

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'delete' )
			->with( $progress );
		$repository->delete( $progress );
	}

	public function testDelete_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$progress = $this->create_comments_based_lesson_progress();

		$comments_based = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Comment_Reading_Aggregate_Lesson_Progress_Repository( $comments_based, $tables_based );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'delete' );
		$repository->delete( $progress );
	}

	public function testDeleteForLesson_Always_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$lesson_id = 2;

		$comments_based = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Comment_Reading_Aggregate_Lesson_Progress_Repository( $comments_based, $tables_based );

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'delete_for_lesson' )
			->with( $lesson_id );
		$repository->delete_for_lesson( $lesson_id );
	}

	public function testDeleteForLesson_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$lesson_id = 2;

		$comments_based = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Comment_Reading_Aggregate_Lesson_Progress_Repository( $comments_based, $tables_based );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'delete_for_lesson' );
		$repository->delete_for_lesson( $lesson_id );
	}

	public function testDeleteForUser_Always_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$user_id = 2;

		$comments_based = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Comment_Reading_Aggregate_Lesson_Progress_Repository( $comments_based, $tables_based );

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'delete_for_user' )
			->with( $user_id );
		$repository->delete_for_user( $user_id );
	}

	public function testDeleteForUser_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$user_id = 2;

		$comments_based = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Comment_Reading_Aggregate_Lesson_Progress_Repository( $comments_based, $tables_based );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'delete_for_user' );
		$repository->delete_for_user( $user_id );
	}

	public function providerDeleteForUser_Always_CallsCommentsBasedRepository(): array {
		return [
			'uses tables'         => [ true ],
			'does not use tables' => [ false ],
		];
	}

	public function testCount_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );
		$repository     = new Comment_Reading_Aggregate_Lesson_Progress_Repository( $comments_based, $tables_based, false );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'count' )
			->with( 1, 2 );
		$repository->count( 1, 2 );
	}

	public function testFind_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Comment_Reading_Aggregate_Lesson_Progress_Repository( $comments_based, $tables_based );

		$args = array(
			'a' => 1,
			'b' => 2,
		);

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'find' )
			->with( $args );
		$repository->find( $args );
	}

	public function testFind_Never_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Comment_Reading_Aggregate_Lesson_Progress_Repository( $comments_based, $tables_based );

		$args = array(
			'a' => 1,
			'b' => 2,
		);

		/* Expect & Act. */
		$tables_based
			->expects( $this->never() )
			->method( 'find' );
		$repository->find( $args );
	}

	/**
	 * Create a comments-baesd lesson progress.
	 *
	 * @param \DateTimeInterface|null $started_at The started at date.
	 * @return Comments_Based_Lesson_Progress
	 */
	public function create_comments_based_lesson_progress( $started_at = null ): Comments_Based_Lesson_Progress {
		return new Comments_Based_Lesson_Progress(
			1,
			2,
			3,
			'a',
			$started_at ?? new \DateTimeImmutable(),
			new \DateTimeImmutable(),
			new \DateTimeImmutable(),
			new \DateTimeImmutable()
		);
	}

	/**
	 * Create a tables-baesd lesson progress.
	 *
	 * @param \DateTimeInterface|null $started_at The started at date.
	 * @return Lesson_Progress
	 */
	public function create_tables_based_lesson_progress( $started_at = null ): Tables_Based_Lesson_Progress {
		return new Tables_Based_Lesson_Progress(
			4,
			2,
			3,
			'b',
			$started_at ?? new \DateTimeImmutable(),
			new \DateTimeImmutable(),
			new \DateTimeImmutable(),
			new \DateTimeImmutable()
		);
	}
}

<?php

namespace SenseiTest\Student_Progress\Quiz_Progress\Repositories;

use Sensei\Student_Progress\Quiz_Progress\Models\Quiz_Progress;
use Sensei\Student_Progress\Quiz_Progress\Repositories\Aggregate_Quiz_Progress_Repository;
use Sensei\Student_Progress\Quiz_Progress\Repositories\Comments_Based_Quiz_Progress_Repository;
use Sensei\Student_Progress\Quiz_Progress\Repositories\Tables_Based_Quiz_Progress_Repository;
use WP_UnitTestCase;

class Aggregate_Quiz_Progress_Repository_Test extends WP_UnitTestCase {
	public function testCreate_UseTablesOn_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Quiz_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Quiz_Progress_Repository::class );
		$repository     = new Aggregate_Quiz_Progress_Repository( $comments_based, $tables_based, true );

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'create' )
			->with( 1, 2 );
		$repository->create( 1, 2 );
	}

	public function testCreate_UseTablesOn_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Quiz_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Quiz_Progress_Repository::class );
		$repository     = new Aggregate_Quiz_Progress_Repository( $comments_based, $tables_based, true );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'create' )
			->with( 1, 2 );
		$repository->create( 1, 2 );
	}

	public function testCreate_UseTablesOff_DoesntCallTablesBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Quiz_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Quiz_Progress_Repository::class );
		$repository     = new Aggregate_Quiz_Progress_Repository( $comments_based, $tables_based, false );

		/* Expect & Act. */
		$tables_based
			->expects( $this->never() )
			->method( 'create' );
		$repository->create( 1, 2 );
	}

	public function testCreate_UseTablesOff_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Quiz_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Quiz_Progress_Repository::class );
		$repository     = new Aggregate_Quiz_Progress_Repository( $comments_based, $tables_based, false );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'create' )
			->with( 1, 2 );
		$repository->create( 1, 2 );
	}

	public function testGet_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Quiz_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Quiz_Progress_Repository::class );
		$repository     = new Aggregate_Quiz_Progress_Repository( $comments_based, $tables_based, false );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'get' )
			->with( 1, 2 );
		$repository->get( 1, 2 );
	}

	public function testHas_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Quiz_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Quiz_Progress_Repository::class );
		$repository     = new Aggregate_Quiz_Progress_Repository( $comments_based, $tables_based, false );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'has' )
			->with( 1, 2 );
		$repository->has( 1, 2 );
	}

	/**
	 * Test that the repository will always use comments based repository while saving.
	 *
	 * @param bool $use_tables
	 * @dataProvider providerSave_Always_CallsCommentsBasedRepository
	 */
	public function testSave_Always_CallsCommentsBasedRepository( bool $use_tables ): void {
		/* Arrange. */
		$progress       = new Quiz_Progress(
			1,
			2,
			3,
			'a',
			new \DateTimeImmutable(),
			new \DateTimeImmutable(),
			new \DateTimeImmutable(),
			new \DateTimeImmutable()
		);
		$comments_based = $this->createMock( Comments_Based_Quiz_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Quiz_Progress_Repository::class );
		$repository     = new Aggregate_Quiz_Progress_Repository( $comments_based, $tables_based, $use_tables );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'save' )
			->with( $progress );
		$repository->save( $progress );
	}

	public function providerSave_Always_CallsCommentsBasedRepository(): array {
		return [
			'uses tables'         => [ true ],
			'does not use tables' => [ false ],
		];
	}

	public function testSave_UseTablesOnAndProgressFound_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$progress       = new Quiz_Progress(
			1,
			2,
			3,
			'a',
			new \DateTimeImmutable(),
			new \DateTimeImmutable(),
			new \DateTimeImmutable(),
			new \DateTimeImmutable()
		);
		$found_progress = new Quiz_Progress(
			2,
			3,
			4,
			'a',
			new \DateTimeImmutable(),
			new \DateTimeImmutable(),
			new \DateTimeImmutable(),
			new \DateTimeImmutable()
		);

		$comments_based = $this->createMock( Comments_Based_Quiz_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Quiz_Progress_Repository::class );
		$tables_based
			->method( 'get' )
			->with( 2, 3 )
			->willReturn( $found_progress );

		$repository = new Aggregate_Quiz_Progress_Repository( $comments_based, $tables_based, true );

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->callback(
					function ( Quiz_Progress $progress_to_save ) use ( $progress, $found_progress ) {
						self::assertNotSame( $progress, $progress_to_save, 'We should create a new progress based on a found one: not using passed for saving.' );
						self::assertNotSame( $found_progress, $progress_to_save, 'We should create a new progress based on a found one: not the found one itself.' );
						return true;
					}
				)
			);
		$repository->save( $progress );
	}

	public function testSave_UseTablesOnAndProgressNotFound_DoesntCallTablesBasedRepository(): void {
		/* Arrange. */
		$progress = new Quiz_Progress(
			1,
			2,
			3,
			'a',
			new \DateTimeImmutable(),
			new \DateTimeImmutable(),
			new \DateTimeImmutable(),
			new \DateTimeImmutable()
		);

		$comments_based = $this->createMock( Comments_Based_Quiz_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Quiz_Progress_Repository::class );
		$tables_based
			->method( 'get' )
			->with( 2, 3 )
			->willReturn( null );

		$repository = new Aggregate_Quiz_Progress_Repository( $comments_based, $tables_based, true );

		/* Expect & Act. */
		$tables_based
			->expects( $this->never() )
			->method( 'save' );
		$repository->save( $progress );
	}
}

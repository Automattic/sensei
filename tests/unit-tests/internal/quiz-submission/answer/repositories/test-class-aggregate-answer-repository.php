<?php

namespace SenseiTest\Internal\Quiz_Submission\Submission\Repositories;

use DateTimeImmutable;
use DateTimeZone;
use Sensei\Internal\Quiz_Submission\Answer\Models\Answer;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Aggregate_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Comments_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Tables_Based_Answer_Repository;

/**
 * Class Aggregate_Answer_Repository_Test
 *
 * @covers \Sensei\Internal\Quiz_Submission\Answer\Repositories\Aggregate_Answer_Repository
 */
class Aggregate_Answer_Repository_Test extends \WP_UnitTestCase {
	public function testCreate_UseTablesOn_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Answer_Repository::class );
		$repository     = new Aggregate_Answer_Repository( $comments_based, $tables_based, true );

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'create' )
			->with( 1, 2, 'value' );

		$repository->create( 1, 2, 'value' );
	}

	public function testCreate_UseTablesOn_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Answer_Repository::class );
		$repository     = new Aggregate_Answer_Repository( $comments_based, $tables_based, true );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'create' )
			->with( 1, 2, 'value' );

		$repository->create( 1, 2, 'value' );
	}

	public function testCreate_UseTablesOff_DoesntCallTablesBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Answer_Repository::class );
		$repository     = new Aggregate_Answer_Repository( $comments_based, $tables_based, false );

		/* Expect & Act. */
		$tables_based
			->expects( $this->never() )
			->method( 'create' );

		$repository->create( 1, 2, 'value' );
	}

	public function testCreate_UseTablesOff_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Answer_Repository::class );
		$repository     = new Aggregate_Answer_Repository( $comments_based, $tables_based, false );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'create' )
			->with( 1, 2, 'value' );

		$repository->create( 1, 2, 'value' );
	}

	public function testGetAll_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Answer_Repository::class );
		$repository     = new Aggregate_Answer_Repository( $comments_based, $tables_based, false );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'get_all' )
			->with( 1 );

		$repository->get_all( 1 );
	}

	public function testDeleteAll_UseTablesOff_DoesntCallTablesBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Answer_Repository::class );
		$repository     = new Aggregate_Answer_Repository( $comments_based, $tables_based, false );

		/* Expect & Act. */
		$tables_based
			->expects( $this->never() )
			->method( 'delete_all' );

		$repository->delete_all( 1 );
	}

	public function testDeleteAll_UseTablesOn_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Answer_Repository::class );
		$repository     = new Aggregate_Answer_Repository( $comments_based, $tables_based, true );

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'delete_all' )
			->with( 1 );

		$repository->delete_all( 1 );
	}

	/**
	 * Test that the repository will always use comments based repository while deleting.
	 *
	 * @param bool $use_tables
	 *
	 * @dataProvider providerDeleteAll_Always_CallsCommentsBasedRepository
	 */
	public function testDeleteAll_Always_CallsCommentsBasedRepository( bool $use_tables ): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Answer_Repository::class );
		$repository     = new Aggregate_Answer_Repository( $comments_based, $tables_based, $use_tables );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'delete_all' );

		$repository->delete_all( 1 );
	}

	public function providerDeleteAll_Always_CallsCommentsBasedRepository(): array {
		return [
			'uses tables'         => [ true ],
			'does not use tables' => [ false ],
		];
	}
}

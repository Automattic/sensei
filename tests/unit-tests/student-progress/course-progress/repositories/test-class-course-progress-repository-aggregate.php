<?php

namespace SenseiTest\Student_Progress\Course_Progress\Repositories;

use Sensei\Student_Progress\Course_Progress\Models\Course_Progress_Interface;
use Sensei\Student_Progress\Course_Progress\Repositories\Comments_Based_Course_Progress_Repository;
use Sensei\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Aggregate;
use Sensei\Student_Progress\Course_Progress\Repositories\Tables_Based_Course_Progress_Repository;

/**
 * Tests for Course_Progress_Repository_Aggregate.
 *
 * @covers \Sensei\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Aggregate
 */
class Course_Progress_Repository_Aggregate_Test extends \WP_UnitTestCase {

	public function testCreate_WhenTablesEnabled_CallsTablesCreateMethod(): void {
		/* Arrange. */
		$tables    = $this->createMock( Tables_Based_Course_Progress_Repository::class );
		$comments  = $this->createMock( Comments_Based_Course_Progress_Repository::class );
		$aggregate = new Course_Progress_Repository_Aggregate( $tables, $comments, true );

		/* Expect & Act. */
		$tables
			->expects( $this->once() )
			->method( 'create' )
			->with( 1, 1 );
		$aggregate->create( 1, 1 );
	}

	public function testCreate_WhenTablesNotEnabled_DoesntCallTablesCreateMethod(): void {
		/* Arrange. */
		$tables    = $this->createMock( Tables_Based_Course_Progress_Repository::class );
		$comments  = $this->createMock( Comments_Based_Course_Progress_Repository::class );
		$aggregate = new Course_Progress_Repository_Aggregate( $tables, $comments, false );

		/* Expect & Act. */
		$tables
			->expects( $this->never() )
			->method( 'create' )
			->with( 1, 1 );
		$aggregate->create( 1, 1 );
	}

	public function testGet_WhenTablesEnabled_CallsTablesGetMethod(): void {
		/* Arrange. */
		$tables    = $this->createMock( Tables_Based_Course_Progress_Repository::class );
		$comments  = $this->createMock( Comments_Based_Course_Progress_Repository::class );
		$aggregate = new Course_Progress_Repository_Aggregate( $tables, $comments, true );

		/* Expect & Act. */
		$tables
			->expects( $this->once() )
			->method( 'get' )
			->with( 1, 1 );
		$aggregate->get( 1, 1 );
	}

	public function testGet_WhenTablesEnabled_ReturnsValueFromGetMethod(): void {
		/* Arrange. */
		$progress = $this->createMock( Course_Progress_Interface::class );
		$tables   = $this->createMock( Tables_Based_Course_Progress_Repository::class );
		$tables
			->expects( $this->once() )
			->method( 'get' )
			->with( 1, 1 )
			->willReturn( $progress );

		$comments  = $this->createMock( Comments_Based_Course_Progress_Repository::class );
		$aggregate = new Course_Progress_Repository_Aggregate( $tables, $comments, true );

		/* Act. */
		$actual = $aggregate->get( 1, 1 );

		/* Assert. */
		self::assertSame( $progress, $actual );
	}

	public function testGet_WhenTablesNotEnabled_DoesntCallTablesGetMethod(): void {
		/* Arrange. */
		$tables    = $this->createMock( Tables_Based_Course_Progress_Repository::class );
		$comments  = $this->createMock( Comments_Based_Course_Progress_Repository::class );
		$aggregate = new Course_Progress_Repository_Aggregate( $tables, $comments, false );

		/* Expect & Act. */
		$tables
			->expects( $this->never() )
			->method( 'get' )
			->with( 1, 1 );
		$aggregate->get( 1, 1 );
	}
}

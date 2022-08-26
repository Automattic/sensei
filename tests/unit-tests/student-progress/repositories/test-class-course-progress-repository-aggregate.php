<?php

namespace SenseiTest\Student_Progress\Repositories;

use Sensei\Student_Progress\Models\Course_Progress_Interface;
use Sensei\Student_Progress\Repositories\Course_Progress_Comments_Repository;
use Sensei\Student_Progress\Repositories\Course_Progress_Repository_Aggregate;
use Sensei\Student_Progress\Repositories\Course_Progress_Tables_Repository;
use WP_UnitTestCase;

class Course_Progress_Repository_Aggregate_Test extends WP_UnitTestCase {

	public function testCreate_WhenTablesEnabled_CallsTablesCreateMethod(): void {
		/* Arrange. */
		$tables    = $this->createMock( Course_Progress_Tables_Repository::class );
		$comments  = $this->createMock( Course_Progress_Comments_Repository::class );
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
		$tables    = $this->createMock( Course_Progress_Tables_Repository::class );
		$comments  = $this->createMock( Course_Progress_Comments_Repository::class );
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
		$tables    = $this->createMock( Course_Progress_Tables_Repository::class );
		$comments  = $this->createMock( Course_Progress_Comments_Repository::class );
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
		$tables   = $this->createMock( Course_Progress_Tables_Repository::class );
		$tables
			->expects( $this->once() )
			->method( 'get' )
			->with( 1, 1 )
			->willReturn( $progress );

		$comments  = $this->createMock( Course_Progress_Comments_Repository::class );
		$aggregate = new Course_Progress_Repository_Aggregate( $tables, $comments, true );

		/* Act. */
		$actual = $aggregate->get( 1, 1 );

		/* Assert. */
		self::assertSame( $progress, $actual );
	}

	public function testGet_WhenTablesNotEnabled_DoesntCallTablesGetMethod(): void {
		/* Arrange. */
		$tables    = $this->createMock( Course_Progress_Tables_Repository::class );
		$comments  = $this->createMock( Course_Progress_Comments_Repository::class );
		$aggregate = new Course_Progress_Repository_Aggregate( $tables, $comments, false );

		/* Expect & Act. */
		$tables
			->expects( $this->never() )
			->method( 'get' )
			->with( 1, 1 );
		$aggregate->get( 1, 1 );
	}


}

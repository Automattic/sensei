<?php

namespace SenseiTest\Internal\Student_Progress\Lesson_Progress\Repositories;

use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Comments_Based_Lesson_Progress;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress_Interface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Tables_Based_Lesson_Progress;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Table_Reading_Aggregate_Lesson_Progress_Repository;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Tables_Based_Lesson_Progress_Repository;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comments_Based_Lesson_Progress_Repository;

class Table_Reading_Aggregate_Lesson_Progress_Repository_Test extends \WP_UnitTestCase {
	public function testCreate_Always_ReturnsTablesBasedVersion(): void {
		/* Arrange. */
		$comments_based_repository = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );

		$created_progress        = $this->createMock( Tables_Based_Lesson_Progress::class );
		$tables_based_repository = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );
		$tables_based_repository->method( 'create' )->willReturn( $created_progress );

		$repository = new Table_Reading_Aggregate_Lesson_Progress_Repository( $comments_based_repository, $tables_based_repository );

		/* Act. */
		$actual = $repository->create( 1, 2 );

		/* Assert. */
		$this->assertSame( $created_progress, $actual );
	}

	public function testCreate_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based_repository = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based_repository   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Table_Reading_Aggregate_Lesson_Progress_Repository( $comments_based_repository, $tables_based_repository );

		/* Expect & Act. */
		$comments_based_repository
			->expects( $this->once() )
			->method( 'create' )
			->with( 1, 2 );
		$repository->create( 1, 2 );
	}

	public function testGet_Always_ReturnsTablesBasedProgress(): void {
		/* Arrange. */
		$comments_based_repository = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );

		$created_progress        = $this->createMock( Tables_Based_Lesson_Progress::class );
		$tables_based_repository = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );
		$tables_based_repository
			->method( 'get' )
			->with( 1, 2 )
			->willReturn( $created_progress );

		$repository = new Table_Reading_Aggregate_Lesson_Progress_Repository( $comments_based_repository, $tables_based_repository );

		/* Act. */
		$actual = $repository->get( 1, 2 );

		/* Assert. */
		$this->assertSame( $created_progress, $actual );
	}

	public function testGet_Never_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based_repository = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based_repository   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Table_Reading_Aggregate_Lesson_Progress_Repository( $comments_based_repository, $tables_based_repository );

		/* Expect & Act. */
		$comments_based_repository
			->expects( $this->never() )
			->method( 'get' );
		$repository->get( 1, 2 );
	}

	public function testHas_Always_ReturnsMatchingValue(): void {
		/* Arrange. */
		$comments_based_repository = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );

		$tables_based_repository = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );
		$tables_based_repository
			->method( 'has' )
			->with( 1, 2 )
			->willReturn( true );

		$repository = new Table_Reading_Aggregate_Lesson_Progress_Repository( $comments_based_repository, $tables_based_repository );

		/* Act. */
		$actual = $repository->has( 1, 2 );

		/* Assert. */
		$this->assertSame( true, $actual );
	}

	public function testHas_Never_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based_repository = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based_repository   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Table_Reading_Aggregate_Lesson_Progress_Repository( $comments_based_repository, $tables_based_repository );

		/* Expect & Act. */
		$comments_based_repository
			->expects( $this->never() )
			->method( 'has' );
		$repository->has( 1, 2 );
	}

	public function testDelete_Always_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$progress = $this->createMock( Lesson_Progress_Interface::class );
		$progress->method( 'get_lesson_id' )->willReturn( 1 );
		$progress->method( 'get_user_id' )->willReturn( 2 );

		$comments_based_repository = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based_repository   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Table_Reading_Aggregate_Lesson_Progress_Repository( $comments_based_repository, $tables_based_repository );

		/* Expect & Act. */
		$tables_based_repository
			->expects( $this->once() )
			->method( 'delete' )
			->with( $progress );
		$repository->delete( $progress );
	}

	public function testDelete_CommentsBasedProgressNotFound_DoesntDeleteCommentsBasedProgress(): void {
		/* Arrange. */
		$progress = $this->createMock( Lesson_Progress_Interface::class );
		$progress->method( 'get_lesson_id' )->willReturn( 1 );
		$progress->method( 'get_user_id' )->willReturn( 2 );

		$comments_based_repository = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$comments_based_repository
			->method( 'get' )
			->with( 1, 2 )
			->willReturn( null );

		$tables_based_repository = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Table_Reading_Aggregate_Lesson_Progress_Repository( $comments_based_repository, $tables_based_repository );

		/* Expect & Act. */
		$comments_based_repository
			->expects( $this->never() )
			->method( 'delete' );
		$repository->delete( $progress );
	}

	public function testDelete_CommentsBasedProgressFound_DeletesCommentsBasedProgress(): void {
		/* Arrange. */
		$progress = $this->createMock( Lesson_Progress_Interface::class );
		$progress->method( 'get_lesson_id' )->willReturn( 1 );
		$progress->method( 'get_user_id' )->willReturn( 2 );

		$commets_based_progress    = $this->createMock( Comments_Based_Lesson_Progress::class );
		$comments_based_repository = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$comments_based_repository
			->method( 'get' )
			->with( 1, 2 )
			->willReturn( $commets_based_progress );

		$tables_based_repository = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Table_Reading_Aggregate_Lesson_Progress_Repository( $comments_based_repository, $tables_based_repository );

		/* Expect & Act. */
		$comments_based_repository
			->expects( $this->once() )
			->method( 'delete' )
			->with( $commets_based_progress );
		$repository->delete( $progress );
	}

	public function testDeleteForLesson_Always_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$comments_based_repository = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based_repository   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Table_Reading_Aggregate_Lesson_Progress_Repository( $comments_based_repository, $tables_based_repository );

		/* Expect & Act. */
		$tables_based_repository
			->expects( $this->once() )
			->method( 'delete_for_lesson' )
			->with( 1 );
		$repository->delete_for_lesson( 1 );
	}

	public function testDeleteForLesson_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based_repository = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based_repository   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Table_Reading_Aggregate_Lesson_Progress_Repository( $comments_based_repository, $tables_based_repository );

		/* Expect & Act. */
		$comments_based_repository
			->expects( $this->once() )
			->method( 'delete_for_lesson' )
			->with( 1 );
		$repository->delete_for_lesson( 1 );
	}

	public function testDeleteForUser_Always_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$comments_based_repository = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based_repository   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Table_Reading_Aggregate_Lesson_Progress_Repository( $comments_based_repository, $tables_based_repository );

		/* Expect & Act. */
		$tables_based_repository
			->expects( $this->once() )
			->method( 'delete_for_user' )
			->with( 1 );
		$repository->delete_for_user( 1 );
	}

	public function testDeleteForUser_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based_repository = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based_repository   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Table_Reading_Aggregate_Lesson_Progress_Repository( $comments_based_repository, $tables_based_repository );

		/* Expect & Act. */
		$comments_based_repository
			->expects( $this->once() )
			->method( 'delete_for_user' )
			->with( 1 );
		$repository->delete_for_user( 1 );
	}

	public function testSave_Always_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$date_mock               = new \DateTimeImmutable( '2020-01-01' );
		$tables_based_progress   = new Tables_Based_Lesson_Progress( 3, 1, 2, 'in-progress', $date_mock, $date_mock, $date_mock, $date_mock );
		$comments_based_progress = new Comments_Based_Lesson_Progress( 4, 1, 2, 'in-progress', $date_mock, $date_mock, $date_mock, $date_mock );

		$comments_based_repository = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$comments_based_repository
			->method( 'get' )
			->with( 1, 2 )
			->willReturn( $comments_based_progress );

		$tables_based_repository = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Table_Reading_Aggregate_Lesson_Progress_Repository( $comments_based_repository, $tables_based_repository );

		/* Expect & Act. */
		$tables_based_repository
			->expects( $this->once() )
			->method( 'save' )
			->with( $tables_based_progress );
		$repository->save( $tables_based_progress );
	}

	public function testSave_CommentsBasedProgressNotFound_CreatesCommentsBasedProgress(): void {
		/* Arrange. */
		$date_mock               = new \DateTimeImmutable( '2020-01-01' );
		$tables_based_progress   = new Tables_Based_Lesson_Progress( 3, 1, 2, 'in-progress', $date_mock, $date_mock, $date_mock, $date_mock );
		$comments_based_progress = new Comments_Based_Lesson_Progress( 4, 1, 2, 'in-progress', $date_mock, $date_mock, $date_mock, $date_mock );

		$comments_based_repository = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$comments_based_repository
			->method( 'get' )
			->with( 1, 2 )
			->willReturn( null );

		$tables_based_repository = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Table_Reading_Aggregate_Lesson_Progress_Repository( $comments_based_repository, $tables_based_repository );

		/* Expect & Act. */
		$comments_based_repository
			->expects( $this->once() )
			->method( 'create' )
			->with( 1, 2 )
			->willReturn( $comments_based_progress );
		$repository->save( $tables_based_progress );
	}


	public function testSave_CommentsBasedProgressFound_DoesntCreateCommentsBasedProgress(): void {
		/* Arrange. */
		$date_mock               = new \DateTimeImmutable( '2020-01-01' );
		$tables_based_progress   = new Tables_Based_Lesson_Progress( 3, 1, 2, 'in-progress', $date_mock, $date_mock, $date_mock, $date_mock );
		$comments_based_progress = new Comments_Based_Lesson_Progress( 4, 1, 2, 'in-progress', $date_mock, $date_mock, $date_mock, $date_mock );

		$comments_based_repository = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$comments_based_repository
			->method( 'get' )
			->with( 1, 2 )
			->willReturn( $comments_based_progress );

		$tables_based_repository = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Table_Reading_Aggregate_Lesson_Progress_Repository( $comments_based_repository, $tables_based_repository );

		/* Expect & Act. */
		$comments_based_repository
			->expects( $this->never() )
			->method( 'create' )
			->with( 1, 2 )
			->willReturn( $comments_based_progress );
		$repository->save( $tables_based_progress );
	}

	public function testSave_Always_SavesToCommentsBasedRepository(): void {
		/* Arrange. */
		$date_mock               = new \DateTimeImmutable( '2020-01-01' );
		$tables_based_progress   = new Tables_Based_Lesson_Progress( 3, 1, 2, 'complete', $date_mock, $date_mock, $date_mock, $date_mock );
		$comments_based_progress = new Comments_Based_Lesson_Progress( 4, 1, 2, 'in-progress', $date_mock, $date_mock, $date_mock, $date_mock );

		$comments_based_repository = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$comments_based_repository
			->method( 'get' )
			->with( 1, 2 )
			->willReturn( $comments_based_progress );

		$tables_based_repository = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Table_Reading_Aggregate_Lesson_Progress_Repository( $comments_based_repository, $tables_based_repository );

		/* Expect & Act. */
		$comments_based_repository
			->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->callback(
					function ( Lesson_Progress_Interface $progress ) {
						return 'complete' === $progress->get_status();
					}
				)
			);
		$repository->save( $tables_based_progress );
	}

	public function testCount_Always_ReturnsMatchingValue(): void {
		/* Arrange. */
		$comments_based_repository = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );

		$tables_based_repository = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );
		$tables_based_repository->method( 'count' )->with( 1, 2 )->willReturn( 3 );

		$repository = new Table_Reading_Aggregate_Lesson_Progress_Repository( $comments_based_repository, $tables_based_repository );

		/* Act. */
		$actual = $repository->count( 1, 2 );

		/* Assert. */
		$this->assertSame( 3, $actual );
	}

	public function testFind_Always_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Table_Reading_Aggregate_Lesson_Progress_Repository( $comments_based, $tables_based );

		$args = array(
			'a' => 1,
			'b' => 2,
		);

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'find' )
			->with( $args );
		$repository->find( $args );
	}

	public function testFind_Never_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Lesson_Progress_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Lesson_Progress_Repository::class );

		$repository = new Table_Reading_Aggregate_Lesson_Progress_Repository( $comments_based, $tables_based );

		$args = array(
			'a' => 1,
			'b' => 2,
		);

		/* Expect & Act. */
		$comments_based
			->expects( $this->never() )
			->method( 'find' );
		$repository->find( $args );
	}
}

<?php

namespace SenseiTest\Internal\Quiz_Submission\Submission\Repositories;

use DateTimeImmutable;
use DateTimeZone;
use Sensei\Internal\Quiz_Submission\Submission\Models\Comments_Based_Submission;
use Sensei\Internal\Quiz_Submission\Submission\Models\Tables_Based_Submission;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Comment_Reading_Aggregate_Submission_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Comments_Based_Submission_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Tables_Based_Submission_Repository;

/**
 * Class Comment_Reading_Aggregate_Submission_Repository_Test
 *
 * @covers \Sensei\Internal\Quiz_Submission\Submission\Repositories\Comment_Reading_Aggregate_Submission_Repository
 */
class Comment_Reading_Aggregate_Submission_Repository_Test extends \WP_UnitTestCase {
	public function testCreate_Always_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );
		$repository     = new Comment_Reading_Aggregate_Submission_Repository( $comments_based, $tables_based );

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'create' )
			->with( 1, 2, 12.34 );

		$repository->create( 1, 2, 12.34 );
	}

	public function testCreate_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );
		$repository     = new Comment_Reading_Aggregate_Submission_Repository( $comments_based, $tables_based );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'create' )
			->with( 1, 2, 12.34 );

		$repository->create( 1, 2, 12.34 );
	}

	public function testGetOrCreate_Always_CallsGetOrCreateOnCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );
		$repository     = new Comment_Reading_Aggregate_Submission_Repository( $comments_based, $tables_based );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'get_or_create' )
			->with( 1, 2, 12.34 )
			->willReturn( $this->createMock( Comments_Based_Submission::class ) );
		$repository->get_or_create( 1, 2, 12.34 );
	}

	public function testGetOrCreate_Never_CallsGetOrCreateOnTablesBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );
		$repository     = new Comment_Reading_Aggregate_Submission_Repository( $comments_based, $tables_based );

		/* Expect & Act. */
		$tables_based
			->expects( $this->never() )
			->method( 'get_or_create' );
		$repository->get_or_create( 1, 2, 12.34 );
	}

	public function testGetQuestionIds_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );
		$repository     = new Comment_Reading_Aggregate_Submission_Repository( $comments_based, $tables_based, false );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'get_question_ids' )
			->with( 1 );

		$repository->get_question_ids( 1 );
	}

	public function testSave_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$submission     = $this->create_submission();
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );
		$repository     = new Comment_Reading_Aggregate_Submission_Repository( $comments_based, $tables_based );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'save' )
			->with( $submission );

		$repository->save( $submission );
	}

	public function testSave_TablesBasedSubmissionFound_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$submission       = $this->create_submission();
		$found_submission = $this->create_submission();
		$comments_based   = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based     = $this->createMock( Tables_Based_Submission_Repository::class );
		$tables_based
			->method( 'get' )
			->with( 2, 3 )
			->willReturn( $found_submission );

		$repository = new Comment_Reading_Aggregate_Submission_Repository( $comments_based, $tables_based );

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->callback(
					function ( Tables_Based_Submission $submission_to_save ) use ( $submission, $found_submission ) {
						self::assertNotSame( $submission, $submission_to_save, 'We should create a new submission based on a found one: not using passed for saving.' );
						self::assertNotSame( $found_submission, $submission_to_save, 'We should create a new submission based on a found one: not the found one itself.' );
						return true;
					}
				)
			);

		$repository->save( $submission );
	}

	public function testSave_TablesBasedSubmissionNotFound_CreatesTablesBasedSubmission(): void {
		/* Arrange. */
		$submission     = $this->create_submission();
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );

		$repository = new Comment_Reading_Aggregate_Submission_Repository( $comments_based, $tables_based );

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'get_or_create' )
			->with( 2, 3, 12.34 )
			->willReturn( $this->create_submission() );

		$repository->save( $submission );
	}

	public function testSave_WhenSubmissionWithNonUTCDatesGiven_ConvertsDatesToUTC() {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );
		$submission     = new Comments_Based_Submission(
			1,
			2,
			3,
			12.34,
			new DateTimeImmutable( 'now', new DateTimeZone( 'US/Central' ) ),
			new DateTimeImmutable( 'now', new DateTimeZone( 'US/Central' ) )
		);

		$tables_based
			->method( 'get' )
			->with( 2, 3 )
			->willReturn( $submission );

		$repository = new Comment_Reading_Aggregate_Submission_Repository( $comments_based, $tables_based );

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->callback(
					function ( Tables_Based_Submission $submission_to_save ) {
						$this->assertSame( '+00:00', $submission_to_save->get_created_at()->getTimezone()->getName() );
						$this->assertSame( '+00:00', $submission_to_save->get_updated_at()->getTimezone()->getName() );

						return true;
					}
				)
			);

		$repository->save( $submission );
	}

	public function testDelete_Always_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$submission     = $this->create_submission();
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );
		$repository     = new Comment_Reading_Aggregate_Submission_Repository( $comments_based, $tables_based );

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'delete' )
			->with( $submission );

		$repository->delete( $submission );
	}

	public function testDelete_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$submission     = $this->create_submission();
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );
		$repository     = new Comment_Reading_Aggregate_Submission_Repository( $comments_based, $tables_based );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'delete' );

		$repository->delete( $submission );
	}

	/**
	 * Creates a submission object.
	 *
	 * @return Comments_Based_Submission
	 */
	public function create_submission(): Comments_Based_Submission {
		return new Comments_Based_Submission(
			1,
			2,
			3,
			12.34,
			new \DateTimeImmutable(),
			new \DateTimeImmutable()
		);
	}
}

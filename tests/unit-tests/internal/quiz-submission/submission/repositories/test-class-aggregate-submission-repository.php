<?php

namespace SenseiTest\Internal\Quiz_Submission\Submission\Repositories;

use DateTimeImmutable;
use DateTimeZone;
use Sensei\Internal\Quiz_Submission\Submission\Models\Submission;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Aggregate_Submission_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Comments_Based_Submission_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Tables_Based_Submission_Repository;

/**
 * Class Aggregate_Submission_Repository_Test
 *
 * @covers \Sensei\Internal\Quiz_Submission\Submission\Repositories\Aggregate_Submission_Repository
 */
class Aggregate_Submission_Repository_Test extends \WP_UnitTestCase {
	public function testCreate_UseTablesOn_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );
		$repository     = new Aggregate_Submission_Repository( $comments_based, $tables_based, true );

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'create' )
			->with( 1, 2, 12.34 );

		$repository->create( 1, 2, 12.34 );
	}

	public function testCreate_UseTablesOn_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );
		$repository     = new Aggregate_Submission_Repository( $comments_based, $tables_based, true );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'create' )
			->with( 1, 2, 12.34 );

		$repository->create( 1, 2, 12.34 );
	}

	public function testCreate_UseTablesOff_DoesntCallTablesBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );
		$repository     = new Aggregate_Submission_Repository( $comments_based, $tables_based, false );

		/* Expect & Act. */
		$tables_based
			->expects( $this->never() )
			->method( 'create' );

		$repository->create( 1, 2, 12.34 );
	}

	public function testCreate_UseTablesOff_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );
		$repository     = new Aggregate_Submission_Repository( $comments_based, $tables_based, false );

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
		$repository     = new Aggregate_Submission_Repository( $comments_based, $tables_based, true );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'get_or_create' )
			->with( 1, 2, 12.34 )
			->willReturn( $this->createMock( Submission::class ) );
		$repository->get_or_create( 1, 2, 12.34 );
	}

	public function testGetOrCreate_Never_CallsGetOrCreateOnTablesBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );
		$repository     = new Aggregate_Submission_Repository( $comments_based, $tables_based, true );

		/* Expect & Act. */
		$tables_based
			->expects( $this->never() )
			->method( 'get_or_create' );
		$repository->get_or_create( 1, 2, 12.34 );
	}

	public function testGet_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );
		$repository     = new Aggregate_Submission_Repository( $comments_based, $tables_based, false );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'get' )
			->with( 1, 2 );

		$repository->get( 1, 2 );
	}

	public function testGetQuestionIds_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );
		$repository     = new Aggregate_Submission_Repository( $comments_based, $tables_based, false );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'get_question_ids' )
			->with( 1 );

		$repository->get_question_ids( 1 );
	}

	/**
	 * Test that the repository will always use comments based repository while saving.
	 *
	 * @param bool $use_tables
	 * @dataProvider providerSave_Always_CallsCommentsBasedRepository
	 */
	public function testSave_Always_CallsCommentsBasedRepository( bool $use_tables ): void {
		/* Arrange. */
		$submission     = $this->create_submission();
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );
		$repository     = new Aggregate_Submission_Repository( $comments_based, $tables_based, $use_tables );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'save' )
			->with( $submission );

		$repository->save( $submission );
	}

	public function providerSave_Always_CallsCommentsBasedRepository(): array {
		return [
			'uses tables'         => [ true ],
			'does not use tables' => [ false ],
		];
	}

	public function testSave_UseTablesOnAndSubmissionFound_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$submission       = $this->create_submission();
		$found_submission = $this->create_submission();
		$comments_based   = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based     = $this->createMock( Tables_Based_Submission_Repository::class );
		$tables_based
			->method( 'get' )
			->with( 2, 3 )
			->willReturn( $found_submission );

		$repository = new Aggregate_Submission_Repository( $comments_based, $tables_based, true );

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->callback(
					function ( Submission $submission_to_save ) use ( $submission, $found_submission ) {
						self::assertNotSame( $submission, $submission_to_save, 'We should create a new submission based on a found one: not using passed for saving.' );
						self::assertNotSame( $found_submission, $submission_to_save, 'We should create a new submission based on a found one: not the found one itself.' );
						return true;
					}
				)
			);

		$repository->save( $submission );
	}

	public function testSave_UseTablesOnAndSubmissionNotFound_CreatesTablesBasedSubmission(): void {
		/* Arrange. */
		$submission     = $this->create_submission();
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );

		$repository = new Aggregate_Submission_Repository( $comments_based, $tables_based, true );

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
		$submission     = new Submission(
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

		$repository = new Aggregate_Submission_Repository( $comments_based, $tables_based, true );

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->callback(
					function ( Submission $submission_to_save ) {
						$this->assertSame( '+00:00', $submission_to_save->get_created_at()->getTimezone()->getName() );
						$this->assertSame( '+00:00', $submission_to_save->get_updated_at()->getTimezone()->getName() );

						return true;
					}
				)
			);

		$repository->save( $submission );
	}

	public function testDelete_UseTablesOff_DoesntCallTablesBasedRepository(): void {
		/* Arrange. */
		$submission     = $this->create_submission();
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );
		$repository     = new Aggregate_Submission_Repository( $comments_based, $tables_based, false );

		/* Expect & Act. */
		$tables_based
			->expects( $this->never() )
			->method( 'delete' );

		$repository->delete( $submission );
	}

	public function testDelete_UseTablesOn_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$submission     = $this->create_submission();
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );
		$repository     = new Aggregate_Submission_Repository( $comments_based, $tables_based, true );

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'delete' )
			->with( $submission );

		$repository->delete( $submission );
	}

	/**
	 * Test that the repository will always use comments based repository while deleting.
	 *
	 * @param bool $use_tables
	 *
	 * @dataProvider providerDelete_Always_CallsCommentsBasedRepository
	 */
	public function testDelete_Always_CallsCommentsBasedRepository( $use_tables ): void {
		/* Arrange. */
		$submission     = $this->create_submission();
		$comments_based = $this->createMock( Comments_Based_Submission_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Submission_Repository::class );
		$repository     = new Aggregate_Submission_Repository( $comments_based, $tables_based, $use_tables );

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'delete' );

		$repository->delete( $submission );
	}

	public function providerDelete_Always_CallsCommentsBasedRepository(): array {
		return [
			'uses tables'         => [ true ],
			'does not use tables' => [ false ],
		];
	}

	/**
	 * Creates a submission object.
	 *
	 * @return Submission
	 */
	public function create_submission(): Submission {
		return new Submission(
			1,
			2,
			3,
			12.34,
			new \DateTimeImmutable(),
			new \DateTimeImmutable()
		);
	}
}

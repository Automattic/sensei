<?php

namespace SenseiTest\Internal\Quiz_Submission\Grade\Repositories;

use DateTime;
use DateTimeImmutable;
use Sensei\Internal\Quiz_Submission\Answer\Models\Comments_Based_Answer;
use Sensei\Internal\Quiz_Submission\Answer\Models\Tables_Based_Answer;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Comments_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Tables_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Grade\Models\Comments_Based_Grade;
use Sensei\Internal\Quiz_Submission\Grade\Models\Tables_Based_Grade;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Table_Reading_Aggregate_Grade_Repository;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Comments_Based_Grade_Repository;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Tables_Based_Grade_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Models\Comments_Based_Submission;
use Sensei\Internal\Quiz_Submission\Submission\Models\Tables_Based_Submission;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Comments_Based_Submission_Repository;

/**
 * Tests for Table_Reading_Aggregate_Grade_Repository class.
 *
 * @covers \Sensei\Internal\Quiz_Submission\Grade\Repositories\Table_Reading_Aggregate_Grade_Repository
 */
class Table_Reading_Aggregate_Grade_Repository_Test extends \WP_UnitTestCase {
	public function testCreate_Always_UsesTablesBasedRepository(): void {
		/* Arrange */
		$answer                               = $this->createMock( Tables_Based_Answer::class );
		$submission                           = $this->createMock( Tables_Based_Submission::class );
		$tables_based_repository              = $this->createMock( Tables_Based_Grade_Repository::class );
		$comments_based_repository            = $this->createMock( Comments_Based_Grade_Repository::class );
		$comments_based_submission_repository = $this->createMock( Comments_Based_Submission_Repository::class );
		$comments_based_answer_repository     = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based_answer_repository       = $this->createMock( Tables_Based_Answer_Repository::class );

		$repository = new Table_Reading_Aggregate_Grade_Repository(
			$comments_based_repository,
			$tables_based_repository,
			$comments_based_submission_repository,
			$tables_based_answer_repository,
			$comments_based_answer_repository
		);

		/* Expect & Act */
		$tables_based_repository
			->expects( $this->once() )
			->method( 'create' )
			->with( $this->identicalTo( $submission ), $this->identicalTo( $answer ), 3, 4, 'feedback' );
		$repository->create( $submission, $answer, 3, 4, 'feedback' );
	}

	public function testGetAll_Always_UsesTablesBasedRepository(): void {
		/* Arrange */
		$tables_based_repository              = $this->createMock( Tables_Based_Grade_Repository::class );
		$comments_based_repository            = $this->createMock( Comments_Based_Grade_Repository::class );
		$comments_based_submission_repository = $this->createMock( Comments_Based_Submission_Repository::class );
		$comments_based_answer_repository     = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based_answer_repository       = $this->createMock( Tables_Based_Answer_Repository::class );

		$repository = new Table_Reading_Aggregate_Grade_Repository(
			$comments_based_repository,
			$tables_based_repository,
			$comments_based_submission_repository,
			$tables_based_answer_repository,
			$comments_based_answer_repository
		);

		/* Expect & Act */
		$tables_based_repository
			->expects( $this->once() )
			->method( 'get_all' )
			->with( 1 );
		$repository->get_all( 1 );
	}

	public function testGetAll_Always_DoesntUseCommentsBasedRepository(): void {
		/* Arrange */
		$tables_based_repository              = $this->createMock( Tables_Based_Grade_Repository::class );
		$comments_based_repository            = $this->createMock( Comments_Based_Grade_Repository::class );
		$comments_based_submission_repository = $this->createMock( Comments_Based_Submission_Repository::class );
		$comments_based_answer_repository     = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based_answer_repository       = $this->createMock( Tables_Based_Answer_Repository::class );

		$repository = new Table_Reading_Aggregate_Grade_Repository(
			$comments_based_repository,
			$tables_based_repository,
			$comments_based_submission_repository,
			$tables_based_answer_repository,
			$comments_based_answer_repository
		);

		/* Expect & Act */
		$comments_based_repository
			->expects( $this->never() )
			->method( 'get_all' );
		$repository->get_all( 1 );
	}

	public function testDeleteAll_Always_UsesTablesBasedRepository(): void {
		/* Arrange */
		$submission                           = $this->createMock( Tables_Based_Submission::class );
		$tables_based_repository              = $this->createMock( Tables_Based_Grade_Repository::class );
		$comments_based_repository            = $this->createMock( Comments_Based_Grade_Repository::class );
		$comments_based_submission_repository = $this->createMock( Comments_Based_Submission_Repository::class );
		$comments_based_answer_repository     = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based_answer_repository       = $this->createMock( Tables_Based_Answer_Repository::class );

		$repository = new Table_Reading_Aggregate_Grade_Repository(
			$comments_based_repository,
			$tables_based_repository,
			$comments_based_submission_repository,
			$tables_based_answer_repository,
			$comments_based_answer_repository
		);

		/* Expect & Act */
		$tables_based_repository
			->expects( $this->once() )
			->method( 'delete_all' )
			->with( $this->identicalTo( $submission ) );
		$repository->delete_all( $submission );
	}

	public function testDeleteAll_Always_UsesCommentsBasedRepository(): void {
		/* Arrange */
		$submission = $this->createMock( Tables_Based_Submission::class );
		$submission->method( 'get_quiz_id' )->willReturn( 1 );
		$submission->method( 'get_user_id' )->willReturn( 2 );
		$submission->method( 'get_final_grade' )->willReturn( 3.0 );

		$comments_based_submission = $this->createMock( Comments_Based_Submission::class );

		$tables_based_repository   = $this->createMock( Tables_Based_Grade_Repository::class );
		$comments_based_repository = $this->createMock( Comments_Based_Grade_Repository::class );

		$comments_based_submission_repository = $this->createMock( Comments_Based_Submission_Repository::class );
		$comments_based_submission_repository
			->method( 'get_or_create' )
			->with( 1, 2 )
			->willReturn( $comments_based_submission );

		$comments_based_answer_repository = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based_answer_repository   = $this->createMock( Tables_Based_Answer_Repository::class );

		$repository = new Table_Reading_Aggregate_Grade_Repository(
			$comments_based_repository,
			$tables_based_repository,
			$comments_based_submission_repository,
			$tables_based_answer_repository,
			$comments_based_answer_repository
		);

		/* Expect & Act */
		$comments_based_repository
			->expects( $this->once() )
			->method( 'delete_all' )
			->with( $this->identicalTo( $comments_based_submission ) );
		$repository->delete_all( $submission );
	}

	public function testSaveMany_Always_UsesTablesBasedRepository(): void {
		/* Arrange */
		$submission                = $this->createMock( Tables_Based_Submission::class );
		$tables_based_repository   = $this->createMock( Tables_Based_Grade_Repository::class );
		$comments_based_repository = $this->createMock( Comments_Based_Grade_Repository::class );

		$comments_based_submission            = $this->createMock( Comments_Based_Submission::class );
		$comments_based_submission_repository = $this->createMock( Comments_Based_Submission_Repository::class );
		$comments_based_submission_repository
			->method( 'get_or_create' )
			->willReturn( $comments_based_submission );

		$comments_based_answer_repository = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based_answer_repository   = $this->createMock( Tables_Based_Answer_Repository::class );
		$grades                           = [ $this->createMock( Tables_Based_Grade::class ) ];

		$repository = new Table_Reading_Aggregate_Grade_Repository(
			$comments_based_repository,
			$tables_based_repository,
			$comments_based_submission_repository,
			$tables_based_answer_repository,
			$comments_based_answer_repository
		);

		/* Expect & Act */
		$tables_based_repository
			->expects( $this->once() )
			->method( 'save_many' )
			->with(
				$this->identicalTo( $submission ),
				$this->identicalTo( $grades )
			);
		$repository->save_many( $submission, $grades );
	}

	public function testSaveMany_Always_UsesCommentsBasedRepository(): void {
		/* Arrange */
		$submission = $this->createMock( Tables_Based_Submission::class );
		$submission->method( 'get_quiz_id' )->willReturn( 5 );
		$submission->method( 'get_user_id' )->willReturn( 6 );
		$submission->method( 'get_final_grade' )->willReturn( 7.0 );

		$tables_based_repository = $this->createMock( Tables_Based_Grade_Repository::class );

		$existing_grade            = new Comments_Based_Grade( 3, 4, 'feedback', new DateTimeImmutable(), new DateTimeImmutable() );
		$comments_based_repository = $this->createMock( Comments_Based_Grade_Repository::class );
		$comments_based_repository
			->method( 'get_all' )
			->with( 8 )
			->willReturn( [ $existing_grade ] );

		$grades = [ new Tables_Based_Grade( 1, 2, 3, 4, 'feedback2', new DateTimeImmutable(), new DateTimeImmutable() ) ];

		$comments_based_submission = $this->createMock( Comments_Based_Submission::class );
		$comments_based_submission->method( 'get_id' )->willReturn( 8 );

		$comments_based_submission_repository = $this->createMock( Comments_Based_Submission_Repository::class );
		$comments_based_submission_repository
			->method( 'get_or_create' )
			->with( 5, 6, 7.0 )
			->willReturn( $comments_based_submission );

		$comments_based_answer_repository = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based_answer_repository   = $this->createMock( Tables_Based_Answer_Repository::class );

		$repository = new Table_Reading_Aggregate_Grade_Repository(
			$comments_based_repository,
			$tables_based_repository,
			$comments_based_submission_repository,
			$tables_based_answer_repository,
			$comments_based_answer_repository
		);

		/* Expect & Act */
		$comments_based_repository
			->expects( $this->once() )
			->method( 'save_many' )
			->with(
				$this->identicalTo( $comments_based_submission ),
				$this->callback(
					function ( array $grades ) {
						$this->assertCount( 1, $grades );
						$this->assertSame( 'feedback2', $grades[0]->get_feedback() );

						return true;
					}
				)
			);
		$repository->save_many( $submission, $grades );
	}

	public function testSaveMany_CommentsBasedGradesNotFound_CreatesCommentsBasedGrades(): void {
		/* Arrange */
		$submission = $this->createMock( Tables_Based_Submission::class );
		$submission->method( 'get_id' )->willReturn( 4 );
		$submission->method( 'get_quiz_id' )->willReturn( 5 );
		$submission->method( 'get_user_id' )->willReturn( 6 );
		$submission->method( 'get_final_grade' )->willReturn( 7.0 );

		$tables_based_repository   = $this->createMock( Tables_Based_Grade_Repository::class );
		$comments_based_repository = $this->createMock( Comments_Based_Grade_Repository::class );
		$comments_based_repository
			->method( 'get_all' )
			->with( 8 )
			->willReturn( [] );

		$grades = [ new Tables_Based_Grade( 1, 2, 3, 4, 'feedback2', new DateTimeImmutable(), new DateTimeImmutable() ) ];

		$comments_based_submission = $this->createMock( Comments_Based_Submission::class );
		$comments_based_submission->method( 'get_id' )->willReturn( 8 );

		$comments_based_submission_repository = $this->createMock( Comments_Based_Submission_Repository::class );
		$comments_based_submission_repository
			->method( 'get_or_create' )
			->with( 5, 6, 7.0 )
			->willReturn( $comments_based_submission );

		$comments_based_answer            = new Comments_Based_Answer( 8, 3, '4', new DateTime( '@5' ), new DateTime( '@6' ) );
		$comments_based_answer_repository = $this->createMock( Comments_Based_Answer_Repository::class );
		$comments_based_answer_repository
			->method( 'get_all' )
			->with( 8 )
			->willReturn( [ $comments_based_answer ] );

		$tables_based_answer            = new Tables_Based_Answer( 2, 8, 3, '4', new DateTime( '@5' ), new DateTime( '@6' ) );
		$tables_based_answer_repository = $this->createMock( Tables_Based_Answer_Repository::class );
		$tables_based_answer_repository
			->method( 'get_all' )
			->with( 4 )
			->willReturn( [ $tables_based_answer ] );

		$repository = new Table_Reading_Aggregate_Grade_Repository(
			$comments_based_repository,
			$tables_based_repository,
			$comments_based_submission_repository,
			$tables_based_answer_repository,
			$comments_based_answer_repository
		);

		/* Expect & Act */
		$comments_based_repository
			->expects( $this->once() )
			->method( 'create' )
			->with(
				$this->identicalTo( $comments_based_submission ),
				$this->identicalTo( $comments_based_answer ),
				3,
				4,
				'feedback2'
			);
		$repository->save_many( $submission, $grades );
	}
}

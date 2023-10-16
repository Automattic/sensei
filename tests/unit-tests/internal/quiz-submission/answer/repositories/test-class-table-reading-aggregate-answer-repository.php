<?php

namespace SenseiTest\Internal\Quiz_Submission\Submission\Repositories;

use Sensei\Internal\Quiz_Submission\Answer\Repositories\Table_Reading_Aggregate_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Comments_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Tables_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Models\Tables_Based_Submission;
use Sensei\Internal\Quiz_Submission\Submission\Models\Comments_Based_Submission;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Comments_Based_Submission_Repository;

/**
 * Class Table_Reading_Aggregate_Answer_Repository_Test
 *
 * @covers \Sensei\Internal\Quiz_Submission\Answer\Repositories\Table_Reading_Aggregate_Answer_Repository
 */
class Table_Reading_Aggregate_Answer_Repository_Test extends \WP_UnitTestCase {
	public function testCreate_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based_submission = $this->createMock( Comments_Based_Submission::class );
		$tables_based_submission   = $this->createMock( Tables_Based_Submission::class );
		$tables_based_submission->method( 'get_quiz_id' )->willReturn( 1 );
		$tables_based_submission->method( 'get_user_id' )->willReturn( 2 );
		$tables_based_submission->method( 'get_final_grade' )->willReturn( 3.0 );

		$comments_based = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Answer_Repository::class );

		$comments_based_submission_repository = $this->createMock( Comments_Based_Submission_Repository::class );
		$comments_based_submission_repository
			->method( 'get_or_create' )
			->with( 1, 2, 3.0 )
			->willReturn( $comments_based_submission );

		$repository = new Table_Reading_Aggregate_Answer_Repository(
			$comments_based,
			$tables_based,
			$comments_based_submission_repository
		);

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'create' )
			->with( $comments_based_submission, 3, 'value' );

		$repository->create( $tables_based_submission, 3, 'value' );
	}

	public function testCreate_Always_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$tables_based_submission = $this->createMock( Tables_Based_Submission::class );
		$comments_based          = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based            = $this->createMock( Tables_Based_Answer_Repository::class );

		$comments_based_submission            = $this->createMock( Comments_Based_Submission::class );
		$comments_based_submission_repository = $this->createMock( Comments_Based_Submission_Repository::class );
		$comments_based_submission_repository
			->method( 'get_or_create' )
			->willReturn( $comments_based_submission );

		$repository = new Table_Reading_Aggregate_Answer_Repository(
			$comments_based,
			$tables_based,
			$comments_based_submission_repository
		);

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'create' )
			->with( $tables_based_submission, 1, 'value' );

		$repository->create( $tables_based_submission, 1, 'value' );
	}

	public function testGetAll_SubmissionIdGiven_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$comments_based                       = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based                         = $this->createMock( Tables_Based_Answer_Repository::class );
		$comments_based_submission_repository = $this->createMock( Comments_Based_Submission_Repository::class );

		$repository = new Table_Reading_Aggregate_Answer_Repository(
			$comments_based,
			$tables_based,
			$comments_based_submission_repository
		);

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'get_all' )
			->with( 1 );

		$repository->get_all( 1 );
	}

	public function testDeleteAll_CommentsBasedSubmissionFound_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based_submission = $this->createMock( Comments_Based_Submission::class );
		$tables_based_submission   = $this->createMock( Tables_Based_Submission::class );
		$tables_based_submission->method( 'get_quiz_id' )->willReturn( 1 );
		$tables_based_submission->method( 'get_user_id' )->willReturn( 2 );
		$tables_based_submission->method( 'get_final_grade' )->willReturn( 3.0 );

		$comments_based = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based   = $this->createMock( Tables_Based_Answer_Repository::class );

		$comments_based_submission_repository = $this->createMock( Comments_Based_Submission_Repository::class );
		$comments_based_submission_repository
			->method( 'get_or_create' )
			->with( 1, 2.0 )
			->willReturn( $comments_based_submission );

		$repository = new Table_Reading_Aggregate_Answer_Repository(
			$comments_based,
			$tables_based,
			$comments_based_submission_repository
		);

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'delete_all' )
			->with( $comments_based_submission );

		$repository->delete_all( $tables_based_submission );
	}

	public function testDeleteAll_SubmissionGiven_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$tables_based_submission = $this->createMock( Tables_Based_Submission::class );
		$comments_based          = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based            = $this->createMock( Tables_Based_Answer_Repository::class );

		$comments_based_submission            = $this->createMock( Comments_Based_Submission::class );
		$comments_based_submission_repository = $this->createMock( Comments_Based_Submission_Repository::class );
		$comments_based_submission_repository
			->method( 'get_or_create' )
			->willReturn( $comments_based_submission );

		$repository = new Table_Reading_Aggregate_Answer_Repository(
			$comments_based,
			$tables_based,
			$comments_based_submission_repository
		);

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'delete_all' )
			->with( $tables_based_submission );

		$repository->delete_all( $tables_based_submission );
	}
}

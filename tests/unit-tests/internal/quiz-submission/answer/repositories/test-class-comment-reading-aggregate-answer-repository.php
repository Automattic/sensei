<?php

namespace SenseiTest\Internal\Quiz_Submission\Submission\Repositories;

use Sensei\Internal\Quiz_Submission\Answer\Repositories\Comment_Reading_Aggregate_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Comments_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Tables_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Models\Tables_Based_Submission;
use Sensei\Internal\Quiz_Submission\Submission\Models\Comments_Based_Submission;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Tables_Based_Submission_Repository;

/**
 * Class Comment_Reading_Aggregate_Answer_Repository_Test
 *
 * @covers \Sensei\Internal\Quiz_Submission\Answer\Repositories\Comment_Reading_Aggregate_Answer_Repository
 */
class Comment_Reading_Aggregate_Answer_Repository_Test extends \WP_UnitTestCase {
	public function testCreate_Always_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$submission = $this->createMock( Comments_Based_Submission::class );
		$submission->method( 'get_quiz_id' )->willReturn( 1 );
		$submission->method( 'get_user_id' )->willReturn( 2 );
		$submission->method( 'get_final_grade' )->willReturn( 3.0 );

		$tables_based_submission = $this->createMock( Tables_Based_Submission::class );
		$comments_based          = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based            = $this->createMock( Tables_Based_Answer_Repository::class );

		$tables_based_submission_repository = $this->createMock( Tables_Based_Submission_Repository::class );
		$tables_based_submission_repository
			->method( 'get_or_create' )
			->with( 1, 2, 3.0 )
			->willReturn( $tables_based_submission );

		$repository = new Comment_Reading_Aggregate_Answer_Repository(
			$comments_based,
			$tables_based,
			$tables_based_submission_repository
		);

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'create' )
			->with( $tables_based_submission, 3, 'value' );

		$repository->create( $submission, 3, 'value' );
	}

	public function testCreate_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$submission                         = $this->createMock( Comments_Based_Submission::class );
		$comments_based                     = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based                       = $this->createMock( Tables_Based_Answer_Repository::class );
		$tables_based_submission_repository = $this->createMock( Tables_Based_Submission_Repository::class );

		$repository = new Comment_Reading_Aggregate_Answer_Repository(
			$comments_based,
			$tables_based,
			$tables_based_submission_repository
		);

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'create' )
			->with( $submission, 1, 'value' );

		$repository->create( $submission, 1, 'value' );
	}

	public function testGetAll_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$comments_based                     = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based                       = $this->createMock( Tables_Based_Answer_Repository::class );
		$tables_based_submission_repository = $this->createMock( Tables_Based_Submission_Repository::class );

		$repository = new Comment_Reading_Aggregate_Answer_Repository(
			$comments_based,
			$tables_based,
			$tables_based_submission_repository
		);

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'get_all' )
			->with( 1 );

		$repository->get_all( 1 );
	}

	public function testDeleteAll_Always_CallsTablesBasedRepository(): void {
		/* Arrange. */
		$submission = $this->createMock( Comments_Based_Submission::class );
		$submission->method( 'get_quiz_id' )->willReturn( 1 );
		$submission->method( 'get_user_id' )->willReturn( 2 );
		$submission->method( 'get_final_grade' )->willReturn( 3.0 );

		$tables_based_submission = $this->createMock( Tables_Based_Submission::class );
		$comments_based          = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based            = $this->createMock( Tables_Based_Answer_Repository::class );

		$tables_based_submission_repository = $this->createMock( Tables_Based_Submission_Repository::class );
		$tables_based_submission_repository
			->method( 'get_or_create' )
			->with( 1, 2.0 )
			->willReturn( $tables_based_submission );

		$repository = new Comment_Reading_Aggregate_Answer_Repository(
			$comments_based,
			$tables_based,
			$tables_based_submission_repository
		);

		/* Expect & Act. */
		$tables_based
			->expects( $this->once() )
			->method( 'delete_all' )
			->with( $tables_based_submission );

		$repository->delete_all( $submission );
	}

	public function testDeleteAll_Always_CallsCommentsBasedRepository(): void {
		/* Arrange. */
		$submission                         = $this->createMock( Comments_Based_Submission::class );
		$comments_based                     = $this->createMock( Comments_Based_Answer_Repository::class );
		$tables_based                       = $this->createMock( Tables_Based_Answer_Repository::class );
		$tables_based_submission_repository = $this->createMock( Tables_Based_Submission_Repository::class );

		$repository = new Comment_Reading_Aggregate_Answer_Repository(
			$comments_based,
			$tables_based,
			$tables_based_submission_repository
		);

		/* Expect & Act. */
		$comments_based
			->expects( $this->once() )
			->method( 'delete_all' )
			->with( $submission );

		$repository->delete_all( $submission );
	}
}

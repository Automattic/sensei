<?php

namespace SenseiTest\Internal\Quiz_Submission\Submission\Repositories;

use RuntimeException;
use Sensei\Internal\Quiz_Submission\Submission\Models\Comments_Based_Submission;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Comments_Based_Submission_Repository;
use Sensei_Utils;

/**
 * Class Comments_Based_Submission_Repository_Test
 *
 * @covers \Sensei\Internal\Quiz_Submission\Submission\Repositories\Comments_Based_Submission_Repository
 */
class Comments_Based_Submission_Repository_Test extends \WP_UnitTestCase {

	protected $factory;

	public function setUp(): void {
		parent::setUp();
		$this->factory = new \Sensei_Factory();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testCreate_WhenLessonStatusNotFound_ThrowsException(): void {
		/* Arrange. */
		$repository = new Comments_Based_Submission_Repository();

		/* Assert. */
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Missing lesson status.' );

		/* Act. */
		$repository->create( 1, 2 );
	}

	public function testCreate_WhenLessonStatusFound_ReturnsSubmission(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$quiz_id    = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
		$repository = new Comments_Based_Submission_Repository();

		Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );

		/* Act. */
		$submission = $repository->create( $quiz_id, $user_id, 12.34 );

		/* Assert. */
		$expected = [
			'quiz_id'     => $quiz_id,
			'user_id'     => $user_id,
			'final_grade' => 12.34,
		];

		$this->assertSame( $expected, $this->export_submission( $submission ) );
	}

	public function testGetOrCreate_WhenSubmissionExists_ReturnsExistingSubmission(): void {
		/* Arrange. */
		$repository_mock = $this->getMockBuilder( Comments_Based_Submission_Repository::class )
			->setMethods( [ 'get', 'create' ] )
			->getMock();

		/* Assert. */
		$repository_mock
			->expects( $this->once() )
			->method( 'get' )
			->willReturn( $this->createMock( Comments_Based_Submission::class ) );

		$repository_mock
			->expects( $this->never() )
			->method( 'create' );

		/* Act. */
		$repository_mock->get_or_create( 1, 2 );
	}

	public function testGetOrCreate_WhenSubmissionDoesNotExist_ReturnsNewSubmission(): void {
		/* Arrange. */
		$repository_mock = $this->getMockBuilder( Comments_Based_Submission_Repository::class )
			->setMethods( [ 'get', 'create' ] )
			->getMock();

		/* Assert. */
		$repository_mock
			->expects( $this->once() )
			->method( 'get' )
			->willReturn( null );

		$repository_mock
			->expects( $this->once() )
			->method( 'create' );

		/* Act. */
		$repository_mock->get_or_create( 1, 2 );
	}

	public function testGet_WhenLessonStatusNotFound_ReturnsNull(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$quiz_id    = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
		$repository = new Comments_Based_Submission_Repository();

		/* Act. */
		$submission = $repository->get( $quiz_id, $user_id );

		/* Assert. */
		$this->assertNull( $submission );
	}

	public function testGet_WhenNoQuestionsSubmitted_ReturnsNull(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$quiz_id    = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
		$repository = new Comments_Based_Submission_Repository();

		Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );

		/* Act. */
		$submission = $repository->get( $quiz_id, $user_id );

		/* Assert. */
		$this->assertNull( $submission );
	}

	public function testGet_WhenFinalGradeIsZero_ReturnsSubmissionWithZeroFinalGrade(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$quiz_id    = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
		$repository = new Comments_Based_Submission_Repository();

		$submission_id = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );

		$submission = $repository->create( $quiz_id, $user_id, 0 );

		update_comment_meta( $submission_id, 'questions_asked', '1,2' );

		/* Act. */
		$submission = $repository->get( $quiz_id, $user_id );

		/* Assert. */
		$this->assertSame( 0.0, $submission->get_final_grade() );
	}

	public function testGetQuestionIds_WhenLessonStatusFound_ReturnsQuestionIds(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$quiz_id    = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
		$repository = new Comments_Based_Submission_Repository();

		$comment_id = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );

		update_comment_meta( $comment_id, 'questions_asked', implode( ',', [ 1, 2, 3 ] ) );

		/* Act. */
		$question_ids = $repository->get_question_ids( $comment_id );

		/* Assert. */
		$this->assertSame( [ 1, 2, 3 ], $question_ids );
	}

	public function testGetQuestionIds_WhenNoQuestionsAsked_ReturnsEmptyArray(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$quiz_id    = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
		$repository = new Comments_Based_Submission_Repository();

		$comment_id = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );

		/* Act. */
		$question_ids = $repository->get_question_ids( $comment_id );

		/* Assert. */
		$this->assertSame( [], $question_ids );
	}

	public function testSave_WhenLessonStatusNotFound_ThrowsException(): void {
		/* Arrange. */
		$submission = $this->createMock( Comments_Based_Submission::class );
		$repository = new Comments_Based_Submission_Repository();

		/* Assert. */
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Missing lesson status.' );

		/* Act. */
		$repository->save( $submission );
	}

	public function testSave_WhenGradeIsSet_UpdatesTheGrade(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$quiz_id    = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
		$repository = new Comments_Based_Submission_Repository();

		$submission_id = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );

		$submission = $repository->create( $quiz_id, $user_id );

		update_comment_meta( $submission_id, 'questions_asked', '1,2' );

		/* Act. */
		$submission->set_final_grade( 12.34 );
		$repository->save( $submission );

		/* Assert. */
		$this->assertEquals(
			$this->export_submission( $submission ),
			$this->export_submission( $repository->get( $quiz_id, $user_id ) )
		);
	}

	public function testSave_WhenGradeIsSetToNull_UpdatesTheGrade(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$quiz_id    = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
		$repository = new Comments_Based_Submission_Repository();

		$submission_id = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );

		update_comment_meta( $submission_id, 'questions_asked', '1,2' );

		$submission = $repository->create( $quiz_id, $user_id, 12.34 );

		/* Act. */
		$submission->set_final_grade( null );
		$repository->save( $submission );

		/* Assert. */
		$this->assertNull(
			$repository->get( $quiz_id, $user_id )->get_final_grade()
		);
	}

	public function testDelete_WhenCalled_DeletesTheGrade(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$quiz_id    = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
		$repository = new Comments_Based_Submission_Repository();

		$submission_id = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );

		update_comment_meta( $submission_id, 'questions_asked', '1,2' );

		$submission = $repository->create( $quiz_id, $user_id, 12.34 );

		/* Act. */
		$repository->delete( $submission );

		/* Assert. */
		$this->assertNull( $repository->get( $quiz_id, $user_id )->get_final_grade() );
	}

	private function export_submission( Comments_Based_Submission $submission ): array {
		return [
			'quiz_id'     => $submission->get_quiz_id(),
			'user_id'     => $submission->get_user_id(),
			'final_grade' => $submission->get_final_grade(),
		];
	}

}

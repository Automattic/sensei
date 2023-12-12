<?php

namespace SenseiTest\Internal\Quiz_Submission\Answer\Repositories;

use Sensei\Internal\Quiz_Submission\Answer\Models\Comments_Based_Answer;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Comments_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Models\Comments_Based_Submission;
use Sensei_Utils;

/**
 * Class Comments_Based_Answer_Repository_Test
 *
 * @covers \Sensei\Internal\Quiz_Submission\Answer\Repositories\Comments_Based_Answer_Repository
 */
class Comments_Based_Answer_Repository_Test extends \WP_UnitTestCase {

	protected $factory;

	public function setUp(): void {
		parent::setUp();
		$this->factory = new \Sensei_Factory();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testCreate_WhenCalled_ReturnsAnswer(): void {
		/* Arrange. */
		$lesson_id         = $this->factory->lesson->create();
		$user_id           = $this->factory->user->create();
		$answer_repository = new Comments_Based_Answer_Repository();
		$submission        = $this->create_submission( $lesson_id, $user_id );

		/* Act. */
		$answer = $answer_repository->create( $submission, 1, 'Yes' );

		/* Assert. */
		$expected = [
			'question_id' => 1,
			'value'       => 'Yes',
		];

		$this->assertSame( $expected, $this->export_answer( $answer ) );
	}

	public function testCreate_WhenCalled_SavesTheAnswer(): void {
		/* Arrange. */
		$lesson_id         = $this->factory->lesson->create();
		$user_id           = $this->factory->user->create();
		$submission        = $this->create_submission( $lesson_id, $user_id );
		$answer_repository = new Comments_Based_Answer_Repository();

		/* Act. */
		$answer_repository->create( $submission, 1, 'Yes' );
		$answer_repository->create( $submission, 2, 'No' );

		/* Assert. */
		$this->assertSame(
			[
				1 => 'Yes',
				2 => 'No',
			],
			get_comment_meta( $submission->get_id(), 'quiz_answers', true )
		);
	}

	public function testCreate_WhenCalled_SavesTheQuestionsAsked(): void {
		/* Arrange. */
		$lesson_id         = $this->factory->lesson->create();
		$user_id           = $this->factory->user->create();
		$submission        = $this->create_submission( $lesson_id, $user_id );
		$answer_repository = new Comments_Based_Answer_Repository();

		/* Act. */
		$answer_repository->create( $submission, 1, 'Yes' );
		$answer_repository->create( $submission, 2, 'No' );

		/* Assert. */
		$this->assertSame(
			'1,2',
			get_comment_meta( $submission->get_id(), 'questions_asked', true )
		);
	}

	public function testGetAll_WhenCalled_ReturnsAllAnswers(): void {
		/* Arrange. */
		$lesson_id         = $this->factory->lesson->create();
		$user_id           = $this->factory->user->create();
		$submission        = $this->create_submission( $lesson_id, $user_id );
		$answer_repository = new Comments_Based_Answer_Repository();

		$answer_1 = $answer_repository->create( $submission, 1, 'Yes' );
		$answer_2 = $answer_repository->create( $submission, 2, 'No' );

		/* Act. */
		$answers = $answer_repository->get_all( $submission->get_id() );

		/* Assert. */
		$this->assertSame(
			[
				$this->export_answer( $answer_1 ),
				$this->export_answer( $answer_2 ),
			],
			array_map(
				[ $this, 'export_answer' ],
				$answers
			)
		);
	}

	private function create_submission( $lesson_id, $user_id ): Comments_Based_Submission {
		$submission_id = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );
		return new Comments_Based_Submission(
			$submission_id,
			$lesson_id,
			$user_id,
			null,
			new \DateTimeImmutable(),
			new \DateTimeImmutable()
		);
	}

	public function testDeleteAll_WhenCalled_DeletesAllGrades(): void {
		/* Arrange. */
		$lesson_id         = $this->factory->lesson->create();
		$user_id           = $this->factory->user->create();
		$submission        = $this->create_submission( $lesson_id, $user_id );
		$answer_repository = new Comments_Based_Answer_Repository();

		$answer_repository->create( $submission, 1, 'Yes' );

		/* Act. */
		$answer_repository->delete_all( $submission );

		/* Assert. */
		$this->assertSame(
			[],
			$answer_repository->get_all( $submission->get_id() )
		);
	}

	private function export_answer( Comments_Based_Answer $answer ): array {
		return [
			'question_id' => $answer->get_question_id(),
			'value'       => $answer->get_value(),
		];
	}

}

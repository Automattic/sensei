<?php

namespace SenseiTest\Internal\Quiz_Submission\Grade\Repositories;

use Sensei\Internal\Quiz_Submission\Grade\Models\Grade;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Comments_Based_Grade_Repository;
use Sensei_Utils;

/**
 * Class Comments_Based_Grade_Repository_Test
 *
 * @covers \Sensei\Internal\Quiz_Submission\Grade\Repositories\Comments_Based_Grade_Repository
 */
class Comments_Based_Grade_Repository_Test extends \WP_UnitTestCase {

	protected $factory;

	public function setUp(): void {
		parent::setUp();
		$this->factory = new \Sensei_Factory();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testCreate_WhenCalled_ReturnsGrade(): void {
		/* Arrange. */
		$lesson_id        = $this->factory->lesson->create();
		$user_id          = $this->factory->user->create();
		$grade_repository = new Comments_Based_Grade_Repository();
		$submission_id    = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );

		/* Act. */
		$grade = $grade_repository->create( $submission_id, 0, 1, 22, 'Great!' );

		/* Assert. */
		$expected = [
			'answer_id'   => 0,
			'question_id' => 1,
			'points'      => 22,
			'feedback'    => 'Great!',
		];

		$this->assertSame( $expected, $this->export_grade( $grade ) );
	}

	public function testCreate_WhenCalled_SavesTheGrade(): void {
		/* Arrange. */
		$lesson_id        = $this->factory->lesson->create();
		$user_id          = $this->factory->user->create();
		$grade_repository = new Comments_Based_Grade_Repository();
		$submission_id    = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );

		/* Act. */
		$grade_repository->create( $submission_id, 0, 1, 22, 'Great!' );
		$grade_repository->create( $submission_id, 0, 2, 33, 'Awesome!' );

		/* Assert. */
		$this->assertSame(
			[
				1 => 22,
				2 => 33,
			],
			get_comment_meta( $submission_id, 'quiz_grades', true )
		);
	}

	public function testCreate_WhenCalled_SavesTheGradeFeedback(): void {
		/* Arrange. */
		$lesson_id        = $this->factory->lesson->create();
		$user_id          = $this->factory->user->create();
		$grade_repository = new Comments_Based_Grade_Repository();
		$submission_id    = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );

		/* Act. */
		$grade_repository->create( $submission_id, 0, 1, 22, 'Great!' );
		$grade_repository->create( $submission_id, 0, 2, 33, 'Awesome!' );

		/* Assert. */
		$this->assertSame(
			[
				1 => 'Great!',
				2 => 'Awesome!',
			],
			get_comment_meta( $submission_id, 'quiz_answers_feedback', true )
		);
	}

	public function testGetAll_WhenHasNoGrades_ReturnsEmptyArray(): void {
		/* Arrange. */
		$lesson_id        = $this->factory->lesson->create();
		$user_id          = $this->factory->user->create();
		$grade_repository = new Comments_Based_Grade_Repository();
		$submission_id    = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );

		/* Act. */
		$grades = $grade_repository->get_all( $submission_id );

		/* Assert. */
		$this->assertSame( [], $grades );
	}

	public function testGetAll_WhenHasGrades_ReturnsAllGrades(): void {
		/* Arrange. */
		$lesson_id        = $this->factory->lesson->create();
		$user_id          = $this->factory->user->create();
		$grade_repository = new Comments_Based_Grade_Repository();
		$submission_id    = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );

		$grade_1 = $grade_repository->create( $submission_id, 0, 1, 22, 'Great!' );
		$grade_2 = $grade_repository->create( $submission_id, 0, 2, 33, 'Awesome!' );

		/* Act. */
		$grades = $grade_repository->get_all( $submission_id );

		/* Assert. */
		$this->assertSame(
			[
				$this->export_grade( $grade_1 ),
				$this->export_grade( $grade_2 ),
			],
			array_map(
				[ $this, 'export_grade' ],
				$grades
			)
		);
	}

	public function testSaveMany_WhenCalled_SavesAllGrades(): void {
		/* Arrange. */
		$lesson_id        = $this->factory->lesson->create();
		$user_id          = $this->factory->user->create();
		$grade_repository = new Comments_Based_Grade_Repository();
		$submission_id    = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );

		$grade_1 = $grade_repository->create( $submission_id, 0, 1, 22, 'Great!' );
		$grade_2 = $grade_repository->create( $submission_id, 0, 2, 33, 'Awesome!' );

		$grade_1->set_feedback( 'Amazing!' );
		$grade_2->set_feedback( 'Wow!' );

		/* Act. */
		$grade_repository->save_many( $submission_id, [ $grade_1, $grade_2 ] );

		/* Assert. */
		$this->assertSame(
			[
				$this->export_grade( $grade_1 ),
				$this->export_grade( $grade_2 ),
			],
			array_map(
				[ $this, 'export_grade' ],
				$grade_repository->get_all( $submission_id )
			)
		);
	}

	public function testDeleteAll_WhenCalled_DeletesAllGrades(): void {
		/* Arrange. */
		$lesson_id        = $this->factory->lesson->create();
		$user_id          = $this->factory->user->create();
		$grade_repository = new Comments_Based_Grade_Repository();
		$submission_id    = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );

		$grade_repository->create( $submission_id, 0, 1, 22, 'Great!' );

		/* Act. */
		$grade_repository->delete_all( $submission_id );

		/* Assert. */
		$this->assertSame(
			[],
			$grade_repository->get_all( $submission_id )
		);
	}

	private function export_grade( Grade $grade ): array {
		return [
			'answer_id'   => $grade->get_answer_id(),
			'question_id' => $grade->get_question_id(),
			'points'      => $grade->get_points(),
			'feedback'    => $grade->get_feedback(),
		];
	}

}

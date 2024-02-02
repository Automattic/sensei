<?php

use Sensei\Internal\Quiz_Submission\Answer\Models\Answer_Interface;
use Sensei\Internal\Quiz_Submission\Grade\Models\Grade_Interface;

/**
 * Tests for Sensei_Update_Legacy_Quiz_Data class.
 *
 * @group update-tasks
 * @group background-jobs
 */
class Sensei_Update_Legacy_Quiz_Data_Test extends WP_UnitTestCase {
	/**
	 * Sensei Factory.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Set up the tests.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->factory = new Sensei_Factory();
	}

	public function testRun_WhenHasMultipleAnswers_MigratesTheAnswers() {
		/* Arrange */
		$lesson_id  = $this->factory->lesson->create();
		$quiz_id    = $this->factory->quiz->create(
			array(
				'post_parent' => $lesson_id,
			)
		);
		$question_1 = $this->factory->question->create(
			array(
				'quiz_id' => $quiz_id,
			)
		);
		$question_2 = $this->factory->question->create(
			array(
				'quiz_id' => $quiz_id,
			)
		);
		$answer_1   = $this->factory->comment->create(
			array(
				'comment_post_ID'  => $question_1,
				'comment_content'  => 'answer 1',
				'comment_type'     => 'sensei_user_answer',
				'comment_approved' => 'log',
				'user_id'          => 1,
			)
		);
		$answer_2   = $this->factory->comment->create(
			array(
				'comment_post_ID'  => $question_2,
				'comment_content'  => 'answer 2',
				'comment_type'     => 'sensei_user_answer',
				'comment_approved' => 'log',
				'user_id'          => 1,
			)
		);

		Sensei_Utils::sensei_start_lesson( $lesson_id, 1 );

		/* Act */
		$instance = new Sensei_Update_Legacy_Quiz_Data();
		$instance->run();

		/* Assert */
		$submission = Sensei()->quiz_submission_repository->get( $quiz_id, 1 );
		$answers    = Sensei()->quiz_answer_repository->get_all( $submission->get_id() );

		$this->assertSame(
			array(
				array(
					'question_id' => $question_1,
					'value'       => 'answer 1',
				),
				array(
					'question_id' => $question_2,
					'value'       => 'answer 2',
				),
			),
			array(
				$this->get_answer_data( $answers[0] ),
				$this->get_answer_data( $answers[1] ),
			)
		);
	}

	public function testRun_WhenHasMultipleGrades_MigratesTheGrades() {
		/* Arrange */
		$lesson_id  = $this->factory->lesson->create();
		$quiz_id    = $this->factory->quiz->create(
			array(
				'post_parent' => $lesson_id,
			)
		);
		$question_1 = $this->factory->question->create(
			array(
				'quiz_id' => $quiz_id,
			)
		);
		$question_2 = $this->factory->question->create(
			array(
				'quiz_id' => $quiz_id,
			)
		);
		$answer_1   = $this->factory->comment->create(
			array(
				'comment_post_ID'  => $question_1,
				'comment_content'  => 'answer 1',
				'comment_type'     => 'sensei_user_answer',
				'comment_approved' => 'log',
				'user_id'          => 1,
			)
		);
		$answer_2   = $this->factory->comment->create(
			array(
				'comment_post_ID'  => $question_2,
				'comment_content'  => 'answer 2',
				'comment_type'     => 'sensei_user_answer',
				'comment_approved' => 'log',
				'user_id'          => 1,
			)
		);

		update_comment_meta( $answer_1, 'user_grade', 1 );
		update_comment_meta( $answer_1, 'answer_note', 'note 1' );
		update_comment_meta( $answer_2, 'user_grade', 0 );

		Sensei_Utils::sensei_start_lesson( $lesson_id, 1 );

		/* Act */
		$instance = new Sensei_Update_Legacy_Quiz_Data();
		$instance->run();

		/* Assert */
		$submission = Sensei()->quiz_submission_repository->get( $quiz_id, 1 );
		$grades     = Sensei()->quiz_grade_repository->get_all( $submission->get_id() );

		$this->assertSame(
			array(
				array(
					'question_id' => $question_1,
					'points'      => 1,
					'feedback'    => 'note 1',
				),
				array(
					'question_id' => $question_2,
					'points'      => 0,
					'feedback'    => null,
				),
			),
			array(
				$this->get_grade_data( $grades[0] ),
				$this->get_grade_data( $grades[1] ),
			)
		);
	}

	private function get_answer_data( Answer_Interface $answer ) : array {
		return array(
			'question_id' => $answer->get_question_id(),
			'value'       => $answer->get_value(),
		);
	}

	private function get_grade_data( Grade_Interface $grade ) : array {
		return array(
			'question_id' => $grade->get_question_id(),
			'points'      => $grade->get_points(),
			'feedback'    => $grade->get_feedback(),
		);
	}
}

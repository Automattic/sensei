<?php

/**
 * Tests for Sensei_Question class.
 */
class Sensei_Question_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	protected $factory;

	protected function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * This behavior comes from the UI because in the UI the settings related to answer feedback are hidden when pass is not required.
	 */
	public function testTheAnswerFeedback_WhenPassIsNotRequired_ReturnAnswerFeedbackRegardlessOtherSettings() {
		/* Arrange */
		$this->login_as_student();

		$question_id = $this->create_graded_quiz_with_metas_including_one_question_and_return_question_id(
			[
				'_pass_required'               => '',
				'_quiz_passmark'               => '80',
				'_failed_indicate_incorrect'   => 'no',
				'_failed_show_correct_answers' => 'no',
				'_failed_show_answer_feedback' => 'no',
			]
		);

		/* Act */
		ob_start();
		Sensei_Question::the_answer_feedback( $question_id );
		$output = ob_get_clean();

		/* Assert */
		$this->assertStringContainsString( '<div class="sensei-lms-question__answer-feedback__header">', $output );
	}

	public function testTheAnswerFeedback_WhenPassIsRequiredAndPassmarkIsAchieved_RespectAnswerFeedbackSettings() {
		/* Arrange */
		$this->login_as_student();

		$question_id = $this->create_graded_quiz_with_metas_including_one_question_and_return_question_id(
			[
				'_pass_required'               => 'on',
				'_quiz_passmark'               => '80',
				'_failed_indicate_incorrect'   => 'yes',
				'_failed_show_correct_answers' => 'no',
				'_failed_show_answer_feedback' => 'yes',
			]
		);

		/* Act */
		ob_start();
		Sensei_Question::the_answer_feedback( $question_id );
		$output = ob_get_clean();

		/* Assert */
		$this->assertStringContainsString( '<div class="sensei-lms-question__answer-feedback__header">', $output );
		$this->assertStringNotContainsString( '<div class="sensei-lms-question__answer-feedback__correct-answer">', $output );
		$this->assertStringContainsString( '<div class="sensei-lms-question__answer-feedback__answer-notes">', $output );
	}

	private function create_graded_quiz_with_metas_including_one_question_and_return_question_id( $metas ) {
		// Create a quiz with a question.
		$lesson_id   = $this->factory->lesson->create();
		$meta_input  = wp_parse_args(
			$metas,
			[
				'_quiz_lesson' => $lesson_id,
			]
		);
		$quiz_id     = $this->factory->quiz->create(
			[
				'post_parent' => $lesson_id,
				'meta_input'  => $meta_input,
			]
		);
		$question_id = $this->factory->question->create( [ 'quiz_id' => $quiz_id ] );
		$user_id     = wp_get_current_user()->ID;

		// Set current post as the quiz.
		$GLOBALS['post'] = get_post( $quiz_id );

		// Create quiz answers.
		$user_quiz_answers = $this->factory->generate_user_quiz_answers( $quiz_id );
		Sensei()->quiz->save_user_answers( $user_quiz_answers, array(), $lesson_id, $user_id );

		// Grade the quiz.
		$quiz_progress = Sensei()->quiz_progress_repository->get( $quiz_id, $user_id );
		$quiz_progress->grade();
		Sensei()->quiz_progress_repository->save( $quiz_progress );

		return $question_id;
	}
}

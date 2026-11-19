<?php

/**
 * Tests for the single quiz question type templates.
 *
 * The templates render their inputs through `sensei_the_question_content()`.
 * A visitor who is not enrolled sees the quiz in a disabled state, while a
 * student who already submitted it sees the submitted answer as text.
 *
 * PHPUnit converts PHP deprecations to exceptions, so rendering with a
 * `null` answer on PHP 8.1+ also fails these tests when a template
 * reintroduces that regression.
 */
class Sensei_Quiz_Question_Templates_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	protected $factory;

	protected function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	protected function tearDown(): void {
		parent::tearDown();

		$this->factory->tearDown();
		unset( $GLOBALS['post'] );
	}

	public function testSenseiTheQuestionContent_GapFillQuestionOnUnavailableQuiz_RendersDisabledInput() {
		/* Arrange. */
		$quiz = $this->create_quiz_with_question( 'gap-fill' );

		/* Act. */
		$output = $this->render_question_answer( $quiz['quiz_id'] );

		/* Assert. */
		$this->assertMatchesRegularExpression( '/<input[^>]*class="gapfill-answer-gap"[^>]*disabled/', $output );
	}

	public function testSenseiTheQuestionContent_SingleLineQuestionOnUnavailableQuiz_RendersDisabledInput() {
		/* Arrange. */
		$quiz = $this->create_quiz_with_question( 'single-line' );

		/* Act. */
		$output = $this->render_question_answer( $quiz['quiz_id'] );

		/* Assert. */
		$this->assertMatchesRegularExpression( '/<input[^>]+disabled/', $output );
	}

	public function testSenseiTheQuestionContent_MultiLineQuestionOnUnavailableQuiz_RendersDisabledTextarea() {
		/* Arrange. */
		$quiz = $this->create_quiz_with_question( 'multi-line' );

		/* Act. */
		$output = $this->render_question_answer( $quiz['quiz_id'] );

		/* Assert. */
		$this->assertMatchesRegularExpression( '/<textarea[^>]+disabled/', $output );
	}

	public function testSenseiTheQuestionContent_FileUploadQuestionOnUnavailableQuiz_RendersDisabledFileInput() {
		/* Arrange. */
		$quiz = $this->create_quiz_with_question( 'file-upload' );

		/* Act. */
		$output = $this->render_question_answer( $quiz['quiz_id'] );

		/* Assert. */
		$this->assertMatchesRegularExpression( '/<input[^>]*type="file"[^>]*disabled/', $output );
	}

	public function testSenseiTheQuestionContent_GapFillQuestionOnAvailableQuiz_RendersEditableInput() {
		/* Arrange. */
		$quiz    = $this->create_quiz_with_question( 'gap-fill' );
		$user_id = $this->factory->user->create();
		$this->login_as( $user_id );
		$this->enrol_user_in_course();

		/* Act. */
		$output = $this->render_question_answer( $quiz['quiz_id'] );

		/* Assert. */
		$this->assertMatchesRegularExpression( '/<input(?:(?!disabled).)*class="gapfill-answer-gap"(?:(?!disabled).)*\/>/s', $output );
	}

	public function testSenseiTheQuestionContent_GapFillQuestionOnCompletedQuiz_RendersSubmittedAnswer() {
		/* Arrange. */
		$quiz    = $this->create_quiz_with_question( 'gap-fill' );
		$user_id = $this->factory->user->create();
		$this->login_as( $user_id );
		$this->enrol_user_in_course();
		$this->submit_quiz_answer( $quiz, $user_id, 'My stored answer' );

		/* Act. */
		$output = $this->render_question_answer( $quiz['quiz_id'] );

		/* Assert. */
		$this->assertMatchesRegularExpression( '/class="wp-block-sensei-lms-question-answers__answer">\s*My stored answer\s*<\/span>/', $output );
	}

	public function testSenseiTheQuestionContent_MultiLineQuestionOnCompletedQuiz_RendersSubmittedAnswer() {
		/* Arrange. */
		$quiz    = $this->create_quiz_with_question( 'multi-line' );
		$user_id = $this->factory->user->create();
		$this->login_as( $user_id );
		$this->enrol_user_in_course();
		$this->submit_quiz_answer( $quiz, $user_id, 'My stored answer' );

		/* Act. */
		$output = $this->render_question_answer( $quiz['quiz_id'] );

		/* Assert. */
		$this->assertMatchesRegularExpression( '/class="wp-block-sensei-lms-question-answers__answer">\s*My stored answer\s*<\/div>/', $output );
	}

	/**
	 * Create a course, a lesson with a quiz, and one question of the given type.
	 *
	 * @param string $question_type Question type slug.
	 * @return array{course_id: int, lesson_id: int, quiz_id: int, question_id: int}
	 */
	private function create_quiz_with_question( string $question_type ): array {
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create();
		add_post_meta( $lesson_id, '_lesson_course', $course_id );

		$quiz_id     = $this->factory->quiz->create( array( 'post_parent' => $lesson_id ) );
		$question_id = $this->factory->question->create(
			array(
				'quiz_id'       => $quiz_id,
				'question_type' => $question_type,
			)
		);

		return array(
			'course_id'   => $course_id,
			'lesson_id'   => $lesson_id,
			'quiz_id'     => $quiz_id,
			'question_id' => $question_id,
		);
	}

	/**
	 * Make the current user count as enrolled so quiz availability checks pass.
	 */
	private function enrol_user_in_course(): void {
		// Same pattern the lesson and course theme tests use for an enrolled student.
		add_filter( 'sensei_is_enrolled', '__return_true' );
	}

	/**
	 * Save and grade an answer for the quiz's single question.
	 *
	 * @param array  $quiz    Fixture returned by `create_quiz_with_question()`.
	 * @param int    $user_id User ID.
	 * @param string $answer  Answer to submit.
	 */
	private function submit_quiz_answer( array $quiz, int $user_id, string $answer ): void {
		// Remove the hooks within the submit function to avoid side effects.
		remove_all_actions( 'sensei_user_quiz_submitted' );
		remove_all_actions( 'sensei_user_lesson_end' );

		WooThemes_Sensei_Quiz::submit_answers_for_grading(
			array( $quiz['question_id'] => $answer ),
			array(),
			$quiz['lesson_id'],
			$user_id
		);
	}

	/**
	 * Render the answer part of the quiz's first question through the loop.
	 *
	 * @param int $quiz_id Quiz post ID.
	 * @return string Rendered markup.
	 */
	private function render_question_answer( int $quiz_id ): string {
		$GLOBALS['post'] = get_post( $quiz_id );

		Sensei_Quiz::start_quiz_questions_loop();
		sensei_setup_the_question();

		ob_start();
		sensei_the_question_content();

		return ob_get_clean();
	}
}

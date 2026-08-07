<?php

namespace SenseiTest\WPML;

use Sensei\WPML\Question_Display;

/**
 * Class Question_Display_Test
 *
 * @covers \Sensei\WPML\Question_Display
 */
class Question_Display_Test extends \WP_UnitTestCase {
	/**
	 * Sensei Factory.
	 *
	 * @var \Sensei_Factory
	 */
	protected $factory;

	public function set_up(): void {
		parent::set_up();
		$this->factory = new \Sensei_Factory();
	}

	public function tear_down(): void {
		remove_all_filters( 'wpml_current_language' );
		remove_all_filters( 'wpml_object_id' );
		parent::tear_down();
		$this->factory->tearDown();
	}

	public function testInit_WhenCalled_AddsFilters() {
		/* Arrange. */
		$question_display = new Question_Display();

		/* Act. */
		$question_display->init();

		/* Assert. */
		$this->assertEquals( 15, has_filter( 'sensei_get_question_template_data', array( $question_display, 'translate_template_data' ) ), 'The template data filter should be added after the type loaders.' );
		$this->assertEquals( 10, has_filter( 'the_title', array( $question_display, 'translate_question_title' ) ), 'The title filter should be added.' );
		$this->assertEquals( 10, has_filter( 'sensei_question_description_post_id', array( $question_display, 'translate_question_description_id' ) ), 'The description question filter should be added.' );
		$this->assertEquals( 10, has_filter( 'sensei_question_answer_message_correct_answer', array( $question_display, 'translate_correct_answer_message' ) ), 'The right-answer reveal filter should be added.' );
		$this->assertEquals( 10, has_filter( 'sensei_question_answer_notes', array( $question_display, 'translate_answer_notes' ) ), 'The answer notes filter should be added.' );
	}

	public function testTranslateQuestionTitle_ViewerLanguageDiffersFromTakenQuestion_ShowsViewerLanguageTitle() {
		/* Arrange. */
		list( $taken_question_id, $display_question_id ) = $this->create_question_pair();
		$this->simulate_wpml_viewer_on_en( $taken_question_id, $display_question_id );

		$question_display = new Question_Display();
		$question_display->init();

		/* Act. */
		$title = get_the_title( $taken_question_id );

		/* Assert. */
		$this->assertSame( 'What color is the sky?', $title );
	}

	public function testTranslateQuestionTitle_InAdminContext_ReturnsTitleUnchanged() {
		/* Arrange. */
		list( $taken_question_id, $display_question_id ) = $this->create_question_pair();
		$this->simulate_wpml_viewer_on_en( $taken_question_id, $display_question_id );

		set_current_screen( 'edit-post' );

		$question_display = new Question_Display();
		$question_display->init();

		/* Act. */
		$title = get_the_title( $taken_question_id );

		/* Clean up. */
		unset( $GLOBALS['current_screen'] );

		/* Assert. */
		$this->assertSame( '¿De qué color es el cielo?', $title );
	}

	public function testTranslateQuestionDescriptionId_ViewerLanguageDiffersFromTakenQuestion_ShowsViewerLanguageDescription() {
		/* Arrange. */
		list( $taken_question_id, $display_question_id ) = $this->create_question_pair();

		wp_update_post(
			array(
				'ID'           => $taken_question_id,
				'post_content' => '<!-- wp:sensei-lms/question-description --><p>Descripción ES</p><!-- /wp:sensei-lms/question-description -->',
			)
		);
		wp_update_post(
			array(
				'ID'           => $display_question_id,
				'post_content' => '<!-- wp:sensei-lms/question-description --><p>EN description</p><!-- /wp:sensei-lms/question-description -->',
			)
		);

		$this->simulate_wpml_viewer_on_en( $taken_question_id, $display_question_id );

		$question_display = new Question_Display();
		$question_display->init();

		/* Act. */
		$description = \Sensei_Question::get_the_question_description( $taken_question_id );

		/* Assert. */
		$this->assertStringContainsString( 'EN description', $description );
	}

	public function testTranslateQuestionDescriptionId_InAdminContext_ReturnsQuestionIdUnchanged() {
		/* Arrange. */
		list( $taken_question_id, $display_question_id ) = $this->create_question_pair();
		$this->simulate_wpml_viewer_on_en( $taken_question_id, $display_question_id );

		set_current_screen( 'edit-post' );

		$question_display = new Question_Display();
		$question_display->init();

		/* Act. */
		$question_id = apply_filters( 'sensei_question_description_post_id', $taken_question_id );

		/* Clean up. */
		unset( $GLOBALS['current_screen'] );

		/* Assert. */
		$this->assertSame( $taken_question_id, $question_id, 'The grading screen should keep rendering the as-taken description.' );
	}

	public function testTranslateCorrectAnswerMessage_TranslationForMultipleChoiceExists_ShowsViewerLanguageRightAnswer() {
		/* Arrange. */
		list( $taken_question_id, $display_question_id ) = $this->create_question_pair();

		wp_set_object_terms( $taken_question_id, 'multiple-choice', 'question-type' );
		wp_set_object_terms( $display_question_id, 'multiple-choice', 'question-type' );

		$this->simulate_wpml_viewer_on_en( $taken_question_id, $display_question_id );

		$question_display = new Question_Display();
		$question_display->init();

		/* Act. */
		$message = apply_filters( 'sensei_question_answer_message_correct_answer', 'Azul', 0, $taken_question_id, 0, false );

		/* Assert. */
		$this->assertSame( 'Blue', $message );
	}

	public function testTranslateTemplateData_QuizNotCompleted_ReturnsQuestionDataUnchanged() {
		/* Arrange. */
		list( $question_data, $taken_question_id ) = $this->arrange_loader_output_with_translation();

		// The viewer's quiz is still open: the options are live form values.
		$question_data['quiz_is_completed'] = false;

		$question_display = new Question_Display();

		/* Act. */
		$actual = $question_display->translate_template_data( $question_data, $taken_question_id );

		/* Assert. */
		$this->assertSame( $question_data, $actual );
	}

	public function testTranslateTemplateData_AnswerListsSizesDiffer_ReturnsQuestionDataUnchanged() {
		/* Arrange. */
		list( $question_data, $taken_question_id ) = $this->arrange_loader_output_with_translation();

		update_post_meta( $taken_question_id, '_question_wrong_answers', array( 'Verde', 'Rojo' ) );

		$question_display = new Question_Display();

		/* Act. */
		$actual = $question_display->translate_template_data( $question_data, $taken_question_id );

		/* Assert. */
		$this->assertSame( $question_data, $actual, 'On an answer-list size mismatch the whole question should render as taken.' );
	}

	public function testTranslateTemplateData_TranslationForMultipleChoiceExists_KeepsAnswerListsShape() {
		/* Arrange. */
		list( $question_data, $taken_question_id ) = $this->arrange_loader_output_with_translation();

		$question_display = new Question_Display();

		/* Act. */
		$actual = $question_display->translate_template_data( $question_data, $taken_question_id );

		/* Assert. */
		$this->assertSame( array( 'Green', 'Blue' ), $actual['question_wrong_answers'], 'The loader-merged wrong list should be translated in place, keeping its shape.' );
	}

	public function testTranslateTemplateData_NoTranslationExists_ReturnsQuestionDataUnchanged() {
		/* Arrange. */
		list( $question_data, $taken_question_id ) = $this->arrange_loader_output_with_translation();

		remove_all_filters( 'wpml_object_id' );

		$question_display = new Question_Display();

		/* Act. */
		$actual = $question_display->translate_template_data( $question_data, $taken_question_id );

		/* Assert. */
		$this->assertSame( $question_data, $actual );
	}

	public function testTranslateAnswerNotes_NotesMatchTakenQuestionGenericFeedback_ShowsViewerLanguageFeedback() {
		/* Arrange. */
		list( $taken_question_id, $display_question_id ) = $this->create_question_pair();

		update_post_meta( $taken_question_id, '_answer_feedback', 'Feedback ES' );
		update_post_meta( $display_question_id, '_answer_feedback', 'Feedback EN' );

		$this->simulate_wpml_viewer_on_en( $taken_question_id, $display_question_id );

		$question_display = new Question_Display();
		$question_display->init();

		/* Act. */
		$notes = apply_filters( 'sensei_question_answer_notes', 'Feedback ES', $taken_question_id, 0 );

		/* Assert. */
		$this->assertSame( 'Feedback EN', $notes );
	}

	public function testTranslateAnswerNotes_NotesAreTeacherFeedback_ReturnsNotesUnchanged() {
		/* Arrange. */
		list( $taken_question_id, $display_question_id ) = $this->create_question_pair();

		update_post_meta( $taken_question_id, '_answer_feedback', 'Feedback ES' );
		update_post_meta( $display_question_id, '_answer_feedback', 'Feedback EN' );

		$this->simulate_wpml_viewer_on_en( $taken_question_id, $display_question_id );

		$question_display = new Question_Display();
		$question_display->init();

		/* Act. */
		$notes = apply_filters( 'sensei_question_answer_notes', 'Muy buen razonamiento, Ana.', $taken_question_id, 0 );

		/* Assert. */
		$this->assertSame( 'Muy buen razonamiento, Ana.', $notes, 'Per-student feedback written by the teacher should be shown as written.' );
	}

	public function testTranslateTemplateData_TranslationForMultipleChoiceExists_ShowsViewerLanguageOptionLabels() {
		/* Arrange. */
		list( $taken_question_id, $quiz_id ) = $this->arrange_real_taken_multiple_choice_with_translation();

		$question_display = new Question_Display();
		$question_display->init();

		/* Act. */
		$question_data = \Sensei_Question::get_template_data( $taken_question_id, $quiz_id );

		/* Assert. */
		$this->assertSame( array( 'Green', 'Blue' ), array_values( array_column( $question_data['answer_options'], 'answer' ) ) );
	}

	public function testTranslateTemplateData_TranslationForMultipleChoiceExists_KeepsStudentSelectionByPosition() {
		/* Arrange. */
		list( $question_data, $taken_question_id ) = $this->arrange_loader_output_with_translation();

		$question_display = new Question_Display();

		/* Act. */
		$actual = $question_display->translate_template_data( $question_data, $taken_question_id );

		/* Assert. */
		$checked_labels = array();
		foreach ( $actual['answer_options'] as $option ) {
			if ( '' !== $option['checked'] ) {
				$checked_labels[] = $option['answer'];
			}
		}
		$this->assertSame( array( 'Blue' ), $checked_labels, 'The option the student picked (Azul, second position) should stay checked as its translation (Blue).' );
	}

	public function testTranslateTemplateData_ViewerLanguageDiffersFromTakenQuestion_ShowsViewerLanguageTitle() {
		/* Arrange. */
		list( $question_data, $taken_question_id ) = $this->arrange_loader_output_with_translation();

		$question_display = new Question_Display();

		/* Act. */
		$actual = $question_display->translate_template_data( $question_data, $taken_question_id );

		/* Assert. */
		$this->assertSame( 'What color is the sky?', $actual['title'] );
	}

	/**
	 * Create a real ES multiple choice (taxonomy, metas, and answer order, so the
	 * type loader runs on it), an EN translation, and WPML simulated with the
	 * viewer on EN.
	 *
	 * The ES question shows "Verde" (wrong) then "Azul" (right); the EN translation has
	 * "Green"/"Blue" in the same buckets and positions.
	 *
	 * @return array{0: int, 1: int} As-taken question ID and quiz ID.
	 */
	private function arrange_real_taken_multiple_choice_with_translation() {
		list( $taken_question_id, $display_question_id ) = $this->create_question_pair();

		wp_set_object_terms( $taken_question_id, 'multiple-choice', 'question-type' );
		update_post_meta(
			$taken_question_id,
			'_answer_order',
			\Sensei()->lesson->get_answer_id( 'Verde' ) . ',' . \Sensei()->lesson->get_answer_id( 'Azul' )
		);

		$this->simulate_wpml_viewer_on_en( $taken_question_id, $display_question_id );

		$course_with_lessons = $this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 1,
				'question_count' => 1,
			)
		);
		$quiz_id             = $course_with_lessons['quiz_ids'][0];
		$this->complete_quiz_for_current_user( $quiz_id, $course_with_lessons['lesson_ids'][0] );

		return array( $taken_question_id, $quiz_id );
	}

	/**
	 * Mark the quiz as submitted (awaiting grading) for a fresh logged-in
	 * student, so the template data renders in review mode.
	 *
	 * @param int $quiz_id   Quiz ID.
	 * @param int $lesson_id Lesson the quiz belongs to.
	 */
	private function complete_quiz_for_current_user( $quiz_id, $lesson_id ) {
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );

		// The comments-based quiz progress piggybacks on the lesson progress.
		\Sensei()->lesson_progress_repository->create( $lesson_id, $user_id );

		$progress = \Sensei()->quiz_progress_repository->create( $quiz_id, $user_id );
		$progress->ungrade();
		\Sensei()->quiz_progress_repository->save( $progress );
	}

	/**
	 * Build the template data of an ES-taken multiple choice as the type loader
	 * leaves it (as-taken labels, selection and order already resolved), with an
	 * EN translation and WPML simulated with the viewer on EN.
	 *
	 * The student picked "Azul" (right answer, second position).
	 *
	 * @return array{0: array, 1: int} Question data and as-taken question ID.
	 */
	private function arrange_loader_output_with_translation() {
		list( $taken_question_id, $display_question_id ) = $this->create_question_pair();

		$this->simulate_wpml_viewer_on_en( $taken_question_id, $display_question_id );

		$question_data = array(
			'ID'                     => $taken_question_id,
			'title'                  => '¿De qué color es el cielo?',
			'content'                => '',
			'quiz_is_completed'      => true,
			'question_right_answer'  => 'Azul',
			// The type loader merges the right answer into the wrong list.
			'question_wrong_answers' => array( 'Verde', 'Azul' ),
			'user_answer_entry'      => 'Azul',
			'answer_options'         => array(
				md5( 'Verde' ) => array(
					'ID'      => md5( 'Verde' ),
					'answer'  => 'Verde',
					'checked' => '',
				),
				md5( 'Azul' )  => array(
					'ID'      => md5( 'Azul' ),
					'answer'  => 'Azul',
					'checked' => 'checked="checked"',
				),
			),
		);

		return array( $question_data, $taken_question_id );
	}

	/**
	 * Create the ES/EN question pair with their right/wrong answer metas.
	 *
	 * @return array{0: int, 1: int} As-taken (ES) and display (EN) question IDs.
	 */
	private function create_question_pair() {
		$taken_question_id   = $this->factory->post->create(
			array(
				'post_type'  => 'question',
				'post_title' => '¿De qué color es el cielo?',
			)
		);
		$display_question_id = $this->factory->post->create(
			array(
				'post_type'  => 'question',
				'post_title' => 'What color is the sky?',
			)
		);

		update_post_meta( $taken_question_id, '_question_right_answer', 'Azul' );
		update_post_meta( $taken_question_id, '_question_wrong_answers', array( 'Verde' ) );
		update_post_meta( $display_question_id, '_question_right_answer', 'Blue' );
		update_post_meta( $display_question_id, '_question_wrong_answers', array( 'Green' ) );

		return array( $taken_question_id, $display_question_id );
	}

	/**
	 * Register the WPML simulation filters: viewer on EN, ES question mapped to
	 * its EN translation.
	 *
	 * @param int $taken_question_id   As-taken (ES) question ID.
	 * @param int $display_question_id Display (EN) question ID.
	 */
	private function simulate_wpml_viewer_on_en( $taken_question_id, $display_question_id ) {
		add_filter(
			'wpml_current_language',
			function () {
				return 'en';
			},
			10,
			0
		);

		add_filter(
			'wpml_object_id',
			function ( $object_id, $type ) use ( $taken_question_id, $display_question_id ) {
				if ( 'question' === $type && $taken_question_id === $object_id ) {
					return $display_question_id;
				}
				return $object_id;
			},
			10,
			2
		);
	}
}

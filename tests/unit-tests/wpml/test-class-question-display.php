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

	public function testRenderQuestionDescription_ViewerLanguageDiffersFromTakenQuestion_ShowsViewerLanguageDescription() {
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

		// Minimal question loop state for the core title callback on the same action.
		global $sensei_question_loop;
		$sensei_question_loop = array(
			'current_page'     => 1,
			'posts_per_page'   => 1,
			'current'          => 0,
			'current_question' => get_post( $taken_question_id ),
			'quiz_id'          => $this->factory->quiz->create(),
		);

		$question_display = new Question_Display();
		$question_display->init();
		$question_display->replace_question_description_renderer();

		/* Act. */
		ob_start();
		do_action( 'sensei_quiz_question_inside_before', $taken_question_id );
		$html = ob_get_clean();

		/* Clean up. */
		$sensei_question_loop = null;

		/* Assert. */
		$this->assertStringContainsString( 'EN description', $html );
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
		list( $taken_question_id, $quiz_id ) = $this->arrange_taken_question_with_translation();

		$question_display = new Question_Display();
		$question_display->init();

		/* Act. */
		$question_data = \Sensei_Question::get_template_data( $taken_question_id, $quiz_id );

		/* Assert. */
		$this->assertSame( 'Is the sky blue?', $question_data['title'] );
	}

	/**
	 * Create a real ES multiple choice (taxonomy, metas, and answer order, so the
	 * type loader runs on it), an EN twin question, and WPML simulated with the
	 * viewer on EN.
	 *
	 * The ES question shows "Verde" (wrong) then "Azul" (right); the EN twin has
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

		return array( $taken_question_id, $this->factory->quiz->create() );
	}

	/**
	 * Build the template data of an ES-taken multiple choice as the type loader
	 * leaves it (as-taken labels, selection and order already resolved), with an
	 * EN twin question and WPML simulated with the viewer on EN.
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
	 * its EN twin.
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

	/**
	 * Create an EN course whose quiz question has an ES twin, simulate WPML with
	 * the viewer on EN, and return the as-taken (ES) question and the quiz.
	 *
	 * The scenario mirrors a student who took the quiz in ES and now views the
	 * results with the site in EN: the page loop passes the stored ES question
	 * to the template data builder.
	 *
	 * @return array{0: int, 1: int} As-taken question ID and quiz ID.
	 */
	private function arrange_taken_question_with_translation() {
		$course_with_lessons = $this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 1,
				'question_count' => 1,
			)
		);

		$quiz_id             = $course_with_lessons['quiz_ids'][0];
		$display_question_id = \Sensei()->quiz->get_questions( $quiz_id )[0]->ID;
		wp_update_post(
			array(
				'ID'         => $display_question_id,
				'post_title' => 'Is the sky blue?',
			)
		);

		$taken_question_id = $this->factory->post->create(
			array(
				'post_type'  => 'question',
				'post_title' => '¿El cielo es azul?',
			)
		);

		$this->simulate_wpml_viewer_on_en( $taken_question_id, $display_question_id );

		return array( $taken_question_id, $quiz_id );
	}
}

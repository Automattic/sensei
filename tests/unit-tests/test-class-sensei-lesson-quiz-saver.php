<?php
/**
 * Tests for Sensei_Lesson_Quiz_Saver.
 *
 * @covers Sensei_Lesson_Quiz_Saver
 */
class Sensei_Lesson_Quiz_Saver_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	/**
	 * Sensei post factory.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	public function tearDown(): void {
		$this->factory->tearDown();

		parent::tearDown();
	}

	/**
	 * Count the question posts of a type.
	 *
	 * @param string $post_type Post type.
	 */
	private function countPosts( string $post_type ): int {
		return count(
			get_posts(
				array(
					'post_type'   => $post_type,
					'post_status' => 'any',
					'numberposts' => -1,
					'fields'      => 'ids',
				)
			)
		);
	}

	/**
	 * A lesson without a quiz gets one, with its term, meta and questions in order.
	 *
	 * @covers Sensei_Lesson_Quiz_Saver::save
	 */
	public function testSave_LessonWithoutQuizGiven_CreatesQuizWithTermMetaAndQuestions() {
		$this->login_as_teacher();
		$lesson_id = $this->factory->lesson->create( array( 'post_author' => get_current_user_id() ) );
		$saver     = new Sensei_Lesson_Quiz_Saver();

		$quiz_id = $saver->save(
			get_post( $lesson_id ),
			array(
				'options'   => array(
					'pass_required' => true,
					'quiz_passmark' => 80,
				),
				'questions' => array(
					array(
						'type'   => 'boolean',
						'title'  => 'A power chord has a third.',
						'answer' => array( 'correct' => false ),
					),
					array(
						'type'  => 'multi-line',
						'title' => 'Describe your project.',
					),
				),
			)
		);

		$this->assertIsInt( $quiz_id, 'The quiz id should be returned.' );
		$this->assertSame( 'quiz', get_post_type( $quiz_id ) );
		$this->assertSame( $lesson_id, get_post( $quiz_id )->post_parent, 'The quiz should belong to the lesson.' );
		$this->assertSame( $quiz_id, (int) get_post_meta( $lesson_id, '_lesson_quiz', true ), 'The lesson should point at the quiz.' );
		$this->assertSame( $lesson_id, (int) get_post_meta( $quiz_id, '_quiz_lesson', true ), 'The quiz should point at the lesson.' );
		$this->assertTrue( has_term( 'multiple-choice', 'quiz-type', $quiz_id ), 'The quiz type term should be set.' );
		$this->assertSame( 'on', get_post_meta( $quiz_id, '_pass_required', true ) );
		$this->assertSame( '80', get_post_meta( $quiz_id, '_quiz_passmark', true ) );

		$questions = Sensei()->quiz->get_questions( $quiz_id );
		$this->assertSame( array( 'A power chord has a third.', 'Describe your project.' ), wp_list_pluck( $questions, 'post_title' ), 'The questions should be saved in order.' );
		$this->assertSame( array( 'publish', 'publish' ), wp_list_pluck( $questions, 'post_status' ), 'Questions are published, as in the editor.' );
	}

	/**
	 * A question without a title is rejected before the quiz or any question is created.
	 *
	 * @covers Sensei_Lesson_Quiz_Saver::save
	 */
	public function testSave_QuestionWithoutTitleGiven_ReturnsErrorBeforeWriting() {
		$this->login_as_teacher();
		$lesson_id = $this->factory->lesson->create( array( 'post_author' => get_current_user_id() ) );
		$saver     = new Sensei_Lesson_Quiz_Saver();

		$result = $saver->save(
			get_post( $lesson_id ),
			array(
				'options'   => array(),
				'questions' => array(
					array(
						'type'  => 'multi-line',
						'title' => 'Fine',
					),
					array(
						'type'  => 'multi-line',
						'title' => '',
					),
				),
			)
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'sensei_lesson_quiz_question_missing_title', $result->get_error_code() );
		$this->assertNull( Sensei()->lesson->lesson_quizzes( $lesson_id ), 'No quiz should be created.' );
		$this->assertSame( 0, $this->countPosts( 'question' ), 'No question should be created.' );
	}

	/**
	 * A category question with an unknown category is rejected before anything is written.
	 *
	 * @covers Sensei_Lesson_Quiz_Saver::save
	 */
	public function testSave_InvalidCategoryGiven_ReturnsErrorBeforeWriting() {
		$this->login_as_teacher();
		$lesson_id = $this->factory->lesson->create( array( 'post_author' => get_current_user_id() ) );
		$saver     = new Sensei_Lesson_Quiz_Saver();

		$result = $saver->save(
			get_post( $lesson_id ),
			array(
				'options'   => array(),
				'questions' => array(
					array(
						'type'  => 'multi-line',
						'title' => 'Fine',
					),
					array(
						'type'    => 'category-question',
						'options' => array(
							'category' => 999999,
							'number'   => 2,
						),
					),
				),
			)
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'sensei_lesson_quiz_question_invalid_category', $result->get_error_code() );
		$this->assertNull( Sensei()->lesson->lesson_quizzes( $lesson_id ), 'No quiz should be created.' );
		$this->assertSame( 0, $this->countPosts( 'question' ), 'No question should be created.' );
		$this->assertSame( 0, $this->countPosts( 'multiple_question' ), 'No category question should be created.' );
	}

	/**
	 * Auto-draft lessons have no quiz to save.
	 *
	 * @covers Sensei_Lesson_Quiz_Saver::save
	 */
	public function testSave_AutoDraftLessonGiven_ReturnsError() {
		$this->login_as_admin();
		$lesson_id = $this->factory->lesson->create( array( 'post_status' => 'auto-draft' ) );

		$result = ( new Sensei_Lesson_Quiz_Saver() )->save(
			get_post( $lesson_id ),
			array(
				'options'   => array(),
				'questions' => array(),
			)
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'sensei_lesson_quiz_lesson_auto_draft', $result->get_error_code() );
		$this->assertEmpty( $result->get_error_data(), 'The error should carry no HTTP status; callers add their own.' );
	}

	/**
	 * A question id the user cannot edit is dropped rather than failing the save.
	 *
	 * @covers Sensei_Lesson_Quiz_Saver::save
	 */
	public function testSave_AnotherTeachersQuestionIdGiven_DropsIt() {
		$this->login_as_teacher();
		$other_question_id = $this->factory->question->create( array( 'question_type' => 'multi-line' ) );

		$this->login_as_teacher_b();
		$lesson_id = $this->factory->lesson->create( array( 'post_author' => get_current_user_id() ) );
		$saver     = new Sensei_Lesson_Quiz_Saver();

		$quiz_id = $saver->save(
			get_post( $lesson_id ),
			array(
				'options'   => array(),
				'questions' => array(
					array(
						'id'    => $other_question_id,
						'type'  => 'multi-line',
						'title' => 'Hijacked',
					),
					array(
						'type'  => 'multi-line',
						'title' => 'Mine',
					),
				),
			)
		);

		$this->assertIsInt( $quiz_id );
		$this->assertSame( array( 'Mine' ), wp_list_pluck( Sensei()->quiz->get_questions( $quiz_id ), 'post_title' ), "Another teacher's question should not be in the quiz." );
		$this->assertNotSame( 'Hijacked', get_post( $other_question_id )->post_title, "Another teacher's question should be untouched." );
	}

	/**
	 * Backslashes in question titles survive the save.
	 *
	 * @covers Sensei_Lesson_Quiz_Saver::save
	 */
	public function testSave_BackslashesGiven_KeepsBackslashes() {
		$this->login_as_admin();
		$lesson_id = $this->factory->lesson->create();
		$saver     = new Sensei_Lesson_Quiz_Saver();

		$quiz_id   = $saver->save(
			get_post( $lesson_id ),
			array(
				'options'   => array(),
				'questions' => array(
					array(
						'type'        => 'multi-line',
						'title'       => 'What does \\d match?',
						'description' => 'Think of C:\\temp',
					),
				),
			)
		);
		$questions = Sensei()->quiz->get_questions( $quiz_id );

		$this->assertSame( 'What does \\d match?', $questions[0]->post_title );
		$this->assertSame( 'Think of C:\\temp', $questions[0]->post_content );
	}

	/**
	 * Options not sent keep their stored value, and unset tri-state options stay unset.
	 *
	 * @covers Sensei_Lesson_Quiz_Saver::get_options_for_update
	 */
	public function testGetOptionsForUpdate_ExistingQuizGiven_KeepsUnsentOptions() {
		$this->login_as_admin();
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->quiz->create(
			array(
				'post_parent' => $lesson_id,
				'meta_input'  => array(
					'_pass_required'             => 'on',
					'_quiz_passmark'             => 42,
					'_failed_indicate_incorrect' => 'yes',
				),
			)
		);

		$options = ( new Sensei_Lesson_Quiz_Saver() )->get_options_for_update( get_post( $quiz_id ), array( 'auto_grade' => false ) );

		$this->assertFalse( $options['auto_grade'], 'The sent option should be applied.' );
		$this->assertTrue( $options['pass_required'], 'A stored option should be kept.' );
		$this->assertSame( 42, $options['quiz_passmark'], 'A stored option should be kept.' );
		$this->assertTrue( $options['failed_indicate_incorrect'], 'A set tri-state option should be kept.' );
		$this->assertNull( $options['failed_show_correct_answers'], 'An unset tri-state option should stay unset.' );
		$this->assertNull( $options['failed_show_answer_feedback'], 'An unset tri-state option should stay unset.' );
		$this->assertSame( 12, $options['pagination']['progress_bar_height'], 'Pagination should be filled in.' );
	}

	/**
	 * A lesson without a quiz starts from the schema defaults.
	 *
	 * @covers Sensei_Lesson_Quiz_Saver::get_options_for_update
	 * @covers Sensei_Lesson_Quiz_Saver::get_default_options
	 */
	public function testGetOptionsForUpdate_NoQuizGiven_UsesSchemaDefaults() {
		$options = ( new Sensei_Lesson_Quiz_Saver() )->get_options_for_update( null, array( 'pagination' => array( 'pagination_number' => 2 ) ) );

		$this->assertSame( 100, $options['quiz_passmark'] );
		$this->assertTrue( $options['auto_grade'] );
		$this->assertTrue( $options['allow_retakes'] );
		$this->assertFalse( $options['pass_required'] );
		$this->assertNull( $options['failed_indicate_incorrect'] );
		$this->assertSame( 2, $options['pagination']['pagination_number'], 'The sent pagination option should be applied.' );
		$this->assertFalse( $options['pagination']['show_progress_bar'], 'Other pagination options should take their defaults.' );
	}

	/**
	 * Saving with options from get_options_for_update() leaves the stored options as they were.
	 *
	 * @covers Sensei_Lesson_Quiz_Saver::save
	 * @covers Sensei_Lesson_Quiz_Saver::get_options_for_update
	 */
	public function testSave_OptionsForUpdateGiven_LeavesStoredOptionsUnchanged() {
		$this->login_as_admin();
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->quiz->create(
			array(
				'post_parent' => $lesson_id,
				'meta_input'  => array(
					'_quiz_passmark'     => 42,
					'_enable_quiz_reset' => 'on',
					'_button_text_color' => '#fff',
				),
			)
		);
		$saver     = new Sensei_Lesson_Quiz_Saver();

		$saver->save(
			get_post( $lesson_id ),
			array(
				'options'   => $saver->get_options_for_update( get_post( $quiz_id ), array( 'random_question_order' => true ) ),
				'questions' => array(),
			)
		);

		$this->assertSame( 'yes', get_post_meta( $quiz_id, '_random_question_order', true ) );
		$this->assertSame( '42', get_post_meta( $quiz_id, '_quiz_passmark', true ) );
		$this->assertSame( 'on', get_post_meta( $quiz_id, '_enable_quiz_reset', true ) );
		$this->assertSame( '#fff', get_post_meta( $quiz_id, '_button_text_color', true ) );
		$this->assertSame( '', get_post_meta( $quiz_id, '_failed_show_correct_answers', true ), 'An unset tri-state option should stay unset.' );
	}

	/**
	 * Teachers may save their own lessons' quizzes; others, pages and guests are refused.
	 *
	 * @covers Sensei_Lesson_Quiz_Saver::can_current_user_save
	 */
	public function testCanCurrentUserSave_RoleAndLessonGiven_MatchesEditLesson() {
		$this->login_as_teacher();
		$lesson_id = $this->factory->lesson->create( array( 'post_author' => get_current_user_id() ) );
		$page_id   = $this->factory->post->create( array( 'post_type' => 'page' ) );
		$this->assertTrue( Sensei_Lesson_Quiz_Saver::can_current_user_save( $lesson_id ), 'The owner should be allowed.' );
		$this->assertFalse( Sensei_Lesson_Quiz_Saver::can_current_user_save( $page_id ), 'A page is not a lesson.' );
		$this->assertFalse( Sensei_Lesson_Quiz_Saver::can_current_user_save( 0 ), 'A missing id is refused.' );

		$this->login_as_teacher_b();
		$this->assertFalse( Sensei_Lesson_Quiz_Saver::can_current_user_save( $lesson_id ), 'Another teacher should be refused.' );

		$this->login_as_admin();
		$this->assertTrue( Sensei_Lesson_Quiz_Saver::can_current_user_save( $lesson_id ), 'An administrator should be allowed.' );

		$this->logout();
		$this->assertFalse( Sensei_Lesson_Quiz_Saver::can_current_user_save( $lesson_id ), 'A guest should be refused.' );
	}
}

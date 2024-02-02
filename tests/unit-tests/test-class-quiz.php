<?php

use Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress_Interface;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Interface;
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress_Interface;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Interface;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Tables_Based_Quiz_Progress_Repository;

class Sensei_Class_Quiz_Test extends WP_UnitTestCase {

	use Sensei_Test_Redirect_Helpers;

	/**
	 * @var $factory
	 */
	protected $factory;

	/**
	 * Constructor function.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Setup function.
	 *
	 * This function sets up the lessons, quizes and their questions. This function runs before
	 * every single test in this class.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();

		// Override the default upload to ensure file upload tests pass.
		add_filter( 'sensei_file_upload_args', 'testSaveUserAnswers_override_file_upload_args' );
		if ( ! function_exists( 'testSaveUserAnswers_override_file_upload_args' ) ) {
			function testSaveUserAnswers_override_file_upload_args( $args ) {
				$args['action'] = 'custom_testing_upload_function';
				return $args;
			}
		}

	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();

		// Remove all lessons.
		$lessons = get_posts( 'post_type=lesson' );
		foreach ( $lessons as $index => $lesson ) {
			wp_delete_post( $lesson->ID, true );
		}

		// Remove all quizzes.
		$quizzes = get_posts( 'post_type=quiz' );
		foreach ( $quizzes as $index => $quiz ) {
			wp_delete_post( $quiz->ID, true );
		}
		WP_Block_Supports::$block_to_render = null;
	}

	/**
	 * Testing the quiz class to make sure it is loaded.
	 */
	public function testClassInstance() {
		// Setup the test.
		// Test if the global sensei quiz class is loaded.
		$this->assertTrue( isset( Sensei()->quiz ), 'Sensei quiz class is not loaded' );

	}
	/**
	 * Testing Woothemes_Sensei()->quiz->get_questions.
	 */
	public function testGetQuestionsFiltersIncompleteQuestionsEmptyValues() {
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->maybe_create_quiz_for_lesson( $lesson_id );

		$this->factory->question->create_many(
			3,
			[
				'quiz_id'                => $quiz_id,
				'question_type'          => 'multiple-choice',
				'question_right_answers' => [],
				'question_wrong_answers' => [],
			]
		);
		$this->factory->question->create_many(
			3,
			[
				'quiz_id'                                => $quiz_id,
				'question_type'                          => 'gap-fill',
				'add_question_right_answer_gapfill_pre'  => '',
				'add_question_right_answer_gapfill_gap'  => '',
				'add_question_right_answer_gapfill_post' => '',
			]
		);
		$questions_filtered     = Sensei()->quiz->get_questions( $quiz_id, 'any', 'meta_value_num title', 'ASC', true );
		$questions_not_filtered = Sensei()->quiz->get_questions( $quiz_id );
		$this->assertCount( 6, $questions_not_filtered );
		$this->assertCount( 0, $questions_filtered );
	}

	/**
	 * Testing Woothemes_Sensei()->quiz->get_questions.
	 */
	public function testGetQuestionsFiltersIncompleteQuestionsWhitespaceValues() {
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->maybe_create_quiz_for_lesson( $lesson_id );

		$this->factory->question->create_many(
			3,
			[
				'quiz_id'                => $quiz_id,
				'question_type'          => 'multiple-choice',
				'question_right_answers' => [ ' ', ' ', ' ' ],
				'question_wrong_answers' => [ ' ' ],
			]
		);
		$this->factory->question->create_many(
			3,
			[
				'quiz_id'                                => $quiz_id,
				'question_type'                          => 'gap-fill',
				'add_question_right_answer_gapfill_pre'  => ' ',
				'add_question_right_answer_gapfill_gap'  => ' ',
				'add_question_right_answer_gapfill_post' => ' ',
			]
		);
		$questions_filtered     = Sensei()->quiz->get_questions( $quiz_id, 'any', 'meta_value_num title', 'ASC', true );
		$questions_not_filtered = Sensei()->quiz->get_questions( $quiz_id );
		$this->assertCount( 6, $questions_not_filtered );
		$this->assertCount( 0, $questions_filtered );
	}

	/**
	 * Testing Woothemes_Sensei()->quiz->get_questions.
	 */
	public function testGetQuestionsFiltersIncompleteQuestionsMultipleChoice() {
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->maybe_create_quiz_for_lesson( $lesson_id );

		$this->factory->question->create_many(
			2,
			[
				'quiz_id'                => $quiz_id,
				'question_type'          => 'multiple-choice',
				'question_right_answers' => [],
				'question_wrong_answers' => [],
			]
		);
		$this->factory->question->create_many(
			2,
			[
				'quiz_id'       => $quiz_id,
				'question_type' => 'multiple-choice',
			]
		);
		$questions_filtered     = Sensei()->quiz->get_questions( $quiz_id, 'any', 'meta_value_num title', 'ASC', true );
		$questions_not_filtered = Sensei()->quiz->get_questions( $quiz_id );
		$this->assertCount( 4, $questions_not_filtered );
		$this->assertCount( 2, $questions_filtered );
	}

	/**
	 * Testing Woothemes_Sensei()->quiz->get_questions.
	 */
	public function testGetQuestionsFiltersIncompleteQuestionsFillGap() {
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->maybe_create_quiz_for_lesson( $lesson_id );
		$this->factory->question->create_many(
			2,
			[
				'quiz_id'                                => $quiz_id,
				'question_type'                          => 'gap-fill',
				'add_question_right_answer_gapfill_pre'  => '',
				'add_question_right_answer_gapfill_gap'  => '',
				'add_question_right_answer_gapfill_post' => '',
			]
		);
		$this->factory->question->create_many(
			2,
			[
				'quiz_id'       => $quiz_id,
				'question_type' => 'gap-fill',
			]
		);

		$questions_filtered     = Sensei()->quiz->get_questions( $quiz_id, 'any', 'meta_value_num title', 'ASC', true );
		$questions_not_filtered = Sensei()->quiz->get_questions( $quiz_id );
		$this->assertCount( 4, $questions_not_filtered );
		$this->assertCount( 2, $questions_filtered );
	}

	/**
	 * This test Woothemes_Sensei()->quiz->save_user_answers.
	 */
	public function testSaveUserAnswers() {

		// Setup the data and objects needed for this test.
		$test_user_id   = wp_create_user( 'student', 'student', 'student@test.com' );
		$test_lesson_id = $this->factory->get_random_lesson_id();
		$test_quiz_id   = Sensei()->lesson->lesson_quizzes( $test_lesson_id );

		// Does the save_user_answers function exist?
		$this->assertTrue(
			method_exists( Sensei()->quiz, 'save_user_answers' ),
			'The quiz class function `save_user_answers` does not exist '
		);

		// Does this save_user_answers return false for bogus data.
		$this->assertFalse( Sensei()->quiz->save_user_answers( array(), array(), -1000, -200 ), 'save_user_answers does not return false for no existent users and lesson ' );
		$this->assertFalse( Sensei()->quiz->save_user_answers( '', array(), '', '' ), 'save_user_answers does not return false for empty parameters' );

		// Does the function return the correct information when a user doesn't exist?
		$this->assertFalse( Sensei()->quiz->save_user_answers( '', array(), '', $test_lesson_id ), 'save_user_answers does not return false for empty user' );
		$this->assertFalse( Sensei()->quiz->save_user_answers( '', array(), -500, $test_lesson_id ), 'save_user_answers does not return false for a non existant user' );

		// Test the answers_array parameter.
		$this->assertFalse( Sensei()->quiz->save_user_answers( 'Answers Text', array(), $test_lesson_id, $test_user_id ), 'save_user_answers does not return false if answers is not passed in as an array' );
		$this->assertFalse( Sensei()->quiz->save_user_answers( '', array(), $test_lesson_id, $test_user_id ), 'save_user_answers does not return false for empty answer array' );
		$this->assertFalse( Sensei()->quiz->save_user_answers( '', array(), '', '' ), 'save_user_answers does not return false incorrectly formatted answers' );

		// Test a case that is setup correctly which should return a positive result.
		$test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
		Sensei_Utils::sensei_start_lesson( $test_lesson_id, $test_user_id );
		$files             = $this->factory->generate_test_files( $test_user_quiz_answers );
		$lesson_data_saved = Sensei()->quiz->save_user_answers( $test_user_quiz_answers, $files, $test_lesson_id, $test_user_id );

		// Did the correct data return a valid comment id on the lesson as a result?
		$this->assertTrue( intval( $lesson_data_saved ) > 0, 'The comment id returned after saving the quiz answer does not represent a valid comment ' );

		// Setup for the next group of assertions.
		$sensei_activity_logged = Sensei_Utils::sensei_check_for_activity(
			array(
				'post_id' => $test_lesson_id,
				'user_id' => $test_user_id,
			)
		);
		$status_comment         = Sensei_Utils::user_lesson_status( $test_lesson_id, $test_user_id );
		$saved_data             = get_comment_meta( $status_comment->comment_ID, 'quiz_answers', true );

		// Was the data that was just stored stored correctly ? Check the comment meta on the lesson id.
		$this->assertTrue( (bool) $sensei_activity_logged, 'The saved answers were not stored correctly on the Quiz' );
		$this->assertFalse( empty( $saved_data ), 'The saved answers were not stored correctly on the Quiz' );
		$this->assertTrue( is_array( maybe_unserialize( $saved_data ) ), 'The saved answers were not stored correctly on the Quiz' );

		// Can you retrieve data and is it the same as what was stored?
		// Compare every single answer.
		$retrieved_saved_array = maybe_unserialize( $saved_data );

		foreach ( $test_user_quiz_answers as $question_id => $answer ) {

			$type = Sensei()->question->get_question_type( $question_id );
			// If file skip it because  files going in comes out as attachment ids.
			if ( 'file-upload' == $type ) {
				continue;
			}
			$saved_single_answer = $retrieved_saved_array[ $question_id ];
			$assert_message      = 'The saved answer of type "' . strtoupper( $type )
								. '" does not correspond to what was passed into the function';
			$this->assertEquals(
				$answer,
				maybe_unserialize( base64_decode( $saved_single_answer ) ),
				$assert_message
			);

		}

		// Was the files submitted uploaded and saved correctly?
		if ( isset( $files ) && ! empty( $files ) ) {
			$file_keys = array_keys( $files );
			foreach ( $file_keys as $key ) {

				$question_id   = str_replace( 'file_upload_', '', $key );
				$attachment_id = base64_decode( $retrieved_saved_array[ $question_id ] );
				// Make sure this is an attachment.
				$image_location = get_attached_file( $attachment_id, false );
				$this->assertFalse( empty( $image_location ), 'The ' . $files[ $key ]['name'] . ' image was not attached' );

			}
		}

	}

	/**
	 * This test Woothemes_Sensei()->quiz->save_user_answers.
	 *
	 * The test confirms that a user can have unique answers for the same question in different lesson quizzes.
	 *
	 * @ticket 618 ( GitHub issue)
	 */
	public function testSaveUserAnswersUniquelyPerQuiz() {

		// Setup data for the tests assertions.
		$test_user_id = wp_create_user( 'UniquelyPerQuiz', 'UniquelyPerQuiz', 'UniquelyPerQuiz@test-unique.com' );
		$test_lessons = $this->factory->get_random_lesson_id( 3 );
		$lesson_1     = $test_lessons[0];
		$lesson_2     = $test_lessons[1];
		$lesson_3     = $test_lessons[2];

		$lesson_1_quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_1 );
		$lesson_2_quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_2 );
		$lesson_3_quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_3 );

		$test_question_data = array(
			'question_type'                        => 'single-line',
			'question_category'                    => 'undefined',
			'action'                               => 'add',
			'question'                             => 'Is this a sample' . 'single-line' . ' question ? _ ' . rand(),
			'question_grade'                       => '1',
			'answer_feedback'                      => 'Answer Feedback sample ' . rand(),
			'question_description'                 => ' Basic description for the question',
			'question_media'                       => '',
			'answer_order'                         => '',
			'random_order'                         => 'yes',
			'question_count'                       => 1,
			'add_question_right_answer_singleline' => '',
			'quiz_id'                              => $lesson_1_quiz_id,
			'post_author'                          => 1,
		);

		// Add question to the the first quiz.
		$test_question_id = Sensei()->lesson->lesson_save_question( $test_question_data );

		// Add question to quiz 2.
		add_post_meta( $test_question_id, '_quiz_id', $lesson_2_quiz_id, false );
		$question_order = $lesson_2_quiz_id . '0001';
		add_post_meta( $test_question_id, '_quiz_question_order' . $lesson_2_quiz_id, $question_order );

		// Add question to quiz 3.
		add_post_meta( $test_question_id, '_quiz_id', $lesson_3_quiz_id, false );
		$question_order = $lesson_3_quiz_id . '0001';
		add_post_meta( $test_question_id, '_quiz_question_order' . $lesson_3_quiz_id, $question_order );

		// Create sample answer array and save it on each lesson.
		foreach ( $test_lessons as $lesson_id ) {
			$answers = array( $test_question_id => 'Sample Answer for lesson ' . $lesson_id );
			Sensei()->quiz->save_user_answers( $answers, array(), $lesson_id, $test_user_id );
		}

		// Check if the answers are not the same.
		$answer_from_lesson_1 = Sensei()->quiz->get_user_question_answer( $lesson_1, $test_question_id, $test_user_id );
		$answer_from_lesson_2 = Sensei()->quiz->get_user_question_answer( $lesson_2, $test_question_id, $test_user_id );
		$answer_from_lesson_3 = Sensei()->quiz->get_user_question_answer( $lesson_3, $test_question_id, $test_user_id );

		$answers_the_same = $answer_from_lesson_1 == $answer_from_lesson_2
							&& $answer_from_lesson_2 == $answer_from_lesson_3
							&& $answer_from_lesson_1 == $answer_from_lesson_3;

		$this->assertFalse( $answers_the_same, 'The unique answer saved by one user for the same question on different lessons was not really saved uniquely.' );

	}

	/**
	 * This test is for Woothemes_Sensei()->quiz->save_user_answers. We check the transients only.
	 *
	 * @group transient
	 */
	public function testSaveUserAnswersTransients() {

		// Setup the data and objects needed for this test.
		$test_user_id   = wp_create_user( 'studentTransients', 'transients', 'transients@test.com' );
		$test_lesson_id = $this->factory->get_random_lesson_id();
		$test_quiz_id   = Sensei()->lesson->lesson_quizzes( $test_lesson_id );

		// Generate and save the test data.
		$test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
		Sensei_Utils::sensei_start_lesson( $test_lesson_id, $test_user_id );
		$files = $this->factory->generate_test_files( $test_user_quiz_answers );
		Sensei()->quiz->save_user_answers( $test_user_quiz_answers, $files, $test_lesson_id, $test_user_id );
		$users_retrieved_answers = Sensei()->quiz->get_user_answers( $test_lesson_id, $test_user_id );

		// Was it saved correctly?
		$transient_key         = 'sensei_answers_' . $test_user_id . '_' . $test_lesson_id;
		$transient_val         = get_transient( $transient_key );
		$decoded_transient_val = array();
		if ( is_array( $transient_val ) ) {
			foreach ( $transient_val as $question_id => $encoded_answer ) {
				$decoded_transient_val[ $question_id ] = maybe_unserialize( base64_decode( $encoded_answer ) );
			}
		}

		$this->assertFalse( empty( $transient_val ), 'Transients are not saved correctly for user answers ' );
		$this->assertEquals(
			$users_retrieved_answers,
			$decoded_transient_val,
			'The transient should be the same as the prepared answer which was base64 encoded'
		);

		// If saved again will the transient be updated.
		$old_transient_value = $decoded_transient_val;
		$new_answers         = $this->factory->generate_user_quiz_answers( $test_quiz_id );
		$new_files           = $this->factory->generate_test_files( $test_user_quiz_answers );
		Sensei()->quiz->save_user_answers( $new_answers, $new_files, $test_lesson_id, $test_user_id );
		$new_users_retrieved_answers = Sensei()->quiz->get_user_answers( $test_lesson_id, $test_user_id );

		$this->assertNotEquals(
			$old_transient_value,
			$new_users_retrieved_answers,
			'Transient not updated on new save for the same user lesson combination'
		);

	}


	public function testGeCorrectAnswerCorrectFeedbacBlock_ReturnBlock() {
		$quiz = $this->factory->quiz->create_and_get(
			[
				'post_content' => '<!-- wp:sensei-lms/quiz-question-feedback-correct {"className":"my-feedback success"} -->
			<!-- /wp:sensei-lms/quiz-question-feedback-correct -->',
			]
		);

		$expected = 'sensei-lms/quiz-question-feedback-correct';
		$actual   = Sensei_Quiz::get_correct_answer_feedback_block( $quiz->ID );

		$this->assertEquals( $actual['blockName'], $expected );

	}


	public function testGeCorrectAnswerIncorrectFeedbacBlock_ReturnBlock() {
		$quiz = $this->factory->quiz->create_and_get(
			[
				'post_content' => '<!-- wp:sensei-lms/quiz-question-feedback-incorrect {"className":"my-feedback success"} -->
			<!-- /wp:sensei-lms/quiz-question-feedback-incorrect -->',
			]
		);

		$expected = 'sensei-lms/quiz-question-feedback-incorrect';
		$actual   = Sensei_Quiz::get_incorrect_answer_feedback_block( $quiz->ID );

		$this->assertEquals( $actual['blockName'], $expected );
	}

	/**
	 * This test Woothemes_Sensei()->quiz->get_user_answers transients only.
	 *
	 * @group transient
	 */
	function testGetUserAnswersTransient() {

		// Setup the test data.
		$test_user_id               = wp_create_user( 'studentTransientsGet', 'transientsGet', 'transientsGet@test.com' );
		$test_lesson_id             = $this->factory->get_random_lesson_id();
		$transient_key              = 'sensei_answers_' . $test_user_id . '_' . $test_lesson_id;
		$transient_get_test         = array( base64_encode( 'transientGetTest' ) );
		$transient_get_test_decoded = array( 'transientGetTest' );
		set_transient( $transient_key, $transient_get_test, 10 * DAY_IN_SECONDS );
		$users_retrieved_answers = Sensei()->quiz->get_user_answers( $test_lesson_id, $test_user_id );

		// Test if the answer is taken from the transient.
		$this->assertEquals(
			$transient_get_test_decoded,
			$users_retrieved_answers,
			'The transient was not used before proceeding to get the users answers from DB'
		);

		// Setup next assertion.
		$test_quiz_id           = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
		$test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
		Sensei_Utils::sensei_start_lesson( $test_lesson_id, $test_user_id );
		$files = $this->factory->generate_test_files( $test_user_quiz_answers );
		Sensei()->quiz->save_user_answers( $test_user_quiz_answers, $files, $test_lesson_id, $test_user_id );
		delete_transient( $transient_key );
		Sensei()->quiz->get_user_answers( $test_lesson_id, $test_user_id );
		$transient_data_after_retrieval = get_transient( $transient_key );

		// Test if a transient is created when one does not exist.
		// In this test we first delete the transient after it is been added in the save_user_answers
		// function above, then we get the data again and test if the function added the transient.
		$this->assertNotFalse(
			$transient_data_after_retrieval,
			' The get_user_answers function does not set the transient after retrieving the data '
		);

		// Make sure the one of the keys passed in is in the transient.
		$random_key = array_rand( $test_user_quiz_answers );
		$this->assertArrayHasKey(
			$random_key,
			$transient_data_after_retrieval,
			'The transient does not contain the same elements that we passed in'
		);

		// Make sure the number of elements passes in is the same as what is in the new transient cache.
		$this->assertEquals(
			count( $test_user_quiz_answers ),
			count( $transient_data_after_retrieval ),
			'The number of elements in the transient does not match those the user submitted'
		);
	}

	/**
	 * This test Woothemes_Sensei()->lesson->lesson_quizzes( $lesson_id )
	 */
	public function testGetQuizId() {

		// Save the user answers.
		$this->assertTrue(
			method_exists( Sensei()->lesson, 'lesson_quizzes' ),
			'The lesson class function `lesson_quizzes` does not exist '
		);

		// Test with an invalid lesson id.
		$invalid_lesson_quiz_id = Sensei()->lesson->lesson_quizzes( -13333 );
		$this->assertTrue( 0 == $invalid_lesson_quiz_id, 'Get quiz id does not return Zero for an invalid lesson ID' );

		// Test with a valid lesson that has a quiz.
		// The get_random_lesson_id gets a lesson that is already setup with quizzes and questions.
		$valid_lesson_quiz_id = Sensei()->lesson->lesson_quizzes( $this->factory->get_random_lesson_id() );
		$this->assertTrue( $valid_lesson_quiz_id > 0, 'Get quiz id should return a valid quiz id if a valid  lesson ID is passed in' );

	}

	/**
	 * This test Woothemes_Sensei()->quiz->get_user_answers.
	 */
	function testGetUserAnswers() {

		// Make sure the function exists.
		$this->assertTrue(
			method_exists( Sensei()->quiz, 'get_user_answers' ),
			'The quiz class function `get_user_answers` does not exist '
		);

		// Create a user.
		$test_user_id = wp_create_user( 'StudentTest', 'samplestudent', 'samplestudent@test.com' );
		$this->assertTrue(
			intval( $test_user_id ) > 0 && get_userdata( $test_user_id ),
			'WordPress did not give us a valid user id.'
		);

		// Get a lesson and assign the user to the lesson.
		$test_lesson_id = $this->factory->get_random_lesson_id();
		$this->assertTrue(
			intval( $test_lesson_id ) > 0 && 'lesson' == get_post_type( $test_lesson_id ),
			'The random lesson id need for the rest of this test is not a valid lesson'
		);

		// Get the lessons quiz.
		$test_lesson_quiz_id = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
		$this->assertTrue(
			intval( $test_lesson_quiz_id ) > 0 && 'quiz' == get_post_type( $test_lesson_quiz_id ),
			'The random lesson id need for the rest of this test is not a valid lesson.'
		);

		// Get the quiz questions.
		$quiz_question_posts = Sensei()->lesson->lesson_quiz_questions( $test_lesson_quiz_id );
		$this->assertTrue(
			is_array( $quiz_question_posts )
			&& isset( $quiz_question_posts[0] ) && isset( $quiz_question_posts[0]->ID )
			&& 'question' == get_post_type( $quiz_question_posts[0]->ID ),
			'The quiz questions for quiz_id: ' . $test_lesson_quiz_id . ' does not exist or is not returned as expected.'
		);

		// Create the sample data to save.
		$user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_lesson_quiz_id );

		// Assign the user to the lesson.
		Sensei_Utils::sensei_start_lesson( $test_lesson_id, $test_user_id );

		// Test for when there is no answers saved.
		$is_false_when_no_answers_saved = Sensei()->quiz->get_user_answers( $test_lesson_id, $test_user_id );
		$this->assertFalse( $is_false_when_no_answers_saved, 'The function should return false when no answers are saved on the Lesson' );

		// Save the test users answers on the tes lesson.
		$lesson_data_saved = Sensei()->quiz->save_user_answers( $user_quiz_answers, array(), $test_lesson_id, $test_user_id );
		$this->assertTrue( intval( $lesson_data_saved ) > 0, 'The comment id returned after saving the quiz answer does not represent a valid comment ' );

		// Test the function with the wrong parameters.
		$result_for_invalid_user = Sensei()->quiz->get_user_answers( '', $test_user_id );
		$this->assertFalse( $result_for_invalid_user, 'The function should return false for and invalid lesson id' );

		$result_invalid_lesson = Sensei()->quiz->get_user_answers( $test_lesson_id, '' );
		$this->assertFalse( $result_invalid_lesson, 'The function should return false for and invalid user id' );

		// Test with the correct parameters.
		$user_saved_lesson_answers = Sensei()->quiz->get_user_answers( $test_lesson_id, $test_user_id );
		$this->assertTrue( is_array( $user_saved_lesson_answers ), 'The function should return an array when an exiting user and lesson with saved answers is passed in' );

		// Check all the answers returned.
		foreach ( $user_saved_lesson_answers as $question_id => $answer ) {
			// Test if the returned questions relate to valid question post types.
			$this->assertTrue( 'question' == get_post_type( $question_id ), 'The answers returned  does not relate to valid question post types' );
			// Make sure it is the same as the saved answers.
			$this->assertTrue( $user_quiz_answers[ $question_id ] == $user_saved_lesson_answers[ $question_id ], 'The answers returned are not the same as the answers saved' );

		}

	}


	/**
	 * This test Woothemes_Sensei()->quiz->reset_user_lesson_data( $lesson_id, $user_id ).
	 *
	 * @group transient
	 */
	public function testResetUserLessonData() {

		// Setup globals for access by this method.
		$test_lesson_id = $this->factory->get_random_lesson_id();
		$test_quiz_id   = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
		// Save the user answers.
		$this->assertTrue(
			method_exists( Sensei()->quiz, 'reset_user_lesson_data' ),
			'The quiz class method `reset_user_saved_answers` does not exist '
		);

		$test_user_id = wp_create_user( 'testUserReset', '$%##$#', 'test@reset.users' );

		// Test the function with the wrong parameters.
		$result_for_empty_lesson_id = Sensei()->quiz->reset_user_lesson_data( '', $test_user_id );
		$this->assertFalse( $result_for_empty_lesson_id, 'The function should return false for an empty lesson id.' );

		$result_for_invalid_lesson_id = Sensei()->quiz->reset_user_lesson_data( -4000, $test_user_id );
		$this->assertFalse( $result_for_invalid_lesson_id, 'The function should return false for an invalid lesson id.' );

		$result_for_empty_user_id = Sensei()->quiz->reset_user_lesson_data( $this->factory->get_random_lesson_id(), '' );
		$this->assertFalse( $result_for_empty_user_id, 'The function should return false for an empty user id.' );

		$result_for_invalid_user_id = Sensei()->quiz->reset_user_lesson_data( $this->factory->get_random_lesson_id(), -500 );
		$this->assertFalse( $result_for_invalid_user_id, 'The function should return false for an invalid user id.' );

		// Test for a valid user and lesson that has no lesson_status comment on the lesson.
		$valid_parameters_for_user_with_no_lesson_status =
			Sensei()->quiz->reset_user_lesson_data( $test_lesson_id, $test_user_id );
		$this->assertFalse(
			$valid_parameters_for_user_with_no_lesson_status,
			'The function should return false if the user that has no lesson status data stored'
		);

		// Test for a valid user and lesson that has a sensei_lesson_status comment by this user.
		$user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
		$user_quiz_grades  = $this->factory->generate_user_quiz_grades( $user_quiz_answers );
		Sensei_Utils::sensei_start_lesson( $test_lesson_id, $test_user_id );
		Sensei()->quiz->save_user_answers( $user_quiz_answers, array(), $test_lesson_id, $test_user_id );
		Sensei()->quiz->set_user_grades( $user_quiz_grades, $test_lesson_id, $test_user_id );

		// Was the lesson data reset?
		ob_start();
		$lesson_data_reset        = Sensei()->quiz->reset_user_lesson_data( $test_lesson_id, $test_user_id );
		$lesson_data_reset_notice = ob_get_clean();

		$this->assertTrue( $lesson_data_reset, 'The lesson data was not reset for a valid use case' );
		$valid_notices = [
			'<div class="sensei-message info">Lesson Reset Successfully.</div>',
			'', // No notice is immediately displayed if notices haven't been printed elsewhere in another test.
		];
		$this->assertContains( $lesson_data_reset_notice, $valid_notices, 'Invalid notice displayed after quiz reset' );

		// Make sure transients are remove as well.
		$transient_key  = 'sensei_answers_' . $test_user_id . '_' . $test_lesson_id;
		$transient_data = get_transient( $transient_key );
		$this->assertFalse( $transient_data, 'The transient was not reset along with the users saved data. The result should be false.' );
	}

	/**
	 * This tests Woothemes_Sensei()->quiz->prepare_form_submitted_answers.
	 */
	public function testPrepareFormSubmittedAnswers() {

		// Make sure the method is in the class before we proceed.
		$this->assertTrue(
			method_exists( Sensei()->quiz, 'prepare_form_submitted_answers' ),
			'The prepare_form_submitted_answers method is not in class WooThemes_Sensei_Quiz'
		);

		// Does it return false for empty and non array parameters.
		$this->assertFalse(
			Sensei()->quiz->prepare_form_submitted_answers( '', '' ),
			'prepare_form_submitted_answers should return false for a non array parameter '
		);

		// Setup valid data.
		$test_lesson_id         = $this->factory->get_random_lesson_id();
		$test_quiz_id           = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
		$test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
		$files                  = $this->factory->generate_test_files( $test_user_quiz_answers );

		// Setup for the next group of assertions.
		// Setup this function to override the arguments passed to WordPress upload function.
		add_filter( 'sensei_file_upload_args', 'unit_test_override_sensei_file_upload_args' );
		function unit_test_override_sensei_file_upload_args( $args ) {
			$args['action'] = 'custom_testing_upload_function';
			return $args;
		}

		// For the valid data does it return an array?
		$prepared_test_data = Sensei()->quiz->prepare_form_submitted_answers( $test_user_quiz_answers, $files );
		$this->assertTrue(
			is_array( $prepared_test_data ),
			'function function does not return an array for valid parameters'
		);
		$this->assertTrue(
			count( $prepared_test_data ) == count( $test_user_quiz_answers ),
			'function does not return the same number of items that was passed in'
		);
		$this->assertTrue(
			array_keys( $prepared_test_data ) == array_keys( $test_user_quiz_answers ),
			'function does not return the same array keys( question ids ) that was passed in'
		);

		/**
		 * For valid data, is the answers in the array returned the same as the values passed in.
		 */

		// Testing non file questions.
		$random_index                    = $this->factory->get_random_none_file_question_index( $prepared_test_data );
		$input_array_sample_element_val  = $test_user_quiz_answers[ $random_index ];
		$output_array_sample_element_val = maybe_unserialize( base64_decode( $prepared_test_data[ $random_index ] ) );
		$question_type                   = Sensei()->question->get_question_type( $random_index );
		$test_message                    = 'The function changes the array values so much that they are not the same as when passed in. ';
		$test_message                   .= 'We inspected a random answer saved for the "' . strtoupper( $question_type ) . '" question type';
		$this->assertEquals(
			$input_array_sample_element_val,
			$output_array_sample_element_val,
			$test_message
		);

		// Testing file type questions.
		if ( isset( $files ) && ! empty( $files ) ) {
			$random_file_index = $this->factory->get_random_file_question_index( $prepared_test_data );
			$file_answer       = $prepared_test_data[ $random_file_index ];
			$this->assertFalse(
				empty( $file_answer ),
				'The file type question returns nothing, it should return an attachment ID'
			);
		}
	}

	/**
	 * This tests Woothemes_Sensei()->quiz->submit_answers_for_grading.
	 */
	public function testSubmitAnswersForGrading() {

		// Setup test data.
		$test_lesson_id         = $this->factory->get_random_lesson_id();
		$test_quiz_id           = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
		$test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
		$files                  = $this->factory->generate_test_files( $test_user_quiz_answers );

		// Remove the hooks within the submit function to avoid side effects.
		remove_all_actions( 'sensei_user_quiz_submitted' );
		remove_all_actions( 'sensei_user_lesson_end' );

		$test_user_id = wp_create_user( 'student_submitting', 'student_submitting', 'student_submiting@test.com' );

		// Make sure the function exists.
		$this->assertTrue(
			method_exists( 'WooThemes_Sensei_Quiz', 'submit_answers_for_grading' ),
			'The method submit_answers_for_grading does not exist '
		);

		// Doesn't this function return false for bogus data?
		$this->assertFalse(
			WooThemes_Sensei_Quiz::submit_answers_for_grading( '', '', '', '' ),
			'The function should return false for the wrong parameters'
		);
		$this->assertFalse(
			WooThemes_Sensei_Quiz::submit_answers_for_grading( '-100', array(), '-1000', '-90909' ),
			'The function should return false for the wrong parameters'
		);
		$this->assertFalse(
			WooThemes_Sensei_Quiz::submit_answers_for_grading( array(), array(), '20000', '30000' ),
			'The function should return false for the wrong parameters'
		);

		// Doesn't this function return true for valid data?
		Sensei_Utils::user_start_lesson( $test_user_id, $test_lesson_id );
		$result_for_valid_data = WooThemes_Sensei_Quiz::submit_answers_for_grading(
			$test_user_quiz_answers,
			$files,
			$test_lesson_id,
			$test_user_id
		);
		$this->assertTrue(
			$result_for_valid_data,
			'The function should return true for valid parameters'
		);

	}

	public function testSubmitAnswersForGrading_WhenNoQuizProgressExist_CreatesQuizProgress(): void {
		/* Arrange. */
		$test_lesson_id         = $this->factory->get_random_lesson_id();
		$test_quiz_id           = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
		$test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
		$files                  = $this->factory->generate_test_files( $test_user_quiz_answers );

		// Remove the hooks within the submit function to avoid side effects.
		remove_all_actions( 'sensei_user_quiz_submitted' );
		remove_all_actions( 'sensei_user_lesson_end' );

		$test_user_id = wp_create_user( 'student_submitting', 'student_submitting', 'student_submiting@test.com' );

		$quiz_progress_before = Sensei()->quiz_progress_repository->get( $test_quiz_id, $test_user_id );

		/* Act. */
		WooThemes_Sensei_Quiz::submit_answers_for_grading(
			$test_user_quiz_answers,
			$files,
			$test_lesson_id,
			$test_user_id
		);

		/* Assert. */
		$quiz_progress_after = Sensei()->quiz_progress_repository->get( $test_quiz_id, $test_user_id );
		$actual              = array(
			'exists_before' => null !== $quiz_progress_before,
			'exists_after'  => null !== $quiz_progress_after,
		);
		$expected            = array(
			'exists_before' => false,
			'exists_after'  => true,
		);
		$this->assertSame( $expected, $actual );
	}

	/**
	 * This tests Woothemes_Sensei()->quiz->get_user_question_answer.
	 */
	public function testGetUserQuestionAnswer() {
		// Setup the data needed for the assertions.
		$test_user_id           = wp_create_user( 'studentGetQuestionAnswer', 'studentGetQuestionAnswer', 'studentGetQuestionAnswer@test.com' );
		$test_lesson_id         = $this->factory->get_random_lesson_id();
		$test_quiz_id           = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
		$test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
		$files                  = $this->factory->generate_test_files( $test_user_quiz_answers );
		Sensei()->quiz->save_user_answers( $test_user_quiz_answers, $files, $test_lesson_id, $test_user_id );

		// Make sure the method is in the class before we proceed.
		$this->assertTrue(
			method_exists( Sensei()->quiz, 'get_user_question_answer' ),
			'The get_user_question_answer method is not in class WooThemes_Sensei_Quiz'
		);

		// Does it return false for invalid data.
		$invalid_data_message = 'This function does not check false data correctly';
		$this->assertFalse( Sensei()->quiz->get_user_question_answer( '', '', '' ), $invalid_data_message );
		$this->assertFalse( Sensei()->quiz->get_user_question_answer( ' ', ' ', ' ' ), $invalid_data_message );
		$this->assertFalse( Sensei()->quiz->get_user_question_answer( -2, -3, -1 ), $invalid_data_message );
		$this->assertFalse( Sensei()->quiz->get_user_question_answer( 3000, 5000, 5000 ), $invalid_data_message );

		// Setup data for the next assertion.
		$assertion_message   = ' Comparing the answer retrieved with the answer saved ';
		$random_question_id  = array_rand( $test_user_quiz_answers );
		$users_saved_answers = Sensei()->quiz->get_user_answers( $test_lesson_id, $test_user_id );
		$question_answer     = Sensei()->quiz->get_user_question_answer( $test_lesson_id, $random_question_id, $test_user_id );

		// Testing if the data is returned.
		$this->assertEquals( $users_saved_answers[ $random_question_id ], $question_answer, $assertion_message );
	}

	/**
	 * This tests Woothemes_Sensei()->quiz->get_user_question_answer without user_id argument.
	 */
	public function testGetUserQuestionAnswerWithoutUserId() {
		// Setup the data needed for the assertions.
		$test_user_id           = wp_create_user( 'studentGetQuestionAnswer', 'studentGetQuestionAnswer', 'studentGetQuestionAnswer@test.com' );
		$test_lesson_id         = $this->factory->get_random_lesson_id();
		$test_quiz_id           = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
		$test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
		$files                  = $this->factory->generate_test_files( $test_user_quiz_answers );
		Sensei()->quiz->save_user_answers( $test_user_quiz_answers, $files, $test_lesson_id, $test_user_id );
		$random_question_id  = array_rand( $test_user_quiz_answers );
		$users_saved_answers = Sensei()->quiz->get_user_answers( $test_lesson_id, $test_user_id );

		$this->assertNull(
			Sensei()->quiz->get_user_question_answer( $test_lesson_id, $random_question_id ),
			'Should return null when has no user_id argument and is not logged in'
		);

		// Logged user.
		wp_set_current_user( $test_user_id );
		$question_answer = Sensei()->quiz->get_user_question_answer( $test_lesson_id, $random_question_id );

		$this->assertEquals(
			$users_saved_answers[ $random_question_id ],
			$question_answer,
			'Should return the correct answer if has no user_id argument but user is logged in'
		);

	}

	/**
	 * Testing $woothemes->quiz->set_user_grades.
	 */
	public function testSetUserGrades() {

		// Setup the data needed for the assertions in this test.
		$test_user_id           = wp_create_user( 'studenttestSetUserQuizGrades', 'studenttestSetUserQuizGrades', 'studenttestSetUserQuizGrades@test.com' );
		$test_lesson_id         = $this->factory->get_random_lesson_id();
		$test_quiz_id           = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
		$test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
		$files                  = $this->factory->generate_test_files( $test_user_quiz_answers );
		Sensei()->quiz->save_user_answers( $test_user_quiz_answers, $files, $test_lesson_id, $test_user_id );
		$test_user_grades = $this->factory->generate_user_quiz_grades( $test_user_quiz_answers );

		// Make sure the method is in the class before we proceed.
		$this->assertTrue(
			method_exists( Sensei()->quiz, 'set_user_grades' ),
			'The set_user_grades method is not in class WooThemes_Sensei_quiz'
		);

		// Does this function return false for the invalid parameters.
		$invalid_data_message = 'This function does not check invalid parameters correctly';
		$this->assertFalse( Sensei()->quiz->set_user_grades( '', '', '' ), $invalid_data_message );
		$this->assertFalse( Sensei()->quiz->set_user_grades( ' ', ' ', ' ' ), $invalid_data_message );
		$this->assertFalse( Sensei()->quiz->set_user_grades( -2, -3, -1 ), $invalid_data_message );
		$this->assertFalse( Sensei()->quiz->set_user_grades( 3000, 5000, 5000 ), $invalid_data_message );

		// Does it return true for the right data?
		$this->assertTrue(
			Sensei()->quiz->set_user_grades( $test_user_grades, $test_lesson_id, $test_user_id ),
			'The function should return success for valid parameters'
		);

		// Setup for the next assertions.
		$test_lesson_status    = Sensei_Utils::user_lesson_status( $test_lesson_id, $test_user_id );
		$retrieved_quiz_grades = get_comment_meta( $test_lesson_status->comment_ID, 'quiz_grades', true );
		$random_index          = array_rand( $test_user_grades );

		// Doesn't it save the passed in grades correctly.
		$this->assertTrue( is_array( $retrieved_quiz_grades ), 'The quiz grades was not saved correctly' );
		$this->assertEquals(
			$test_user_grades[ $random_index ],
			$retrieved_quiz_grades[ $random_index ],
			'The quiz grades retrieved is not the same as those passed in when it was saved.'
		);

		// Was the transients saved correctly?
		$transient_key = 'quiz_grades_' . $test_user_id . '_' . $test_lesson_id;
		$transient_val = get_transient( $transient_key );
		$this->assertFalse( empty( $transient_val ), 'Transients are not saved correctly for user answers ' );
		$this->assertEquals(
			$transient_val,
			$test_user_grades,
			'The transient should be the same as the prepared answer which was base64 encoded'
		);

		// If saved again will the transient be updated.
		$old_transient_value  = $transient_val;
		$new_test_user_grades = $this->factory->generate_user_quiz_grades( $test_user_quiz_answers );

		Sensei()->quiz->set_user_grades( $new_test_user_grades, $test_lesson_id, $test_user_id );
		$new_transient_val = get_transient( $transient_key );

		$this->assertNotEquals(
			$new_transient_val,
			$old_transient_value,
			'Transient not updated on new save for the same user lesson combination'
		);

	}

	/**
	 * Testing $woothemes->quiz->get_user_grades.
	 */
	public function testGetUserGrades() {

		// Setup the data needed for the assertions in this test.
		$test_user_id           = wp_create_user( 'getQuizGrades', 'getQuizGrades', 'getQuizGrades@test.com' );
		$test_lesson_id         = $this->factory->get_random_lesson_id();
		$test_quiz_id           = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
		$test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
		$files                  = $this->factory->generate_test_files( $test_user_quiz_answers );
		Sensei()->quiz->save_user_answers( $test_user_quiz_answers, $files, $test_lesson_id, $test_user_id );
		$test_user_grades = $this->factory->generate_user_quiz_grades( $test_user_quiz_answers );

		// Make sure the method is in the class before we proceed.
		$this->assertTrue(
			method_exists( Sensei()->quiz, 'get_user_grades' ),
			'The get_user_grades method is not in class WooThemes_Sensei_quiz'
		);

		// Does this function return false for the invalid parameters.
		$invalid_data_message = 'This function does not check invalid parameters correctly';
		$this->assertFalse( Sensei()->quiz->get_user_grades( '', '' ), $invalid_data_message );
		$this->assertFalse( Sensei()->quiz->get_user_grades( ' ', ' ' ), $invalid_data_message );
		$this->assertFalse( Sensei()->quiz->get_user_grades( -3, -1 ), $invalid_data_message );
		$this->assertFalse( Sensei()->quiz->get_user_grades( 5000, 5000 ), $invalid_data_message );

		// Setup the next assertion.
		Sensei()->quiz->set_user_grades( $test_user_grades, $test_lesson_id, $test_user_id );
		$retrieved_grades = Sensei()->quiz->get_user_grades( $test_lesson_id, $test_user_id );

		// Doesn't this function return the saved data correctly?
		$this->assertEquals( $test_user_grades, $retrieved_grades, 'The grades saved and retrieved do not match.' );

		// Set up the next assertion data.
		$transient_key = 'quiz_grades_' . $test_user_id . '_' . $test_lesson_id;
		Sensei()->quiz->set_user_grades( $test_user_grades, $test_lesson_id, $test_user_id );
		delete_transient( $transient_key );
		Sensei()->quiz->get_user_grades( $test_lesson_id, $test_user_id );
		$transient_val = get_transient( $transient_key );

		// Ensure the transients work.
		$this->assertEquals(
			$test_user_grades,
			$transient_val,
			'The empty transient was not set after querying for the quiz answers data.'
		);

	}

	/**
	 * Testing $woothemes->quiz->get_user_question_grade.
	 */
	public function testGetUserQuestionGrade() {

		// Make sure the method exists.
		$this->assertTrue(
			method_exists(
				Sensei()->quiz,
				'get_user_question_grade'
			),
			'The function get_user_question_grade does not exist within the quiz class.'
		);

		// Does it return false for invalid data.
		$invalid_data_message = 'This function does not check false data correctly';
		$this->assertFalse( Sensei()->quiz->get_user_question_grade( '', '', '' ), $invalid_data_message );
		$this->assertFalse( Sensei()->quiz->get_user_question_grade( ' ', ' ', ' ' ), $invalid_data_message );
		$this->assertFalse( Sensei()->quiz->get_user_question_grade( -2, -3, -1 ), $invalid_data_message );
		$this->assertFalse( Sensei()->quiz->get_user_question_grade( 3000, 5000, 5000 ), $invalid_data_message );

		// Setup the data needed for the assertions in this test.
		$test_user_id           = wp_create_user( 'testGetUserQuestionGrade', 'testGetUserQuestionGrade', 'testGetUserQuestionGrade@test.com' );
		$test_lesson_id         = $this->factory->get_random_lesson_id();
		$test_quiz_id           = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
		$test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
		$test_user_grades       = $this->factory->generate_user_quiz_grades( $test_user_quiz_answers );
		Sensei()->quiz->save_user_answers( $test_user_quiz_answers, [], $test_lesson_id, $test_user_id );
		Sensei()->quiz->set_user_grades( $test_user_grades, $test_lesson_id, $test_user_id );
		$test_question_id = array_rand( $test_user_grades );
		$retrieved_grade  = Sensei()->quiz->get_user_question_grade( $test_lesson_id, $test_question_id, $test_user_id );

		// Test if the question grade can be retrieved.
		$this->assertEquals(
			$test_user_grades[ $test_question_id ],
			$retrieved_grade,
			'The grade retrieved is not equal to the one that was set for this question ID'
		);
	}

	/**
	 * Testing $woothemes->quiz->get_user_question_grade without user_id argument.
	 */
	public function testGetUserQuestionGradeWithoutUserId() {
		// Setup the data needed for the assertions in this test.
		$test_user_id           = wp_create_user( 'testGetUserQuestionGrade', 'testGetUserQuestionGrade', 'testGetUserQuestionGrade@test.com' );
		$test_lesson_id         = $this->factory->get_random_lesson_id();
		$test_quiz_id           = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
		$test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
		$test_user_grades       = $this->factory->generate_user_quiz_grades( $test_user_quiz_answers );
		Sensei()->quiz->save_user_answers( $test_user_quiz_answers, [], $test_lesson_id, $test_user_id );
		Sensei()->quiz->set_user_grades( $test_user_grades, $test_lesson_id, $test_user_id );
		$test_question_id = array_rand( $test_user_grades );

		$this->assertFalse(
			Sensei()->quiz->get_user_question_grade( $test_lesson_id, $test_question_id ),
			'Should return false when has no user_id argument and is not logged in'
		);

		wp_set_current_user( $test_user_id );
		$retrieved_grade = Sensei()->quiz->get_user_question_grade( $test_lesson_id, $test_question_id );

		// Test if the the question grade can be retrieved.
		$this->assertEquals(
			$test_user_grades[ $test_question_id ],
			$retrieved_grade,
			'Should return the correct grade if has no user_id argument but user is logged in'
		);
	}

	/**
	 * This tests Sensei()->quiz->save_user_answers_feedback.
	 */
	public function testSaveUserAnswersFeedback() {

		// Setup the data and objects needed for this test.
		$test_user_id   = wp_create_user( 'studentFeedbackSave', 'studentFeedbackSave', 'studentFeedbackSave@test.com' );
		$test_lesson_id = $this->factory->get_random_lesson_id();
		$test_quiz_id   = Sensei()->lesson->lesson_quizzes( $test_lesson_id );

		// Does the save_user_answers function exist?
		$this->assertTrue(
			method_exists( Sensei()->quiz, 'save_user_answers_feedback' ),
			'The quiz class function `save_user_answers_feedback` does not exist '
		);

		// Does this save_user_answers return false for bogus data.
		$this->assertFalse( Sensei()->quiz->save_user_answers_feedback( array(), array(), -1000, -200 ), 'save_user_answers_feedback does not return false for no existent users and lesson ' );
		$this->assertFalse( Sensei()->quiz->save_user_answers_feedback( '', array(), '', '' ), 'save_user_answers_feedback does not return false for empty parameters' );

		// Does the function return the correct information when a user doesn't exist?
		$this->assertFalse( Sensei()->quiz->save_user_answers_feedback( '', array(), '', $test_lesson_id ), 'save_user_answers_feedback does not return false for empty user' );
		$this->assertFalse( Sensei()->quiz->save_user_answers_feedback( '', array(), -500, $test_lesson_id ), 'save_user_answers_feedback does not return false for a non existant user' );

		// Test the answers_array parameter.
		$this->assertFalse( Sensei()->quiz->save_user_answers_feedback( 'Answers Text', array(), $test_lesson_id, $test_user_id ), 'save_user_answers_feedback does not return false if answers is not passed in as an array' );
		$this->assertFalse( Sensei()->quiz->save_user_answers_feedback( '', array(), $test_lesson_id, $test_user_id ), 'save_user_answers_feedback does not return false for empty answer array' );
		$this->assertFalse( Sensei()->quiz->save_user_answers_feedback( '', array(), '', '' ), 'save_user_answers_feedback does not return false incorrectly formatted answers' );

		// Test a case that is setup correctly which should return a positive result.
		Sensei_Utils::sensei_start_lesson( $test_lesson_id, $test_user_id );

		$test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
		Sensei()->quiz->save_user_answers( $test_user_quiz_answers, [], $test_lesson_id, $test_user_id );

		$test_user_grades = $this->factory->generate_user_quiz_grades( $test_user_quiz_answers );
		Sensei()->quiz->set_user_grades( $test_user_grades, $test_lesson_id, $test_user_id );

		$test_user_answers_feedback = $this->factory->generate_user_answers_feedback( $test_quiz_id );
		$lesson_data_saved          = Sensei()->quiz->save_user_answers_feedback( $test_user_answers_feedback, $test_lesson_id, $test_user_id );

		// Did the correct data return a valid comment id on the lesson as a result?
		$this->assertTrue( intval( $lesson_data_saved ) > 0, 'The comment id returned after saving the quiz feedback does not represent a valid comment ' );

		// Setup for the next group of assertions.
		$sensei_activity_logged = Sensei_Utils::sensei_check_for_activity(
			array(
				'post_id' => $test_lesson_id,
				'user_id' => $test_user_id,
			)
		);
		$status_comment         = Sensei_Utils::user_lesson_status( $test_lesson_id, $test_user_id );
		$saved_feedback         = get_comment_meta( $status_comment->comment_ID, 'quiz_answers_feedback', true );

		// Was the data that was just stored stored correctly ? Check the comment meta on the lesson id.
		$this->assertTrue( (bool) $sensei_activity_logged, 'The saved answers feedback was not stored correctly on the Lesson' );
		$this->assertFalse( empty( $saved_feedback ), 'The saved feedback was not stored correctly on the Quiz' );
		$this->assertTrue( is_array( maybe_unserialize( $saved_feedback ) ), 'The saved feedback was not stored correctly on the Lesson' );

		// Can you retrieve data and is it the same as what was stored?
		// Compare every single answer.
		$retrieved_feedback_array = maybe_unserialize( $saved_feedback );

		foreach ( $test_user_answers_feedback as $question_id => $feedback ) {

			$saved_single_answer = $retrieved_feedback_array[ $question_id ];
			$assert_message      = 'The saved feedback does not correspond to what was passed into the save_user_answers_feedback function ';
			$this->assertEquals(
				$feedback,
				base64_decode( $saved_single_answer ),
				$assert_message
			);
		}

	}

	/**
	 * This tests Sensei()->quiz->get_user_answers_feedback.
	 */
	public function testGetUserAnswersFeedback() {

		// Setup the data and objects needed for this test.
		$user_id   = wp_create_user( 'studentFeedbackGet', 'studentFeedbackGet', 'studentFeedbackGet@test.com' );
		$lesson_id = $this->factory->get_random_lesson_id();
		$quiz_id   = Sensei()->lesson->lesson_quizzes( $lesson_id );

		// Does the save_user_answers function exist?
		$this->assertTrue(
			method_exists( Sensei()->quiz, 'get_user_answers_feedback' ),
			'The quiz class function `get_user_answers_feedback` does not exist '
		);

		// Does this function handle incorrect parameters correctly?
		$this->assertFalse( Sensei()->quiz->get_user_answers_feedback( '', '' ), 'The function should return false for incorrect parameters' );
		$this->assertFalse( Sensei()->quiz->get_user_answers_feedback( 5000, 1000 ), 'The function should return false for incorrect parameters' );
		$this->assertFalse( Sensei()->quiz->get_user_answers_feedback( -1000, -121 ), 'The function should return false for incorrect parameters' );

		// Save the answers to setup the next assertion.
		Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );
		$lesson_id          = $this->factory->get_random_lesson_id();
		$generated_answers  = $this->factory->generate_user_quiz_answers( $quiz_id );
		$generated_feedback = $this->factory->generate_user_answers_feedback( $quiz_id );

		Sensei()->quiz->save_user_answers( $generated_answers, [], $lesson_id, $user_id );
		Sensei()->quiz->save_user_answers_feedback( $generated_feedback, $lesson_id, $user_id );

		$retrieved_feedback = Sensei()->quiz->get_user_answers_feedback( $lesson_id, $user_id );

		$this->assertEquals( $generated_feedback, $retrieved_feedback, 'Feedback retrieved does not match the saved data.' );

	}

	/**
	 * This test Sensei()->quiz->get_user_question_feedback.
	 */
	public function testGetUserQuestionFeedback() {
		// Does this function add_user_data exist?
		$this->assertTrue(
			method_exists( Sensei()->quiz, 'get_user_question_feedback' ),
			'The utils class function `get_user_question_feedback` does not exist '
		);

		// Does it return false for invalid data.
		$invalid_data_message = 'This get_user_question_feedback function does not check false data correctly';
		$this->assertFalse( Sensei()->quiz->get_user_question_feedback( '', '', '' ), $invalid_data_message );
		$this->assertFalse( Sensei()->quiz->get_user_question_feedback( ' ', ' ', ' ' ), $invalid_data_message );
		$this->assertFalse( Sensei()->quiz->get_user_question_feedback( -2, -3, -1 ), $invalid_data_message );
		$this->assertFalse( Sensei()->quiz->get_user_question_feedback( 3000, 5000, 5000 ), $invalid_data_message );

		// Setup the next assertion.
		$test_user_id               = wp_create_user( 'studentQuestionFeedback', 'studentQuestionFeedback', 'studQFeedback@test.com' );
		$test_lesson_id             = $this->factory->get_random_lesson_id();
		$test_quiz_id               = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
		$test_user_answers_feedback = $this->factory->generate_user_answers_feedback( $test_quiz_id );

		$generated_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
		Sensei()->quiz->save_user_answers( $generated_answers, [], $test_lesson_id, $test_user_id );

		Sensei_Utils::sensei_start_lesson( $test_lesson_id, $test_user_id );
		Sensei()->quiz->save_user_answers_feedback( $test_user_answers_feedback, $test_lesson_id, $test_user_id );
		$test_question_id = array_rand( $test_user_answers_feedback );
		$retrieved_grade  = Sensei()->quiz->get_user_question_feedback( $test_lesson_id, $test_question_id, $test_user_id );

		// Test if the the question grade can be retrieved.
		$this->assertEquals(
			$test_user_answers_feedback[ $test_question_id ],
			$retrieved_grade,
			'The feedback retrieved is not equal to the one that was set for this question ID'
		);
	}

	/**
	 * This test is for Woothemes_Sensei()->quiz->save_user_answers_feedback. We check the transients only.
	 *
	 * @group transient
	 */
	public function testSaveUserFeedbackTransients() {

		// Setup the data and objects needed for this test.
		$test_user_id               = wp_create_user( 'studFBTransients', 'studFBTransients', 'studFBTransients@test.com' );
		$test_lesson_id             = $this->factory->get_random_lesson_id();
		$test_quiz_id               = Sensei()->lesson->lesson_quizzes( $test_lesson_id );
		$test_user_answers_feedback = $this->factory->generate_user_answers_feedback( $test_quiz_id );
		Sensei_Utils::sensei_start_lesson( $test_lesson_id, $test_user_id );

		$generated_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
		Sensei()->quiz->save_user_answers( $generated_answers, [], $test_lesson_id, $test_user_id );

		Sensei()->quiz->save_user_answers_feedback( $test_user_answers_feedback, $test_lesson_id, $test_user_id );

		// Was it saved correctly?
		$transient_key         = 'sensei_answers_feedback_' . $test_user_id . '_' . $test_lesson_id;
		$transient_val         = get_transient( $transient_key );
		$decoded_transient_val = array();
		if ( is_array( $transient_val ) ) {
			foreach ( $transient_val as $question_id => $encoded_feedback ) {
				$decoded_transient_val[ $question_id ] = base64_decode( $encoded_feedback );
			}
		}

		$this->assertFalse( empty( $transient_val ), 'Transients are not saved correctly for user feedback ' );
		$this->assertEquals(
			$test_user_answers_feedback,
			$decoded_transient_val,
			'The transient should be the same as the prepared answer which was base64 encoded'
		);

		// If saved again will the transient be updated.
		$old_transient_value = $decoded_transient_val;
		$new_feedback        = $this->factory->generate_user_answers_feedback( $test_quiz_id );
		Sensei()->quiz->save_user_answers_feedback( $new_feedback, $test_lesson_id, $test_user_id );
		$new_users_retrieved_feedback = Sensei()->quiz->get_user_answers_feedback( $test_lesson_id, $test_user_id );

		$this->assertNotEquals(
			$old_transient_value,
			$new_users_retrieved_feedback,
			'Transient not updated on new save for the same user lesson combination'
		);

	}

	/**
	 * This test Woothemes_Sensei()->quiz->get_user_answers_feedback transients only.
	 *
	 * @group transient
	 */
	function testGetUserFeedbackTransients() {

		// Setup the test data.
		$test_user_id               = wp_create_user( 'studFBTransientsGet', 'studFBTransientsGet', 'studFBTransientsGet@test.com' );
		$test_lesson_id             = $this->factory->get_random_lesson_id();
		$transient_key              = 'sensei_answers_feedback_' . $test_user_id . '_' . $test_lesson_id;
		$transient_get_test         = array( base64_encode( 'studFBTransientsGet' ) );
		$transient_get_test_decoded = array( 'studFBTransientsGet' );
		set_transient( $transient_key, $transient_get_test, 10 * DAY_IN_SECONDS );
		$users_retrieved_answers = Sensei()->quiz->get_user_answers_feedback( $test_lesson_id, $test_user_id );

		// Test if the answer is taken from the transient.
		$this->assertEquals(
			$transient_get_test_decoded,
			$users_retrieved_answers,
			'The transient was not used before proceeding to get the users answers from DB'
		);

		// Setup next assertion.
		$test_quiz_id = Sensei()->lesson->lesson_quizzes( $test_lesson_id );

		Sensei_Utils::sensei_start_lesson( $test_lesson_id, $test_user_id );

		$test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
		Sensei()->quiz->save_user_answers( $test_user_quiz_answers, [], $test_lesson_id, $test_user_id );

		$test_user_grades = $this->factory->generate_user_quiz_grades( $test_user_quiz_answers );
		Sensei()->quiz->set_user_grades( $test_user_grades, $test_lesson_id, $test_user_id );

		$test_user_answers_feedback = $this->factory->generate_user_answers_feedback( $test_quiz_id );
		Sensei()->quiz->save_user_answers_feedback( $test_user_answers_feedback, $test_lesson_id, $test_user_id );

		delete_transient( $transient_key );

		Sensei()->quiz->get_user_answers_feedback( $test_lesson_id, $test_user_id );
		$transient_data_after_get_call = get_transient( $transient_key );

		// Test if a transient is created when one does not exist.
		// In this test we first delete the transient after it is been added in the save_user_answers
		// function above, then we get the data again and test if the function added the transient.
		$this->assertNotFalse(
			$transient_data_after_get_call,
			' The get_user_answers function does not set the transient after retrieving the data '
		);

		// Make sure the one of the keys passed in is in the transient.
		$random_key = array_rand( $test_user_answers_feedback );
		$this->assertArrayHasKey(
			$random_key,
			$transient_data_after_get_call,
			'The transient does not contain the same elements that we passed in'
		);

		// Make sure the number of elements passes in is the same as what is in the new transient cache.
		$this->assertEquals(
			count( $test_user_answers_feedback ),
			count( $transient_data_after_get_call ),
			'The number of elements in the transient does not match those the user submitted'
		);

	}

	/**
	 * This test Woothemes_Sensei()->quiz->save_user_answers to see if the function
	 * can overwrite the questions asked. This function should not be able to overwrite questions
	 * asked none was recorded in the first place.
	 *
	 * @group questions
	 */
	function testSaveUserAnswersQuestionsAskedNotOverwriteable() {

		global $current_user;
		$test_user_id                  = wp_create_user( 'studQuestionsaskedOverwrite', 'studQuestionsaskedOverwrite', 'studQuestionsaskedOverwrite@test.com' );
		$current_user                  = get_user_by( 'id', $test_user_id );
		$test_lesson_id                = $this->factory->get_random_lesson_id();
		$user_lesson_status_comment_id = Sensei_Utils::sensei_start_lesson( $test_lesson_id, $test_user_id );

		// Setup the quiz questions asked.
		$test_quiz_id = Sensei()->lesson->lesson_quizzes( $test_lesson_id );

		// Set the show questions to be less than the actual question the quiz has.
		$show_questions = update_post_meta( $test_quiz_id, '_show_questions', 10 );

		// Setup and accident example where the users is asked less questions by mistake.
		// Function that gets questions also load the quiz questions asked if none was set.
		$test_user_quiz_answers = $this->factory->generate_user_quiz_answers( $test_quiz_id );
		$files                  = $this->factory->generate_test_files( $test_user_quiz_answers );

		// Questions asked as it was saved initial.
		$questions_asked_count = count( $test_user_quiz_answers );

		// Submit answers and remove the hooks within the submit function to avoid side effects.
		remove_all_actions( 'sensei_user_quiz_submitted' );
		remove_all_actions( 'sensei_user_lesson_end' );
		$result_for_valid_data = WooThemes_Sensei_Quiz::submit_answers_for_grading(
			$test_user_quiz_answers,
			$files,
			$test_lesson_id,
			$test_user_id
		);

		// Get questions after submitting.
		$questions_asked_after_submitting = Sensei()->quiz_submission_repository->get_question_ids( $user_lesson_status_comment_id );

		// Check if questions asked have not been overwritten.
		$this->assertCount(
			$questions_asked_count,
			$questions_asked_after_submitting,
			'Questions asked user data does not match what was set when the lesson quiz questions was generated.'
		);

	}

	/**
	 * Ensure that when the quiz pagination is enabled, that is reflected in the loop object.
	 *
	 * @covers Sensei_Quiz::start_quiz_questions_loop
	 * @group  questions
	 */
	public function testQuizQuestionsLoopShouldPaginate() {
		/* Arrange */
		global $post, $sensei_question_loop;

		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );

		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course' => $course_id,
				],
			]
		);
		$quiz_id   = $this->factory->maybe_create_quiz_for_lesson( $lesson_id );
		$post      = get_post( $quiz_id );

		$this->factory->question->create_many( 10, [ 'quiz_id' => $quiz_id ] );

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$course_enrolment->enrol( $user_id );

		update_post_meta(
			$quiz_id,
			'_pagination',
			wp_json_encode( [ 'pagination_number' => 2 ] )
		);

		/* Act */
		Sensei_Quiz::start_quiz_questions_loop();

		/* Assert */
		$this->assertEquals( 2, $sensei_question_loop['posts_per_page'], 'The loop `posts_per_page` should be the same as the number defined in the quiz pagination setting.' );
		$this->assertEquals( 5, $sensei_question_loop['total_pages'], 'The loop `total_pages` should be calculated properly`.' );
		$this->assertCount( 2, $sensei_question_loop['questions'], 'The loop questions count should be equal to the questions per page.' );
		$this->assertEquals( 10, $sensei_question_loop['total'], 'The loop total questions count should be equal to the total questions count of the quiz.' );
	}

	/**
	 * Ensure that a quiz is available only to course enrolled users.
	 *
	 * @covers Sensei_Quiz::is_quiz_available
	 */
	public function testQuizIsAvailableOnlyToCourseEnrolledUsers() {
		/* Arrange */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course' => $course_id,
				],
			]
		);

		$quiz_id          = $this->factory->maybe_create_quiz_for_lesson( $lesson_id );
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'passed' );

		/* Assert */
		$this->assertFalse( Sensei_Quiz::is_quiz_available( $quiz_id, $user_id ), 'Users not enrolled in a course, should not be able to take the quiz.' );
		$course_enrolment->enrol( $user_id );
		$this->assertTrue( Sensei_Quiz::is_quiz_available( $quiz_id, $user_id ), 'Users enrolled in a course, should be able to take the quiz.' );
	}

	/**
	 * Ensure that a quiz is available only after completing the prerequisite lesson.
	 *
	 * @covers Sensei_Quiz::is_quiz_available
	 */
	public function testQuizIsAvailableIfPrerequisiteIsCompleted() {
		/* Arrange */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();

		$prerequisite_lesson_id = $this->factory->lesson->create();
		$lesson_id              = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course'       => $course_id,
					'_lesson_prerequisite' => $prerequisite_lesson_id,
				],
			]
		);

		$quiz_id          = $this->factory->maybe_create_quiz_for_lesson( $lesson_id );
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$course_enrolment->enrol( $user_id );

		Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'passed' );

		/* Assert */
		$this->assertFalse( Sensei_Quiz::is_quiz_available( $quiz_id, $user_id ), 'Should not be able to take the quiz if there is a uncompleted prerequisite lesson.' );
		Sensei_Utils::update_lesson_status( $user_id, $prerequisite_lesson_id, 'complete' );
		$this->assertTrue( Sensei_Quiz::is_quiz_available( $quiz_id, $user_id ), 'Should be able to take the quiz if the prerequisite lesson is completed.' );
	}

	/**
	 * Ensure that the user is not able to take the quiz if the lesson status is "ungraded".
	 *
	 * @covers Sensei_Quiz::is_quiz_completed
	 */
	public function testQuizIsCompletedIfTheLessonStatusIsUngraded() {
		/* Arrange */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course' => $course_id,
				],
			]
		);

		$quiz_id          = $this->factory->maybe_create_quiz_for_lesson( $lesson_id );
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$course_enrolment->enrol( $user_id );

		Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'passed' );

		/* Assert */
		$this->assertFalse( Sensei_Quiz::is_quiz_completed( $quiz_id, $user_id ), 'The quiz should not be considered completed if the lesson status is not ungraded.' );
		Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'ungraded' );
		$this->assertTrue( Sensei_Quiz::is_quiz_completed( $quiz_id, $user_id ), 'The quiz should be considered completed if the lesson status is ungraded.' );
	}

	public function testRedirectIfLessonIsProtected_ProtectedLessonGiven_RedirectsToLesson() {
		/* Arrange. */
		$lesson_id = $this->factory->lesson->create( [ 'post_password' => 123 ] );
		$quiz_id   = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );

		$this->go_to( get_permalink( $quiz_id ) );
		$this->prevent_wp_redirect();

		/* Act. */
		try {
			Sensei()->quiz->redirect_if_lesson_is_protected();
		} catch ( \Sensei_WP_Redirect_Exception $e ) {
			$redirect_status   = $e->getCode();
			$redirect_location = $e->getMessage();
		}

		/* Assert. */
		$this->assertEquals( 302, $redirect_status );
		$this->assertEquals( get_permalink( $lesson_id ), $redirect_location );
	}

	public function testRedirectIfLessonIsProtected_NoProtectedLessonGiven_DoesNotRedirectsToLesson() {
		/* Arrange. */
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );

		$this->go_to( get_permalink( $quiz_id ) );

		/* Act. */
		$result = Sensei()->quiz->redirect_if_lesson_is_protected();

		/* Assert. */
		$this->assertNull( $result );
	}

	public function testGetUserQuizGrade_WhenHasNoGrade_ReturnsZero() {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$lesson_id = $this->factory->lesson->create();

		/* Act. */
		$grade = Sensei()->quiz->get_user_quiz_grade( $lesson_id, $user_id );

		/* Assert. */
		$this->assertSame( 0.0, $grade );
	}

	public function testGetUserQuizGrade_WhenHasGrade_ReturnsTheGrade() {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->quiz->create(
			[
				'post_parent' => $lesson_id,
				'meta_input'  => [
					'_quiz_lesson' => $lesson_id,
				],
			]
		);

		$submission_id = Sensei_Utils::user_start_lesson( $user_id, $lesson_id );
		update_comment_meta( $submission_id, 'questions_asked', '1,2' );

		Sensei_Utils::sensei_grade_quiz( $quiz_id, 12.34, $user_id );

		/* Act. */
		$grade = Sensei()->quiz->get_user_quiz_grade( $lesson_id, $user_id );

		/* Assert. */
		$this->assertSame( 12.34, $grade );
	}

	public function testResetUserLessonData_WhenCalled_ResetsTheQuizSubmission() {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->quiz->create(
			[
				'post_parent' => $lesson_id,
				'meta_input'  => [
					'_quiz_lesson' => $lesson_id,
				],
			]
		);

		$submission_id = Sensei_Utils::user_start_lesson( $user_id, $lesson_id );

		update_comment_meta( $submission_id, 'questions_asked', '1,2' );

		Sensei_Utils::sensei_grade_quiz( $quiz_id, 12.34, $user_id );

		/* Act. */
		ob_start();
		Sensei()->quiz->reset_user_lesson_data( $lesson_id, $user_id );
		ob_end_clean();

		$quiz_submission = Sensei()->quiz_submission_repository->get( $quiz_id, $user_id );

		/* Assert. */
		$this->assertNull( $quiz_submission );
	}

	public function testResetUserLessonData_WhenCalled_ResetsTheQuizAnswers() {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->quiz->create(
			[
				'post_parent' => $lesson_id,
				'meta_input'  => [
					'_quiz_lesson' => $lesson_id,
				],
			]
		);
		$this->factory->question->create( [ 'quiz_id' => $quiz_id ] );

		$quiz_answers_map = $this->factory->generate_user_quiz_answers( $quiz_id );
		Sensei()->quiz->save_user_answers( $quiz_answers_map, [], $lesson_id, $user_id );

		$submission_id = Sensei_Utils::user_start_lesson( $user_id, $lesson_id );

		/* Act. */
		ob_start();
		Sensei()->quiz->reset_user_lesson_data( $lesson_id, $user_id );
		ob_end_clean();

		$quiz_answers = Sensei()->quiz_answer_repository->get_all( $submission_id );

		/* Assert. */
		$this->assertEmpty( $quiz_answers );
	}

	public function testResetUserLessonData_WhenCalled_ResetsTheQuizGrades() {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->quiz->create(
			[
				'post_parent' => $lesson_id,
				'meta_input'  => [
					'_quiz_lesson' => $lesson_id,
				],
			]
		);
		$this->factory->question->create( [ 'quiz_id' => $quiz_id ] );

		$quiz_answers_map = $this->factory->generate_user_quiz_answers( $quiz_id );
		$quiz_grades_map  = $this->factory->generate_user_quiz_grades( $quiz_answers_map );

		Sensei()->quiz->save_user_answers( $quiz_answers_map, [], $lesson_id, $user_id );
		Sensei()->quiz->set_user_grades( $quiz_grades_map, $lesson_id, $user_id );

		$submission_id = Sensei_Utils::user_start_lesson( $user_id, $lesson_id );
		$submission    = Sensei()->quiz_submission_repository->get( $quiz_id, $user_id );

		/* Act. */
		ob_start();
		Sensei()->quiz->reset_user_lesson_data( $lesson_id, $user_id );
		ob_end_clean();

		$quiz_grades = Sensei()->quiz_grade_repository->get_all( $submission_id );

		/* Assert. */
		$this->assertEmpty( $quiz_grades );
	}

	public function testResetUserLessonData_WhenCourseAlreadyStarted_DoesNotResetTheCourseStartDate() {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course' => $course_id,
				],
			]
		);

		Sensei_Utils::user_start_lesson( $user_id, $lesson_id );

		$course_start_date    = new DateTime( '-1 day' );
		$course_progress_mock = $this->createMock( Course_Progress_Interface::class );
		$course_progress_mock->method( 'get_started_at' )
			->willReturn( $course_start_date );

		$_course_progress_repository     = Sensei()->course_progress_repository;
		$course_progress_repository_mock = $this->createMock( Course_Progress_Repository_Interface::class );
		$course_progress_repository_mock
			->method( 'get' )
			->with( $course_id, $user_id )
			->willReturn( $course_progress_mock );

		/* Assert. */
		$course_progress_mock
			->expects( $this->once() )
			->method( 'start' )
			->with( $course_start_date );

		/* Act. */
		Sensei()->course_progress_repository = $course_progress_repository_mock;
		ob_start();
		Sensei()->quiz->reset_user_lesson_data( $lesson_id, $user_id );
		ob_end_clean();
		Sensei()->course_progress_repository = $_course_progress_repository; // Reset.
	}

	public function testResetUserLessonData_WhenCourseCompleted_ResetsTheCourseStatus() {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course' => $course_id,
				],
			]
		);

		Sensei_Utils::user_start_lesson( $user_id, $lesson_id, true );

		$course_progress_mock = $this->createMock( Course_Progress_Interface::class );
		$course_progress_mock->method( 'get_started_at' )
			->willReturn( new DateTime() );

		$_course_progress_repository     = Sensei()->course_progress_repository;
		$course_progress_repository_mock = $this->createMock( Course_Progress_Repository_Interface::class );
		$course_progress_repository_mock
			->method( 'get' )
			->with( $course_id, $user_id )
			->willReturn( $course_progress_mock );

		/* Assert. */
		$course_progress_mock
			->expects( $this->once() )
			->method( 'start' );

		/* Act. */
		Sensei()->course_progress_repository = $course_progress_repository_mock;
		ob_start();
		Sensei()->quiz->reset_user_lesson_data( $lesson_id, $user_id );
		ob_end_clean();
		Sensei()->course_progress_repository = $_course_progress_repository; // Reset.
	}

	public function testSetQuizAuthorOnCreate_WhenCreatingQuiz_SetsAuthorToLessonTeacher() {
		// Arrange.
		$main_teacher_id  = $this->factory->user->create( [ 'role' => 'teacher' ] );
		$other_teacher_id = $this->factory->user->create( [ 'role' => 'teacher' ] );

		$data = $this->factory->get_course_with_lessons(
			[
				'course_args' => [
					'post_author' => $main_teacher_id,
				],
				'lesson_args' => [
					'post_author' => $main_teacher_id,
				],
			]
		);

		// Log in as other teacher.
		wp_set_current_user( $other_teacher_id );

		// Act.

		// Create a quiz for the Lesson.
		$lesson_id = $data['lesson_ids'][0];
		$quiz_id   = wp_insert_post(
			[
				'post_type'   => 'quiz',
				'post_title'  => 'Lesson Quiz',
				'post_author' => $other_teacher_id,
				'post_status' => 'publish',
				'post_parent' => $lesson_id,
				'meta_input'  => [
					'_quiz_lesson' => $lesson_id,
				],
			]
		);

		// Assert.

		// Ensure the quiz author is changed to main_teacher_id.
		$this->assertEquals( $main_teacher_id, get_post_field( 'post_author', $quiz_id ) );
	}

	public function testMaybeCreateQuizProgress_QuizWasNotAvailable_DoesntCreateTablesBasedQuizProgress(): void {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course' => $course_id,
				],
			]
		);
		$quiz_id   = $this->factory->quiz->create(
			[
				'post_parent' => $lesson_id,
				'meta_input'  => [
					'_quiz_lesson' => $lesson_id,
				],
			]
		);

		/* Act. */
		Sensei()->quiz->maybe_create_quiz_progress( $quiz_id, $user_id );

		/* Assert. */
		global $wpdb;
		$quiz_progress_repository = new Tables_Based_Quiz_Progress_Repository( $wpdb );
		$actual                   = $quiz_progress_repository->has( $quiz_id, $user_id );
		$this->assertFalse( $actual );
	}

	public function testMaybeCreateQuizProgress_QuizWasAvailable_CreatesTablesBasedQuizProgress(): void {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course' => $course_id,
				],
			]
		);
		$quiz_id   = $this->factory->quiz->create(
			[
				'post_parent' => $lesson_id,
				'meta_input'  => [
					'_quiz_lesson' => $lesson_id,
				],
			]
		);

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$course_enrolment->enrol( $user_id );

		/* Act. */
		Sensei()->quiz->maybe_create_quiz_progress( $quiz_id, $user_id );

		/* Assert. */
		global $wpdb;
		$quiz_progress_repository = new Tables_Based_Quiz_Progress_Repository( $wpdb );
		$actual                   = $quiz_progress_repository->has( $quiz_id, $user_id );
		$this->assertTrue( $actual );
	}

	public function testMaybeCreateQuizProgress_WhenTablesBasedProgressFeatureIsDisabled_DoesntCreateTablesBasedQuizProgress(): void {
		/* Arrange. */
		add_filter( 'sensei_feature_flag_tables_based_progress', '__return_false' );

		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course' => $course_id,
				],
			]
		);
		$quiz_id   = $this->factory->quiz->create(
			[
				'post_parent' => $lesson_id,
				'meta_input'  => [
					'_quiz_lesson' => $lesson_id,
				],
			]
		);

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$course_enrolment->enrol( $user_id );

		/* Act. */
		Sensei()->quiz->maybe_create_quiz_progress( $quiz_id, $user_id );

		/* Assert. */
		global $wpdb;
		$quiz_progress_repository = new Tables_Based_Quiz_Progress_Repository( $wpdb );
		$actual                   = $quiz_progress_repository->has( $quiz_id, $user_id );
		$this->assertFalse( $actual );
	}

	public function testGetLessonIds_QuizIdsGiven_ReturnsMatchingIds(): void {
		/* Arrange. */
		$lesson_ids = $this->factory->lesson->create_many( 3 );
		$quiz_ids   = array();
		foreach ( $lesson_ids as $lesson_id ) {
			$quiz_ids[] = $this->factory->quiz->create(
				array(
					'post_parent' => $lesson_id,
					'meta_input'  => array(
						'_quiz_lesson' => $lesson_id,
					),
				)
			);
		}

		/* Act. */
		$actual = Sensei()->quiz->get_lesson_ids( $quiz_ids );

		/* Assert. */
		sort( $lesson_ids );
		sort( $actual );
		$this->assertSame( $lesson_ids, $actual );
	}

	public function testQuizFooterActions_WhenAwaitingGradeInLearningMode_RendersAwaitingGradeButton() {
		/* Arrange */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course' => $course_id,
				],
			]
		);

		$quiz_id          = $this->factory->maybe_create_quiz_for_lesson( $lesson_id );
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$course_enrolment->enrol( $user_id );

		wp_set_current_user( $user_id );

		// Enable course theme;
		update_post_meta( $course_id, Sensei_Course_Theme_Option::THEME_POST_META_NAME, Sensei_Course_Theme_Option::SENSEI_THEME );

		Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'ungraded' );

		$this->go_to( get_permalink( $quiz_id ) );

		WP_Block_Supports::$block_to_render = [
			'attrs'     => [],
			'blockName' => 'sensei-lms/quiz-actions',
		];

		/* Act */
		$result = ( new \Sensei\Blocks\Course_Theme\Quiz_Actions() )->render();

		/* Assert */
		$this->assertStringContainsString( 'Pending teacher grade', $result );
	}

	public function testQuizFooterActions_WhenAwaitingGradeButNotInLearningMode_DoesNotRenderAwaitingGradeButton() {
		/* Arrange */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course' => $course_id,
				],
			]
		);

		$quiz_id          = $this->factory->maybe_create_quiz_for_lesson( $lesson_id );
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$course_enrolment->enrol( $user_id );

		wp_set_current_user( $user_id );

		Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'ungraded' );

		$this->go_to( get_permalink( $quiz_id ) );

		WP_Block_Supports::$block_to_render = [
			'attrs'     => [],
			'blockName' => 'sensei-lms/quiz-actions',
		];

		/* Act */
		$result = ( new \Sensei\Blocks\Course_Theme\Quiz_Actions() )->render();

		/* Assert */
		$this->assertStringNotContainsString( 'Pending teacher grade', $result );
	}

	public function testQuizFooterActions_WhenPassedButInLearningMode_DoesNotRenderAwaitingGradeButton() {
		/* Arrange */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course' => $course_id,
				],
			]
		);

		$quiz_id          = $this->factory->maybe_create_quiz_for_lesson( $lesson_id );
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$course_enrolment->enrol( $user_id );

		wp_set_current_user( $user_id );

		// Enable course theme;
		update_post_meta( $course_id, Sensei_Course_Theme_Option::THEME_POST_META_NAME, Sensei_Course_Theme_Option::SENSEI_THEME );

		Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'passed' );

		$this->go_to( get_permalink( $quiz_id ) );

		WP_Block_Supports::$block_to_render = [
			'attrs'     => [],
			'blockName' => 'sensei-lms/quiz-actions',
		];

		/* Act */
		$result = ( new \Sensei\Blocks\Course_Theme\Quiz_Actions() )->render();

		/* Assert */
		$this->assertStringNotContainsString( 'Pending teacher grade', $result );
	}

	public function testQuizFooterActions_WhenInProgressButInLearningMode_DoesNotRenderAwaitingGradeButton() {
		/* Arrange */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course' => $course_id,
				],
			]
		);

		$quiz_id          = $this->factory->maybe_create_quiz_for_lesson( $lesson_id );
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$course_enrolment->enrol( $user_id );

		wp_set_current_user( $user_id );

		// Enable course theme;
		update_post_meta( $course_id, Sensei_Course_Theme_Option::THEME_POST_META_NAME, Sensei_Course_Theme_Option::SENSEI_THEME );

		// Explicitly set the lesson status to in-progress just to make sure.
		Sensei_Utils::update_lesson_status( $user_id, $lesson_id );

		$this->go_to( get_permalink( $quiz_id ) );

		WP_Block_Supports::$block_to_render = [
			'attrs'     => [],
			'blockName' => 'sensei-lms/quiz-actions',
		];

		/* Act */
		$result = ( new \Sensei\Blocks\Course_Theme\Quiz_Actions() )->render();

		/* Assert */
		$this->assertStringNotContainsString( 'Pending teacher grade', $result );
	}

	public function testActionButtons_WhenPassedInLearningMode_ShowsTheNextLessonButton() {
		/* Arrange */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_1  = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course'       => $course_id,
					'_order_' . $course_id => 1,
				],
			]
		);
		$this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course'       => $course_id,
					'_order_' . $course_id => 2,
				],
			]
		);

		$quiz_id          = $this->factory->maybe_create_quiz_for_lesson( $lesson_1 );
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$course_enrolment->enrol( $user_id );

		wp_set_current_user( $user_id );

		// Enable course theme;
		update_post_meta( $course_id, Sensei_Course_Theme_Option::THEME_POST_META_NAME, Sensei_Course_Theme_Option::SENSEI_THEME );

		$comment_id = Sensei_Utils::update_lesson_status( $user_id, $lesson_1, 'passed' );
		update_comment_meta( $comment_id, 'grade', 2 );

		$this->go_to( get_permalink( $quiz_id ) );

		WP_Block_Supports::$block_to_render = [
			'attrs'     => [],
			'blockName' => 'sensei-lms/quiz-actions',
		];

		global $sensei_question_loop;
		$sensei_question_loop['total_pages'] = 1;

		/* Act */
		$result = ( new \Sensei\Blocks\Course_Theme\Quiz_Actions() )->render();

		/* Assert */
		$this->assertStringContainsString( 'Continue to next lesson', $result );
	}

	public function testActionButtons_WhenFailedInLearningModeButPassRequired_DoesNotShowTheNextLessonButton() {
		/* Arrange */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_1  = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course'       => $course_id,
					'_order_' . $course_id => 1,
					'_quiz_has_questions'  => 1,
				],
			]
		);
		$this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course'       => $course_id,
					'_order_' . $course_id => 2,
				],
			]
		);

		$quiz_id = $this->factory->maybe_create_quiz_for_lesson( $lesson_1 );
		$this->factory->question->create(
			[
				'quiz_id'                => $quiz_id,
				'question_type'          => 'multiple-choice',
				'question_right_answers' => [ ' ', ' ', ' ' ],
				'question_wrong_answers' => [ ' ' ],
			]
		);

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$course_enrolment->enrol( $user_id );

		wp_set_current_user( $user_id );

		// Enable course theme;
		update_post_meta( $course_id, Sensei_Course_Theme_Option::THEME_POST_META_NAME, Sensei_Course_Theme_Option::SENSEI_THEME );

		$comment_id = Sensei_Utils::update_lesson_status( $user_id, $lesson_1, 'failed' );
		update_comment_meta( $comment_id, 'grade', 2 );
		update_post_meta( $quiz_id, '_pass_required', 'on' );

		$this->go_to( get_permalink( $quiz_id ) );

		WP_Block_Supports::$block_to_render = [
			'attrs'     => [],
			'blockName' => 'sensei-lms/quiz-actions',
		];

		global $sensei_question_loop;
		$sensei_question_loop['total_pages'] = 1;

		/* Act */
		$result = ( new \Sensei\Blocks\Course_Theme\Quiz_Actions() )->render();

		/* Assert */
		$this->assertStringContainsString( 'Contact teacher', $result );
	}

	public function testActionButtons_WhenFailedInLearningModeButPassNotRequired_ShowsTheNextLessonButton() {
		/* Arrange */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_1  = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course'       => $course_id,
					'_order_' . $course_id => 1,
					'_quiz_has_questions'  => 1,
				],
			]
		);
		$this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course'       => $course_id,
					'_order_' . $course_id => 2,
				],
			]
		);

		$quiz_id = $this->factory->maybe_create_quiz_for_lesson( $lesson_1 );
		$this->factory->question->create(
			[
				'quiz_id'                => $quiz_id,
				'question_type'          => 'multiple-choice',
				'question_right_answers' => [ ' ', ' ', ' ' ],
				'question_wrong_answers' => [ ' ' ],
			]
		);

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$course_enrolment->enrol( $user_id );

		wp_set_current_user( $user_id );

		// Enable course theme;
		update_post_meta( $course_id, Sensei_Course_Theme_Option::THEME_POST_META_NAME, Sensei_Course_Theme_Option::SENSEI_THEME );

		$comment_id = Sensei_Utils::update_lesson_status( $user_id, $lesson_1, 'failed' );
		update_comment_meta( $comment_id, 'grade', 2 );
		update_post_meta( $quiz_id, '_pass_required', 0 );

		$this->go_to( get_permalink( $quiz_id ) );

		WP_Block_Supports::$block_to_render = [
			'attrs'     => [],
			'blockName' => 'sensei-lms/quiz-actions',
		];

		global $sensei_question_loop;
		$sensei_question_loop['total_pages'] = 1;

		/* Act */
		$result = ( new \Sensei\Blocks\Course_Theme\Quiz_Actions() )->render();

		/* Assert */
		$this->assertStringContainsString( 'Continue to next lesson', $result );
	}

	public function testActionButtons_WhenPassedButNotInLearningMode_DoesNotShowTheNextLessonButton() {
		/* Arrange */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_1  = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course'       => $course_id,
					'_order_' . $course_id => 1,
					'_quiz_has_questions'  => 1,
				],
			]
		);
		$this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course'       => $course_id,
					'_order_' . $course_id => 2,
				],
			]
		);

		$quiz_id = $this->factory->maybe_create_quiz_for_lesson( $lesson_1 );
		$this->factory->question->create(
			[
				'quiz_id'                => $quiz_id,
				'question_type'          => 'multiple-choice',
				'question_right_answers' => [ ' ', ' ', ' ' ],
				'question_wrong_answers' => [ ' ' ],
			]
		);

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$course_enrolment->enrol( $user_id );

		wp_set_current_user( $user_id );

		$comment_id = Sensei_Utils::update_lesson_status( $user_id, $lesson_1, 'passed' );
		update_comment_meta( $comment_id, 'grade', 2 );
		update_post_meta( $quiz_id, '_pass_required', 0 );

		$this->go_to( get_permalink( $quiz_id ) );

		WP_Block_Supports::$block_to_render = [
			'attrs'     => [],
			'blockName' => 'sensei-lms/quiz-actions',
		];

		global $sensei_question_loop;
		$sensei_question_loop['total_pages'] = 1;

		/* Act */
		$result = ( new \Sensei\Blocks\Course_Theme\Quiz_Actions() )->render();

		/* Assert */
		$this->assertStringNotContainsString( 'Continue to next lesson', $result );
	}

	public function testActionButtons_WhenPassedButNextLessonHasLowerOrder_DoesNotShowTheNextLessonButton() {
		/* Arrange */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_1  = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course'       => $course_id,
					'_order_' . $course_id => 2,
					'_quiz_has_questions'  => 1,
				],
			]
		);
		$this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course'       => $course_id,
					'_order_' . $course_id => 1,
				],
			]
		);

		$quiz_id = $this->factory->maybe_create_quiz_for_lesson( $lesson_1 );
		$this->factory->question->create(
			[
				'quiz_id'                => $quiz_id,
				'question_type'          => 'multiple-choice',
				'question_right_answers' => [ ' ', ' ', ' ' ],
				'question_wrong_answers' => [ ' ' ],
			]
		);

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$course_enrolment->enrol( $user_id );

		wp_set_current_user( $user_id );

		// Enable course theme;
		update_post_meta( $course_id, Sensei_Course_Theme_Option::THEME_POST_META_NAME, Sensei_Course_Theme_Option::SENSEI_THEME );

		$comment_id = Sensei_Utils::update_lesson_status( $user_id, $lesson_1, 'passed' );
		update_comment_meta( $comment_id, 'grade', 2 );
		update_post_meta( $quiz_id, '_pass_required', 0 );

		$this->go_to( get_permalink( $quiz_id ) );

		WP_Block_Supports::$block_to_render = [
			'attrs'     => [],
			'blockName' => 'sensei-lms/quiz-actions',
		];

		global $sensei_question_loop;
		$sensei_question_loop['total_pages'] = 1;

		/* Act */
		$result = ( new \Sensei\Blocks\Course_Theme\Quiz_Actions() )->render();

		/* Assert */
		$this->assertStringNotContainsString( 'Continue to next lesson', $result );
	}

	public function testActionButtons_WhenQuizPassedButOnLessonPage_DoesNotShowTheNextLessonButton() {
		/* Arrange */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_1  = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course'       => $course_id,
					'_order_' . $course_id => 1,
					'_quiz_has_questions'  => 1,
				],
			]
		);
		$this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course'       => $course_id,
					'_order_' . $course_id => 2,
				],
			]
		);

		$quiz_id = $this->factory->maybe_create_quiz_for_lesson( $lesson_1 );
		$this->factory->question->create(
			[
				'quiz_id'                => $quiz_id,
				'question_type'          => 'multiple-choice',
				'question_right_answers' => [ ' ', ' ', ' ' ],
				'question_wrong_answers' => [ ' ' ],
			]
		);

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$course_enrolment->enrol( $user_id );

		wp_set_current_user( $user_id );

		// Enable course theme;
		update_post_meta( $course_id, Sensei_Course_Theme_Option::THEME_POST_META_NAME, Sensei_Course_Theme_Option::SENSEI_THEME );

		$comment_id = Sensei_Utils::update_lesson_status( $user_id, $lesson_1, 'passed' );
		update_comment_meta( $comment_id, 'grade', 2 );
		update_post_meta( $quiz_id, '_pass_required', 0 );

		$this->go_to( get_permalink( $lesson_1 ) );

		WP_Block_Supports::$block_to_render = [
			'attrs'     => [],
			'blockName' => 'sensei-lms/quiz-actions',
		];

		global $sensei_question_loop;
		$sensei_question_loop['total_pages'] = 1;

		/* Act */
		$result = ( new \Sensei\Blocks\Course_Theme\Quiz_Actions() )->render();

		/* Assert */
		$this->assertStringNotContainsString( 'Continue to next lesson', $result );
	}

	public function testGetPrimaryButtonHTML_DefaultParams_ContainsCorrectText() {
		/* Arrange */
		$text = 'Click Me!';

		/* Act */
		$html = Sensei()->quiz->get_primary_button_html( $text );

		/* Assert */
		$this->assertStringContainsString( $text, $html );
	}

	public function testGetPrimaryButtonHTML_SomeParams_ContainsCorrectURL() {
		/* Arrange */
		$url = 'https://example.com';

		/* Act */
		$html = Sensei()->quiz->get_primary_button_html( 'Click Me', $url );

		/* Assert */
		$this->assertStringContainsString( $url, $html );
	}

	public function testGetPrimaryButtonHTML_AllParams_ContainsCorrectClasses() {
		/* Arrange */
		$classes = array(
			'abc',
			'def',
		);

		/* Act */
		$html = Sensei()->quiz->get_primary_button_html( 'Click Me', '', $classes );

		/* Assert */
		foreach ( $classes as $class ) {
			$this->assertStringContainsString( $class, $html );
		}
	}

	public function testIsQuizAwaitingGradeForUser_IsUngraded_ReturnsTrue() {
		/* Arrange */
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			[
				'post_parent' => $course_id,
				'meta_input'  => [
					'_lesson_course' => $course_id,
				],
			]
		);
		$quiz_id   = $this->factory->maybe_create_quiz_for_lesson( $lesson_id );

		$progress = $this->createMock( Quiz_Progress_Interface::class );
		$progress->method( 'get_status' )
			->willReturn( Quiz_Progress_Interface::STATUS_UNGRADED );

		$quiz_progress_repository          = Sensei()->quiz_progress_repository;
		Sensei()->quiz_progress_repository = $this->createMock( Quiz_Progress_Repository_Interface::class );
		Sensei()->quiz_progress_repository->method( 'get' )
			->willReturn( $progress );

		/* Act */
		$result = Sensei_Quiz::is_quiz_awaiting_grade_for_user( $lesson_id, 1 );

		/* Assert */
		$this->assertTrue( $result );

		/* Reset */
		Sensei()->quiz_progress_repository = $quiz_progress_repository;
	}

	public function testIsQuizAwaitingGradeForUser_IsNotUngraded_ReturnsFalse() {
		/* Arrange */
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			[
				'post_parent' => $course_id,
				'meta_input'  => [
					'_lesson_course' => $course_id,
				],
			]
		);
		$quiz_id   = $this->factory->maybe_create_quiz_for_lesson( $lesson_id );

		$progress = $this->createMock( Quiz_Progress_Interface::class );
		$progress->method( 'get_status' )
			->willReturn( Quiz_Progress_Interface::STATUS_GRADED );

		$quiz_progress_repository          = Sensei()->quiz_progress_repository;
		Sensei()->quiz_progress_repository = $this->createMock( Quiz_Progress_Repository_Interface::class );
		Sensei()->quiz_progress_repository->method( 'get' )
			->willReturn( $progress );

		/* Act */
		$result = Sensei_Quiz::is_quiz_awaiting_grade_for_user( $lesson_id, 1 );

		/* Assert */
		$this->assertFalse( $result );

		/* Reset */
		Sensei()->quiz_progress_repository = $quiz_progress_repository;
	}
}

<?php
/**
 * Sensei REST API: Sensei_REST_API_Quiz_Controller_Tests tests
 *
 * @package sensei-lms
 * @since 3.9.0
 * @group rest-api
 */

/**
 * Class Sensei_REST_API_Quiz_Controller tests.
 */
class Sensei_REST_API_Quiz_Controller_Tests extends WP_Test_REST_TestCase {
	use Sensei_Test_Login_Helpers;
	use Sensei_REST_API_Test_Helpers;

	/**
	 * A server instance that we use in tests to dispatch requests.
	 *
	 * @var WP_REST_Server $server
	 */
	protected $server;

	/**
	 * Sensei post factory.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Quiz route.
	 */
	const REST_ROUTE = '/sensei-internal/v1/quiz/';

	/**
	 * Test specific setup.
	 */
	public function setUp() {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init' );

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Tests that a simple request response matches the schema.
	 */
	public function testGetSimple() {
		$this->login_as_teacher();

		$course_result = $this->factory->get_course_with_lessons(
			[
				'module_count'   => 1,
				'lesson_count'   => 1,
				'question_count' => 5,
			]
		);

		$quiz_id  = Sensei()->lesson->lesson_quizzes( $course_result['lesson_ids'][0] );
		$request  = new WP_REST_Request( 'GET', self::REST_ROUTE . $quiz_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( $response->get_status(), 200 );

		$endpoint = new Sensei_REST_API_Quiz_Controller( '' );
		$this->assertMeetsSchema( $endpoint->get_schema(), $response->get_data() );
	}

	/**
	 * Tests that a quiz is only accessed by admins and the teacher that created it.
	 */
	public function testGetAnotherTeachersCourse() {
		$this->login_as_teacher();

		$quiz_id = $this->factory->quiz->create();

		$request  = new WP_REST_Request( 'GET', '/sensei-internal/v1/quiz/' . $quiz_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( $response->get_status(), 200, 'Teacher which created the quiz cannot access it.' );

		$this->login_as_teacher_b();

		$request  = new WP_REST_Request( 'GET', '/sensei-internal/v1/quiz/' . $quiz_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( $response->get_status(), 403, 'Teacher which did not create the quiz can access it.' );

		$this->login_as_admin();

		$request  = new WP_REST_Request( 'GET', '/sensei-internal/v1/quiz/' . $quiz_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( $response->get_status(), 200, 'Admin user cannot access the quiz.' );

		$this->logout();

		$request  = new WP_REST_Request( 'GET', '/sensei-internal/v1/quiz/' . $quiz_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( $response->get_status(), 401, 'Logged out user can access the quiz.' );
	}

	/**
	 * Tests that endpoint returns 404 when quiz doesn't exist.
	 */
	public function testGetMissingQuiz() {
		$this->login_as_admin();

		$request  = new WP_REST_Request( 'GET', '/sensei-internal/v1/quiz/1234' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( $response->get_status(), 404 );
	}

	/**
	 * Tests that quiz options are returned correctly.
	 */
	public function testQuizProperties() {
		$this->login_as_teacher();

		$quiz_args = [
			'meta_input' => [
				'_enable_quiz_reset'     => 'on',
				'_random_question_order' => 'yes',
				'_pass_required'         => 'on',
				'_quiz_passmark'         => 10,
				'_quiz_grade_type'       => 'auto',
				'_show_questions'        => '',
			],
		];
		$quiz_id   = $this->factory->quiz->create( $quiz_args );

		$response_data = $this->send_and_validate( $quiz_id );

		$this->assertTrue( $response_data['options']['pass_required'] );
		$this->assertTrue( $response_data['options']['auto_grade'] );
		$this->assertTrue( $response_data['options']['allow_retakes'] );
		$this->assertTrue( $response_data['options']['random_question_order'] );
		$this->assertEquals( 10, $response_data['options']['quiz_passmark'] );
		$this->assertNull( $response_data['options']['show_questions'] );

		$another_quiz_args = [
			'meta_input' => [
				'_enable_quiz_reset'     => '',
				'_random_question_order' => 'no',
				'_pass_required'         => '',
				'_quiz_passmark'         => 0,
				'_quiz_grade_type'       => 'manual',
				'_show_questions'        => 3,
			],
		];
		$another_quiz_id   = $this->factory->quiz->create( $another_quiz_args );

		$response_data = $this->send_and_validate( $another_quiz_id );

		$this->assertFalse( $response_data['options']['pass_required'] );
		$this->assertFalse( $response_data['options']['auto_grade'] );
		$this->assertFalse( $response_data['options']['allow_retakes'] );
		$this->assertFalse( $response_data['options']['random_question_order'] );
		$this->assertEquals( 0, $response_data['options']['quiz_passmark'] );
		$this->assertEquals( 3, $response_data['options']['show_questions'] );
	}

	/**
	 * Tests multiple choice question properties.
	 */
	public function testMultipleChoiceQuestion() {
		$this->login_as_teacher();

		$quiz_id = $this->factory->quiz->create();
		$this->factory->question->create(
			[
				'quiz_id'                => $quiz_id,
				'question_type'          => 'multiple-choice',
				'question_right_answers' => [ 'Right answer' ],
				'question_wrong_answers' => [ 'Wrong,comma', 'Wrong 1' ],
				'random_order'           => 'no',
				'answer_order'           => 'ac70b9a3f24b5b657826b567057169a2,b13d55d1ff11d676253fa5e4b0517bd7,89dc5589bfebac1468e8823afd5a4861',
				'answer_feedback'        => 'Some feedback',
			]
		);
		$response_data = $this->send_and_validate( $quiz_id );

		$this->assertFalse( $response_data['questions'][0]['random_order'] );
		$this->assertEquals( 'Some feedback', $response_data['questions'][0]['answer_feedback'] );
		$this->assertEquals( 'multiple-choice', $response_data['questions'][0]['type'] );
		$this->assertEquals( 'Wrong 1', $response_data['questions'][0]['options'][0]['label'] );
		$this->assertFalse( $response_data['questions'][0]['options'][0]['correct'] );
		$this->assertEquals( 'Right answer', $response_data['questions'][0]['options'][2]['label'] );
		$this->assertTrue( $response_data['questions'][0]['options'][2]['correct'] );
	}

	/**
	 * Tests true/false question properties.
	 */
	public function testBooleanQuestion() {
		$this->login_as_teacher();

		$quiz_id = $this->factory->quiz->create();
		$this->factory->question->create(
			[
				'quiz_id'                       => $quiz_id,
				'question_type'                 => 'boolean',
				'answer_feedback'               => 'Some feedback',
				'question_right_answer_boolean' => 'false',
			]
		);

		$response_data = $this->send_and_validate( $quiz_id );

		$this->assertFalse( $response_data['questions'][0]['answer'] );
		$this->assertEquals( 'Some feedback', $response_data['questions'][0]['answer_feedback'] );
		$this->assertEquals( 'boolean', $response_data['questions'][0]['type'] );
	}

	/**
	 * Tests gap fill question properties.
	 */
	public function testGapFillQuestion() {
		$this->login_as_teacher();

		$quiz_id = $this->factory->quiz->create();
		$this->factory->question->create(
			[
				'quiz_id'                                => $quiz_id,
				'question_type'                          => 'gap-fill',
				'add_question_right_answer_gapfill_pre'  => 'BEFORE',
				'add_question_right_answer_gapfill_gap'  => 'THE GAP|GAP',
				'add_question_right_answer_gapfill_post' => 'AFTER',
			]
		);

		$response_data = $this->send_and_validate( $quiz_id );

		$this->assertEquals( 'gap-fill', $response_data['questions'][0]['type'] );
		$this->assertEquals( 'BEFORE', $response_data['questions'][0]['before'] );
		$this->assertEquals( 'THE GAP', $response_data['questions'][0]['gap'][0] );
		$this->assertEquals( 'GAP', $response_data['questions'][0]['gap'][1] );
		$this->assertEquals( 'AFTER', $response_data['questions'][0]['after'] );
	}

	/**
	 * Tests single line question properties.
	 */
	public function testSingleLineQuestion() {
		$this->login_as_teacher();

		$quiz_id = $this->factory->quiz->create();
		$this->factory->question->create(
			[
				'quiz_id'                              => $quiz_id,
				'question_type'                        => 'single-line',
				'add_question_right_answer_singleline' => 'NOTES',
			]
		);

		$response_data = $this->send_and_validate( $quiz_id );

		$this->assertEquals( 'single-line', $response_data['questions'][0]['type'] );
		$this->assertEquals( 'NOTES', $response_data['questions'][0]['teacher_notes'] );
	}

	/**
	 * Tests multiple line question properties.
	 */
	public function testMultilineQuestion() {
		$this->login_as_teacher();

		$quiz_id = $this->factory->quiz->create();
		$this->factory->question->create(
			[
				'quiz_id'                             => $quiz_id,
				'question_type'                       => 'multi-line',
				'add_question_right_answer_multiline' => 'NOTES',
			]
		);

		$response_data = $this->send_and_validate( $quiz_id );

		$this->assertEquals( 'multi-line', $response_data['questions'][0]['type'] );
		$this->assertEquals( 'NOTES', $response_data['questions'][0]['teacher_notes'] );
	}

	/**
	 * Tests file upload question properties.
	 */
	public function testFileUploadQuestion() {
		$this->login_as_teacher();

		$quiz_id = $this->factory->quiz->create();
		$this->factory->question->create(
			[
				'quiz_id'                              => $quiz_id,
				'question_type'                        => 'file-upload',
				'add_question_right_answer_fileupload' => 'Teacher note',
				'add_question_wrong_answer_fileupload' => 'User note',
			]
		);

		$response_data = $this->send_and_validate( $quiz_id );

		$this->assertEquals( 'file-upload', $response_data['questions'][0]['type'] );
		$this->assertEquals( 'Teacher note', $response_data['questions'][0]['teacher_notes'] );
		$this->assertEquals( 'User note', $response_data['questions'][0]['student_help'] );
	}

	/**
	 * Tests question category properties.
	 */
	public function testQuestionCategory() {
		$this->login_as_teacher();

		$quiz_id = $this->factory->quiz->create();
		$this->factory->multiple_question->create(
			[
				'quiz_id'    => $quiz_id,
				'meta_input' => [
					'number' => 3,
				],
			]
		);
		$response_data = $this->send_and_validate( $quiz_id );

		$this->assertEquals( 'question-category', $response_data['questions'][0]['type'] );
		$this->assertEquals( 3, $response_data['questions'][0]['questions'] );
	}

	/**
	 * Tests single line question properties.
	 */
	public function testQuestionCommonProperties() {
		$this->login_as_teacher();

		$quiz_id     = $this->factory->quiz->create();
		$question_id = $this->factory->question->create(
			[
				'quiz_id'              => $quiz_id,
				'question_type'        => 'single-line',
				'question'             => 'Will it blend?',
				'question_description' => 'That is the question.',
				'question_grade'       => 10,
			]
		);

		add_post_meta( $question_id, '_quiz_id', $quiz_id, false );

		$response_data = $this->send_and_validate( $quiz_id );

		$this->assertTrue( $response_data['questions'][0]['shared'] );
		$this->assertEquals( 'Will it blend?', $response_data['questions'][0]['title'] );
		$this->assertEquals( 'That is the question.', $response_data['questions'][0]['description'] );
		$this->assertEquals( 10, $response_data['questions'][0]['grade'] );
	}

	/**
	 * Helper method to send and validate a GET request.
	 *
	 * @param int $quiz_id The quiz id.
	 *
	 * @return array Response data.
	 */
	private function send_and_validate( int $quiz_id ) : array {
		$request  = new WP_REST_Request( 'GET', self::REST_ROUTE . $quiz_id );
		$response = $this->server->dispatch( $request );

		$endpoint = new Sensei_REST_API_Quiz_Controller( '' );
		$this->assertMeetsSchema( $endpoint->get_schema(), $response->get_data() );

		return $response->get_data();
	}
}

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
		define( 'REST_REQUEST', true );

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

		$request  = new WP_REST_Request( 'GET', self::REST_ROUTE . $quiz_id );
		$response = $this->server->dispatch( $request );

		$response_data = $response->get_data();
		$endpoint      = new Sensei_REST_API_Quiz_Controller( '' );
		$this->assertMeetsSchema( $endpoint->get_schema(), $response_data );

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

		$request  = new WP_REST_Request( 'GET', self::REST_ROUTE . $another_quiz_id );
		$response = $this->server->dispatch( $request );

		$response_data = $response->get_data();
		$endpoint      = new Sensei_REST_API_Quiz_Controller( '' );
		$this->assertMeetsSchema( $endpoint->get_schema(), $response_data );

		$this->assertFalse( $response_data['options']['pass_required'] );
		$this->assertFalse( $response_data['options']['auto_grade'] );
		$this->assertFalse( $response_data['options']['allow_retakes'] );
		$this->assertFalse( $response_data['options']['random_question_order'] );
		$this->assertEquals( 0, $response_data['options']['quiz_passmark'] );
		$this->assertEquals( 3, $response_data['options']['show_questions'] );
	}
}

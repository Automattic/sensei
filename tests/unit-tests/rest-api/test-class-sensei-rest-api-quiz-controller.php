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
}

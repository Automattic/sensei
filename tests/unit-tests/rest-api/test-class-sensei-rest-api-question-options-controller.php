<?php
/**
 * Sensei REST API: Sensei_REST_API_Question_Options_Controller_Tests tests
 *
 * @package sensei-lms
 * @since 3.9.0
 */

/**
 * Class Sensei_REST_API_Question_Options_Controller tests.
 *
 * @group rest-api
 */
class Sensei_REST_API_Question_Options_Controller_Tests extends WP_Test_REST_TestCase {
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
	const REST_ROUTE = '/sensei-internal/v1/question-options';

	/**
	 * Test specific setup.
	 */
	public function setUp(): void {
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

		$question_id = $this->factory->question->create();

		$request  = new WP_REST_Request( 'GET', self::REST_ROUTE . '/' . $question_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data     = $response->get_data();
		$endpoint = new Sensei_REST_API_Question_Options_Controller( '' );
		$this->assertMeetsSchema( $endpoint->get_single_question_schema(), $data );

		$this->assertEquals( $question_id, $data['id'] );
	}

	/**
	 * Tests that a teacher cannot get another teacher's question options.
	 */
	public function testGetAnotherTeacherQuestion() {
		$this->login_as_teacher();
		$question_id = $this->factory->question->create();

		$this->login_as_teacher_b();
		$request  = new WP_REST_Request( 'GET', self::REST_ROUTE . '/' . $question_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Tests that a admin can get a teacher's question options.
	 */
	public function testAdminGetTeacherQuestion() {
		$this->login_as_teacher();
		$question_id = $this->factory->question->create();

		$this->login_as_teacher();
		$request  = new WP_REST_Request( 'GET', self::REST_ROUTE . '/' . $question_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$endpoint = new Sensei_REST_API_Question_Options_Controller( '' );
		$this->assertMeetsSchema( $endpoint->get_single_question_schema(), $response->get_data() );
	}

	/**
	 * Tests retrieving multiple questions in bulk.
	 */
	public function testGetMultipleQuestions() {
		$this->login_as_teacher();
		$question_ids = $this->factory->question->create_many( 3 );

		$request = new WP_REST_Request( 'GET', self::REST_ROUTE );
		$request->set_param( 'question_ids', implode( ',', $question_ids ) );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$endpoint = new Sensei_REST_API_Question_Options_Controller( '' );
		$this->assertMeetsSchema( $endpoint->get_multiple_question_schema(), $data );

		$this->assertEquals( count( $question_ids ), count( $data ) );

		$retrieved_ids = wp_list_pluck( $data, 'id' );
		sort( $retrieved_ids );

		$this->assertEquals( $question_ids, $retrieved_ids, 'Created IDs should match' );
	}

	/**
	 * Tests retrieving multiple questions in bulk and only returns questions the user has access to.
	 */
	public function testGetMultipleQuestionsOtherTeacher() {
		$this->login_as_teacher();
		$teacher_a_question_ids = $this->factory->question->create_many( 2 );

		$this->login_as_teacher_b();
		$teacher_b_question_ids = $this->factory->question->create_many( 2 );

		$all_question_ids = array_merge( $teacher_a_question_ids, $teacher_b_question_ids );

		$request = new WP_REST_Request( 'GET', self::REST_ROUTE );
		$request->set_param( 'question_ids', implode( ',', $all_question_ids ) );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$endpoint = new Sensei_REST_API_Question_Options_Controller( '' );
		$this->assertMeetsSchema( $endpoint->get_multiple_question_schema(), $data );

		$this->assertEquals( count( $teacher_b_question_ids ), count( $data ) );

		$retrieved_ids = wp_list_pluck( $data, 'id' );
		sort( $retrieved_ids );

		$this->assertEquals( $teacher_b_question_ids, $retrieved_ids, 'Created IDs should match' );
	}
}

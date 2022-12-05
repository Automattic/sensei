<?php

/**
 * Sensei REST API Course Utils Controller Unit Tests.
 *
 * @covers Sensei_REST_API_Course_Utils_Controller
 */
class Sensei_REST_API_Course_Utils_Controller_Test extends WP_Test_REST_TestCase {
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

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testUpdateTeacher_RequestGiven_ReturnsSuccessfulResponse() {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$teacher_id = $this->get_user_by_role( 'teacher', '_c' );

		$this->login_as_admin();

		/* Act. */
		$request = new WP_REST_Request( 'PUT', '/sensei-internal/v1/course-utils/update-teacher' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'teacher'           => $teacher_id,
					'post_id'           => $course_id,
					'sensei_meta_nonce' => wp_create_nonce( Sensei()->teacher::NONCE_ACTION_NAME ),
				]
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'success', $response->get_data()['status'] );
	}

	public function testUpdateTeacher_RequestGiven_ReturnsInvalidNonceResponse() {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$teacher_id = $this->get_user_by_role( 'teacher', '_c' );

		$this->login_as_admin();

		/* Act. */
		$request = new WP_REST_Request( 'PUT', '/sensei-internal/v1/course-utils/update-teacher' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'teacher'           => $teacher_id,
					'post_id'           => $course_id,
					'sensei_meta_nonce' => 'fake_nonce',
				]
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Invalid parameter(s): sensei_meta_nonce', $response->get_data()['message'] );
	}

	public function testUpdateTeacher_RequestGiven_ReturnsMissingNonceResponse() {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$teacher_id = $this->get_user_by_role( 'teacher', '_c' );

		$this->login_as_admin();

		/* Act. */
		$request = new WP_REST_Request( 'PUT', '/sensei-internal/v1/course-utils/update-teacher' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'teacher' => $teacher_id,
					'post_id' => $course_id,
				]
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Missing parameter(s): sensei_meta_nonce', $response->get_data()['message'] );
	}

	public function testUpdateTeacher_RequestGiven_UnauthorizedUserResponse() {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$teacher_id = $this->get_user_by_role( 'teacher', '_c' );

		$this->login_as_student();

		/* Act. */
		$request = new WP_REST_Request( 'PUT', '/sensei-internal/v1/course-utils/update-teacher' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'teacher'           => $teacher_id,
					'post_id'           => $course_id,
					'sensei_meta_nonce' => wp_create_nonce( Sensei()->teacher::NONCE_ACTION_NAME ),
				]
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 403, $response->get_status() );
		$this->assertEquals( 'Sorry, you are not allowed to do that.', $response->get_data()['message'] );
	}
}

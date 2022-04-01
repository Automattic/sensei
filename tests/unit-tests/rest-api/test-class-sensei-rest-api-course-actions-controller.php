<?php

/**
 * Test for Sensei_REST_API_Course_Actions_Controller.
 *
 * @covers Sensei_REST_API_Course_Actions_Controller
 */
class Sensei_REST_API_Course_Actions_Controller_Test extends WP_Test_REST_TestCase {
	use Sensei_Test_Login_Helpers;
	use Sensei_Course_Enrolment_Test_Helpers;
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
	public function setUp() {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init' );

		$this->factory = new Sensei_Factory();

		self::resetEnrolmentProviders();
		$this->prepareEnrolmentManager();
	}

	public function tearDown() {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testAddUsersToCourses_RequestGiven_ReturnsSuccessfulResponse() {
		/* Arrange. */
		$user_ids   = $this->factory->user->create_many( 2 );
		$course_ids = $this->factory->course->create_many( 2 );

		$this->login_as_admin();

		/* Act. */
		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-actions/add' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'user_ids'   => $user_ids,
					'course_ids' => $course_ids,
				]
			)
		);
		$response = $this->server->dispatch( $request );

		/* Assert. */
		$this->assertEquals( 200, $response->get_status() );
	}

	public function testAddUsersToCourses_UserWithInsufficientPermissions_ReturnsFailedResponse() {
		/* Arrange. */
		$user_ids   = $this->factory->user->create_many( 2 );
		$course_ids = $this->factory->course->create_many( 2 );

		$this->login_as_student();

		/* Act. */
		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-actions/add' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'user_ids'   => $user_ids,
					'course_ids' => $course_ids,
				]
			)
		);
		$response = $this->server->dispatch( $request );

		/* Assert. */
		$this->assertEquals( 403, $response->get_status() );
	}
}

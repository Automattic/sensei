<?php
/**
 * Sensei REST API: Sensei_REST_API_Home_Controller tests
 *
 * @package sensei-lms
 * @since   $$next-version$$
 */

/**
 * Class Sensei_REST_API_Home_Controller REST tests.
 *
 * @covers Sensei_REST_API_Home_Controller
 */
class Sensei_REST_API_Home_Controller_REST_Test extends WP_Test_REST_TestCase {
	use Sensei_Test_Login_Helpers;

	/**
	 * A server instance that we use in tests to dispatch requests.
	 *
	 * @var WP_REST_Server $server
	 */
	protected $server;

	const REST_ROUTE = '/sensei-internal/v1/home';

	public function setUp() {
		parent::setUp();

		// Setup REST server for integration tests.
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );
	}

	public function tearDown() {
		// Unregister REST server.
		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tearDown();
	}

	/**
	 * Asserts that guests cannot access Home Data.
	 */
	public function testRESTRequestReturns401ForGuests() {
		$this->login_as( null );

		$request  = new WP_REST_Request( 'GET', self::REST_ROUTE );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Asserts that admins can access Home Data.
	 */
	public function testRESTRequestReturns200ForAdmins() {
		$this->login_as_admin();

		// Prevent requests.
		add_filter(
			'pre_http_request',
			function() {
				return [ 'body' => '[]' ];
			}
		);

		$request  = new WP_REST_Request( 'GET', self::REST_ROUTE );
		$response = $this->server->dispatch( $request );
		remove_all_filters( 'pre_http_request' );

		$this->assertEquals( 200, $response->get_status() );
	}
}

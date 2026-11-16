<?php
/**
 * Sensei REST API: Sensei_REST_API_Home_Controller tests
 *
 * @package sensei-lms
 * @since   4.8.0
 */

/**
 * Class Sensei_REST_API_Home_Controller REST tests.
 *
 * @covers Sensei_REST_API_Home_Controller
 */
class Sensei_REST_API_Home_Controller_REST_Test extends WP_Test_REST_TestCase {
	use Sensei_Test_Login_Helpers;
	use Sensei_Test_Redirect_Helpers;

	/**
	 * A server instance that we use in tests to dispatch requests.
	 *
	 * @var WP_REST_Server $server
	 */
	protected $server;

	const REST_ROUTE = '/sensei-internal/v1/home';

	public function setUp(): void {
		parent::setUp();

		// Setup REST server for integration tests.
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );
	}

	public function tearDown(): void {
		// Unregister REST server.
		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tearDown();
	}

	/**
	 * Asserts that guests cannot access Home Data.
	 */
	public function testGetHomeDataRequestReturns401ForGuests() {
		$this->login_as( null );

		$response = $this->dispatchRequest( 'GET' );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Asserts that admins can access Home Data.
	 */
	public function testGetHomeDataRequestReturns200ForAdmins() {
		$this->login_as_admin();

		$response = $this->dispatchRequest( 'GET' );

		$this->assertEquals( 200, $response->get_status() );
	}


	public function testPostMarkTasksCompleteRequestReturns401ForGuests() {
		$this->login_as( null );

		$response = $this->dispatchRequest( 'POST', '/tasks/complete' );

		$this->assertEquals( 401, $response->get_status() );
	}

	public function testPostMarkTasksCompleteRequestReturns200ForAdmins() {
		$this->login_as_admin();

		$response = $this->dispatchRequest( 'POST', '/tasks/complete' );

		$this->assertEquals( 200, $response->get_status() );
	}

	public function testSenseiProUpsellRedirect_WhenCalled_RedirectsToSenseiProUpsellPage() {
		/* Arrange */
		$this->login_as_admin();
		$redirect_location = '';
		$this->prevent_wp_redirect();

		/* Act */
		try {
			$this->dispatchRequest( 'GET', '/sensei-pro-upsell-redirect' );
		} catch ( Sensei_WP_Redirect_Exception $e ) {
			$redirect_location = $e->getMessage();
		}

		/* Assert */
		$this->assertSame( 'https://senseilms.com/sensei-pro/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=sensei-home', $redirect_location );
		$this->assertTrue( get_option( Sensei_Home_Task_Pro_Upsell::get_id(), false ) );
	}

	public function testSenseiProUpsellRedirect_WhenCalledWithoutLoggingIn_DoesNotRedirect() {
		/* Arrange */
		$redirect_location = '';
		$this->prevent_wp_redirect();

		/* Act */
		try {
			$this->dispatchRequest( 'GET', '/sensei-pro-upsell-redirect' );
		} catch ( Sensei_WP_Redirect_Exception $e ) {
			$redirect_location = $e->getMessage();
		}

		/* Assert */
		$this->assertEmpty( $redirect_location );
		$this->assertFalse( get_option( Sensei_Home_Task_Pro_Upsell::get_id(), false ) );
	}

	public function testSenseiProUpsellRedirect_WhenLoggedInAsNormalUser_DoesNotRedirect() {
		/* Arrange */
		$this->login_as_student();
		$redirect_location = '';
		$this->prevent_wp_redirect();

		/* Act */
		try {
			$this->dispatchRequest( 'GET', '/sensei-pro-upsell-redirect' );
		} catch ( Sensei_WP_Redirect_Exception $e ) {
			$redirect_location = $e->getMessage();
		}

		/* Assert */
		$this->assertEmpty( $redirect_location );
		$this->assertFalse( get_option( Sensei_Home_Task_Pro_Upsell::get_id(), false ) );
	}

	private function dispatchRequest( $method, $route = '' ) {
		// Prevent requests.
		add_filter(
			'pre_http_request',
			function () {
				return [ 'body' => '[]' ];
			}
		);

		$request  = new WP_REST_Request( $method, self::REST_ROUTE . $route );
		$response = $this->server->dispatch( $request );
		remove_all_filters( 'pre_http_request' );

		return $response;
	}
}

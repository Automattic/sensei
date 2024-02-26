<?php
/**
 * Sensei REST API: Sensei_REST_API_Tour_Controller tests
 *
 * @package sensei
 * @since   $$next-version$$
 */

use Sensei\Admin\Tour\Sensei_Tour;

/**
 * Class Sensei_REST_API_Tour_Controller tests.
 *
 * @covers Sensei\Admin\Tour\Sensei_REST_API_Tour_Controller
 */
class Sensei_Rest_Api_Tour_Controller_Test extends \WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	/**
	 * A server instance that we use in tests to dispatch requests.
	 *
	 * @var WP_REST_Server $server
	 */
	protected $server;

	const REST_ROUTE = '/sensei-internal/v1/tour';

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		// Setup REST server for integration tests.
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		$controller     = new \Sensei\Admin\Tour\Sensei_REST_API_Tour_Controller( 'sensei-internal/v1', Sensei_Tour::instance() );

		add_action( 'rest_api_init', [ $controller, 'register_routes' ] );

		do_action( 'rest_api_init' );
	}

	public function tearDown(): void {
		// Unregister REST server.
		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tearDown();
	}

	public function testSetTourCompletionStatus_WhenRequestSent_SetsMetaCorrectly() {
		$this->login_as_teacher();

		$request = new \WP_REST_Request( 'POST', self::REST_ROUTE );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'tour_id'  => 'test_id',
					'complete' => true,
				]
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertTrue( Sensei_Tour::instance()->get_tour_completion_status( 'test_id', get_current_user_id() ) );
		$this->assertFalse( Sensei_Tour::instance()->get_tour_completion_status( 'test_id_1', get_current_user_id() ) );
	}
}

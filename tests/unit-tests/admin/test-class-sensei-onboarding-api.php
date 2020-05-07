<?php
/**
 * Sensei Onboarding REST API tests
 *
 * @package sensei-lms
 * @since   3.1.0
 */

/**
 * Class for Sensei_Onboarding API tests.
 */
class Sensei_Onboarding_API_Test extends WP_Test_REST_TestCase {

	/**
	 * A server instance that we use in tests to dispatch requests.
	 *
	 * @var WP_REST_Server $server
	 */
	protected $server;

	/**
	 * Test specific setup.
	 */
	public function setUp() {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init' );

	}

	/**
	 * Test specific teardown.
	 */
	public function tearDown() {
		parent::tearDown();

		global $wp_rest_server;
		$wp_rest_server = null;

		// Restore Usage tracking option.
		Sensei()->usage_tracking->set_tracking_enabled( true );
	}

	/**
	 * Tests if only privileged users can access the Onboarding API.
	 *
	 * @covers Sensei_Onboarding::handle_api_request
	 */
	public function testOnlyAdminUserCanAccessOnboardingAPI() {

		// Test that a non-admin user cannot access the API.
		$teacher_id = $this->factory->user->create( array( 'role' => 'teacher' ) );
		wp_set_current_user( $teacher_id );

		$request  = new WP_REST_Request( 'GET', '/sensei/v1/onboarding/welcome' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );

		// Test that an administrator with manage_sensei cap can access the API.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$request  = new WP_REST_Request( 'GET', '/sensei/v1/onboarding/welcome' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Tests welcome endpoint returning the current usage tracking setting.
	 *
	 * @covers Sensei_Onboarding::api_welcome_get
	 */
	public function testGetWelcomeReturnsUsageTrackingData() {

		Sensei()->usage_tracking->set_tracking_enabled( true );
		$result = $this->request( 'GET', 'welcome' );

		$this->assertEquals( array( 'usage_tracking' => true ), $result );

		Sensei()->usage_tracking->set_tracking_enabled( false );
		$result = $this->request( 'GET', 'welcome' );

		$this->assertEquals( array( 'usage_tracking' => false ), $result );
	}

	/**
	 * Tests that submitting to welcome endpoint updates usage tracking preference.
	 *
	 * @covers Sensei_Onboarding::api_welcome_submit
	 */
	public function testSubmitWelcomeUpdatesUsageTrackingSetting() {

		Sensei()->usage_tracking->set_tracking_enabled( false );
		$this->request( 'POST', 'welcome', [ 'usage_tracking' => true ] );

		$this->assertEquals( true, Sensei()->usage_tracking->get_tracking_enabled() );
	}

	/**
	 * Tests that submitting to welcome endpoint creates Sensei Courses and My Courses pages.
	 *
	 * @covers Sensei_Onboarding::api_welcome_submit
	 * @covers Sensei_Onboarding_Pages::create_pages
	 */
	public function testSubmitWelcomeCreatesSenseiPages() {

		$this->request( 'POST', 'welcome', [ 'usage_tracking' => false ] );

		$courses_page    = get_page_by_path( 'courses-overview' );
		$my_courses_page = get_page_by_path( 'my-courses' );

		$this->assertNotNull( $courses_page );
		$this->assertNotNull( $my_courses_page );
	}

	/**
	 * Create and dispatch a REST API request.
	 *
	 * @param string $method The request method.
	 * @param string $route  The endpoint under Sensei Onboarding API.
	 * @param array  $data   Request body.
	 *
	 * @return Object Response data.
	 */
	private function request( $method = '', $route = '', $data = null ) {

		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$user = wp_get_current_user();
		$user->add_cap( 'manage_sensei' );

		$request = new WP_REST_Request( $method, '/sensei/v1/onboarding/' . $route );

		if ( null !== $data && 'POST' === $method ) {
			$request->set_header( 'content-type', 'application/json' );
			$request->set_body( wp_json_encode( $data ) );
		}

		return $this->server->dispatch( $request )->get_data();
	}
}

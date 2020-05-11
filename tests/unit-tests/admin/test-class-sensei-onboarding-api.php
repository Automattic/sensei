<?php
/**
 * Sensei Setup Wizard REST API tests
 *
 * @package sensei-lms
 * @since   3.1.0
 */

/**
 * Class for Sensei_Setup_Wizard_API tests.
 */
class Sensei_Setup_Wizard_API_Test extends WP_Test_REST_TestCase {

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
	 * Tests that unprivileged users cannot access the Setup Wizard API.
	 *
	 * @covers Sensei_Setup_Wizard_API::can_user_access_rest_api
	 */
	public function testTeacherUserCannotAccessSetupWizardAPI() {

		$teacher_id = $this->factory->user->create( array( 'role' => 'teacher' ) );
		wp_set_current_user( $teacher_id );

		$request  = new WP_REST_Request( 'GET', '/sensei-internal/v1/onboarding/welcome' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );

	}

	/**
	 * Tests that privileged users can access the Setup Wizard API.
	 *
	 * @covers Sensei_Setup_Wizard_API::can_user_access_rest_api
	 */
	public function testAdminUserCanAccessSetupWizardAPI() {

		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$request  = new WP_REST_Request( 'GET', '/sensei-internal/v1/onboarding/welcome' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Tests welcome endpoint returning the current usage tracking setting.
	 *
	 * @covers Sensei_Setup_Wizard_API::get_welcome
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
	 * @covers Sensei_Setup_Wizard_API::submit_welcome
	 */
	public function testSubmitWelcomeUpdatesUsageTrackingSetting() {

		Sensei()->usage_tracking->set_tracking_enabled( false );
		$this->request( 'POST', 'welcome', [ 'usage_tracking' => true ] );

		$this->assertEquals( true, Sensei()->usage_tracking->get_tracking_enabled() );
	}

	/**
	 * Tests that submitting to welcome endpoint creates Sensei Courses and My Courses pages.
	 *
	 * @covers Sensei_Setup_Wizard_API::submit_welcome
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
	 * Tests that submitting to purpose endpoint saves submitted data
	 *
	 * @covers Sensei_Setup_Wizard_API::submit_purpose
	 */
	public function testSubmitPurposeSavesData() {

		$this->request(
			'POST',
			'purpose',
			[
				'selected' => [ 'share_knowledge', 'other' ],
				'other'    => 'Test',
			]
		);

		$data = Sensei()->onboarding->get_wizard_user_data();

		$this->assertEquals( [ 'share_knowledge', 'other' ], $data['purpose'] );
		$this->assertEquals( 'Test', $data['purpose_other'] );
	}

	/**
	 * Tests that not selecting other clears text value.
	 *
	 * @covers Sensei_Setup_Wizard_API::submit_purpose
	 */
	public function testSubmitPurposeOtherClearedWhenNotSelected() {

		Sensei()->onboarding->update_wizard_user_data(
			[
				'purpose'       => [ 'other' ],
				'purpose_other' => 'Test',
			]
		);

		$this->request(
			'POST',
			'purpose',
			[
				'selected' => [ 'share_knowledge' ],
				'other'    => 'Discard this',
			]
		);

		$data = Sensei()->onboarding->get_wizard_user_data();

		$this->assertEmpty( $data['purpose_other'] );
	}


	/**
	 * Tests that submitting to purpose endpoint validates input against whitelist
	 *
	 * @covers Sensei_Setup_Wizard_API::submit_purpose
	 */
	public function testSubmitPurposeValidated() {

		$this->request(
			'POST',
			'purpose',
			[
				'selected' => [ 'invalid_data' ],
				'other'    => '',
			]
		);

		$data = Sensei()->onboarding->get_wizard_user_data();

		$this->assertNotContains( [ 'invalid_data' ], $data['purpose'] );
	}


	/**
	 * Tests that purpose get endpoint returns user data
	 *
	 * @covers Sensei_Setup_Wizard_API::get_purpose
	 */
	public function testGetPurposeReturnsUserData() {

		Sensei()->onboarding->update_wizard_user_data(
			[
				'purpose'       => [ 'share_knowledge', 'other' ],
				'purpose_other' => 'Test',
			]
		);

		$data = $this->request( 'GET', 'purpose' );

		$this->assertEquals(
			[
				'selected' => [ 'share_knowledge', 'other' ],
				'other'    => 'Test',
			],
			$data
		);
	}


	/**
	 * Create and dispatch a REST API request.
	 *
	 * @param string $method The request method.
	 * @param string $route  The endpoint under Sensei Setup Wizard API.
	 * @param array  $data   Request body.
	 *
	 * @return Object Response data.
	 */
	private function request( $method = '', $route = '', $data = null ) {

		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$user = wp_get_current_user();
		$user->add_cap( 'manage_sensei' );

		$request = new WP_REST_Request( $method, '/sensei-internal/v1/onboarding/' . $route );

		if ( null !== $data && 'POST' === $method ) {
			$request->set_header( 'content-type', 'application/json' );
			$request->set_body( wp_json_encode( $data ) );
		}

		return $this->server->dispatch( $request )->get_data();
	}
}

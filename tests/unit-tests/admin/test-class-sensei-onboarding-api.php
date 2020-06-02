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

		Sensei_Test_Events::reset();
		do_action( 'rest_api_init' );

		// Prevent requests.
		add_filter( 'pre_http_request', '__return_empty_array' );
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
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::can_user_access_rest_api
	 */
	public function testTeacherUserCannotAccessSetupWizardAPI() {

		$teacher_id = $this->factory->user->create( array( 'role' => 'teacher' ) );
		wp_set_current_user( $teacher_id );

		$request  = new WP_REST_Request( 'GET', '/sensei-internal/v1/setup-wizard' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );

	}

	/**
	 * Tests that privileged users can access the Setup Wizard API.
	 *
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::can_user_access_rest_api
	 */
	public function testAdminUserCanAccessSetupWizardAPI() {

		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$request  = new WP_REST_Request( 'GET', '/sensei-internal/v1/setup-wizard' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Tests welcome endpoint returning the current usage tracking setting.
	 *
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::get_data
	 */
	public function testGetWelcomeReturnsUsageTrackingData() {

		Sensei()->usage_tracking->set_tracking_enabled( true );
		$result = $this->request( 'GET', '' );

		$this->assertEquals( array( 'usage_tracking' => true ), $result['welcome'] );

		Sensei()->usage_tracking->set_tracking_enabled( false );
		$result = $this->request( 'GET', '' );

		$this->assertEquals( array( 'usage_tracking' => false ), $result['welcome'] );
	}

	/**
	 * Tests that submitting to welcome endpoint updates usage tracking preference.
	 *
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::submit_welcome
	 */
	public function testSubmitWelcomeUpdatesUsageTrackingSetting() {

		Sensei()->usage_tracking->set_tracking_enabled( false );
		$this->request( 'POST', 'welcome', [ 'usage_tracking' => true ] );

		$this->assertEquals( true, Sensei()->usage_tracking->get_tracking_enabled() );
	}

	/**
	 * Tests that submitting to welcome endpoint creates Sensei Courses and My Courses pages.
	 *
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::submit_welcome
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
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::submit_purpose
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

		$this->assertEquals( [ 'share_knowledge', 'other' ], $data['purpose']['selected'] );
		$this->assertEquals( 'Test', $data['purpose']['other'] );
	}

	/**
	 * Tests that not selecting other clears text value.
	 *
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::submit_purpose
	 */
	public function testSubmitPurposeOtherClearedWhenNotSelected() {

		Sensei()->onboarding->update_wizard_user_data(
			[
				'purpose' => [
					'selected' => [ 'other' ],
					'other'    => 'Test',
				],
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

		$this->assertEmpty( $data['purpose']['other'] );
	}


	/**
	 * Tests that submitting to purpose endpoint validates input against whitelist
	 *
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::submit_purpose
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
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::get_data
	 */
	public function testGetPurposeReturnsUserData() {

		Sensei()->onboarding->update_wizard_user_data(
			[
				'purpose' => [
					'selected' => [ 'share_knowledge', 'other' ],
					'other'    => 'Test',
				],
			]
		);

		$data = $this->request( 'GET', '' );

		$this->assertEquals(
			[
				'selected' => [ 'share_knowledge', 'other' ],
				'other'    => 'Test',
			],
			$data['purpose']
		);
	}

	/**
	 * Tests that completed steps are empty when nothing has been submitted.
	 *
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::get_data
	 */
	public function testDefaultProgressIsEmpty() {
		$data = $this->request( 'GET', '' );
		$this->assertEquals( [], $data['completedSteps'] );
	}

	/**
	 * Tests that welcome step is completed after submitting it.
	 *
	 * @dataProvider step_form_data
	 * @covers       Sensei_REST_API_Setup_Wizard_Controller::submit_welcome
	 * @covers       Sensei_REST_API_Setup_Wizard_Controller::submit_purpose
	 * @covers       Sensei_REST_API_Setup_Wizard_Controller::submit_features
	 *
	 * @param string $step      Step.
	 * @param mixed  $form_data Data submitted.
	 */
	public function testStepCompletedAfterSubmit( $step, $form_data ) {
		$this->request( 'POST', $step, $form_data );
		$data = $this->request( 'GET', '' );
		$this->assertEquals( [ $step ], $data['completedSteps'] );
	}

	public function testMultipleStepsCompleted() {

		$steps_data = $this->step_form_data();

		foreach ( $steps_data as $step_data ) {
			list( $step, $form_data ) = $step_data;
			$this->request( 'POST', $step, $form_data );
		}

		$data = $this->request( 'GET', '' );
		$this->assertEqualSets( [ 'welcome', 'features', 'purpose' ], $data['completedSteps'] );

	}


	/**
	 * Tests that submitting to features endpoint saves submitted data
	 *
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::submit_features
	 */
	public function testSubmitFeaturesSavesData() {

		$this->request(
			'POST',
			'features',
			[
				'selected' => [ 'sensei-certificates' ],
			]
		);

		$data = Sensei()->onboarding->get_wizard_user_data();

		$this->assertEquals( [ 'selected' => [ 'sensei-certificates' ] ], $data['features'] );
	}

	/**
	 * Tests that submitting to features endpoint validates input against whitelist
	 *
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::submit_purpose
	 */
	public function testSubmitPurposeLogged() {

		$this->request(
			'POST',
			'purpose',
			[
				'selected' => [ 'share_knowledge', 'other' ],
				'other'    => 'Test',
			]
		);

		$events = Sensei_Test_Events::get_logged_events( 'sensei_setup_wizard_purpose_continue' );
		$this->assertCount( 1, $events );
		$this->assertEquals( 'share_knowledge,other', $events[0]['url_args']['purpose'] );
		$this->assertEquals( 'Test', $events[0]['url_args']['purpose_details'] );
	}

	/**
	 * Tests that features get endpoint returns fetched data.
	 *
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::get_features_data
	 */
	public function testGetFeaturesReturnsFetchedData() {
		// Mock fetch from senseilms.com.
		$response_body = '{ "products": [ { "product_slug": "slug-1", "plugin_file": "test/test.php" } ] }';
		add_filter(
			'pre_http_request',
			function() use ( $response_body ) {
				return [ 'body' => $response_body ];
			}
		);

		$data = $this->request( 'GET', 'features' );

		$expected_data = [
			'options'  => [
				(object) [
					'product_slug' => 'slug-1',
					'plugin_file'  => 'test/test.php',
				],
			],
			'selected' => [],
		];

		$this->assertEquals( $data, $expected_data );
	}

	/**
	 * Tests that submitting features installation starts installation.
	 *
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::submit_features_installation
	 */
	public function testSubmitFeaturesInstallation() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Skip test for multisite because user will not have the needed permissions.' );
		}

		// Mock fetch from senseilms.com.
		$response_body = '{ "products": [ { "product_slug": "slug-1", "plugin_file": "test/test.php" } ] }';
		add_filter(
			'pre_http_request',
			function() use ( $response_body ) {
				return [ 'body' => $response_body ];
			}
		);

		// Create user with needed capabilities.
		$user_id = $this->factory->user->create();
		$user    = get_user_by( 'id', $user_id );

		$user->add_cap( 'manage_sensei' );
		$user->add_cap( 'install_plugins' );
		wp_set_current_user( $user_id );

		$this->request( 'POST', 'features-installation', [ 'selected' => [ 'slug-1' ] ], $user );

		$expected_extensions = [
			(object) [
				'product_slug' => 'slug-1',
				'plugin_file'  => 'test/test.php',
				'status'       => 'installing',
			],
		];
		$sensei_extensions   = Sensei()->onboarding->get_sensei_extensions();

		$this->assertEquals( $expected_extensions, $sensei_extensions );
	}

	/**
	 * Tests that user cannot install features without capability.
	 *
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::can_user_install_plugins
	 */
	public function testUserCannotInstallFeaturesWithoutCapability() {
		$user_id = $this->factory->user->create();
		$user    = get_user_by( 'id', $user_id );

		$user->add_cap( 'manage_sensei' ); // Without install_plugins capability.
		wp_set_current_user( $user_id );

		$this->assertEquals( current_user_can( 'install_plugins' ), false );

		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/setup-wizard/features-installation' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( [ 'selected' => [] ] ) );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Tests that the complete wizard endpoint clears setup wizard prompts.
	 *
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::complete_setup_wizard
	 */
	public function testCompleteWizardUpdatesOption() {

		update_option( 'sensei_suggest_setup_wizard', 1 );
		$this->request( 'POST', 'ready' );

		$this->assertEquals( 0, get_option( 'sensei_suggest_setup_wizard' ) );
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
	private function request( $method = '', $route = '', $data = null, $user = null ) {

		if ( ! $user ) {
			$admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
			wp_set_current_user( $admin_id );
			$user = wp_get_current_user();
			$user->add_cap( 'manage_sensei' );
		}

		$request = new WP_REST_Request( $method, rtrim( '/sensei-internal/v1/setup-wizard/' . $route, '/' ) );

		if ( null !== $data && 'POST' === $method ) {
			$request->set_header( 'content-type', 'application/json' );
			$request->set_body( wp_json_encode( $data ) );
		}

		return $this->server->dispatch( $request )->get_data();
	}

	/**
	 * Valid form data for step submissions.
	 *
	 * @access private
	 * @return array
	 */
	public function step_form_data() {
		return [
			'Welcome'  => [ 'welcome', [ 'usage_tracking' => true ] ],
			'Purpose'  => [
				'purpose',
				[
					'selected' => [ 'share_knowledge', 'other' ],
					'other'    => 'Test',
				],
			],
			'Features' => [ 'features', [ 'selected' => [ 'sensei-certificates' ] ] ],
		];
	}
}

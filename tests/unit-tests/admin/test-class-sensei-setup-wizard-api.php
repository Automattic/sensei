<?php
/**
 * Sensei Setup Wizard REST API tests
 *
 * @package sensei-lms
 * @since   3.1.0
 */

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';

/**
 * Class for Sensei_Setup_Wizard_API tests.
 */
class Sensei_Setup_Wizard_API_Test extends WP_Test_REST_TestCase {
	use Sensei_Course_Enrolment_Test_Helpers;

	/**
	 * A server instance that we use in tests to dispatch requests.
	 *
	 * @var WP_REST_Server $server
	 */
	protected $server;

	/**
	 * Set up before the class.
	 */
	public static function setUpBeforeClass(): void {
		// Mock WooCommerce plugin information.
		set_transient(
			Sensei_Utils::WC_INFORMATION_TRANSIENT,
			(object) [
				'product_slug' => 'woocommerce',
				'title'        => 'WooCommerce',
				'excerpt'      => 'Lorem ipsum',
				'plugin_file'  => 'woocommerce/woocommerce.php',
				'link'         => 'https://wordpress.org/plugins/woocommerce',
				'unselectable' => true,
				'version'      => '4.0.0',
			],
			DAY_IN_SECONDS
		);
	}

	/**
	 * Test specific setup.
	 */
	public function setUp(): void {
		parent::setUp();

		self::resetEnrolmentProviders();
		$this->prepareEnrolmentManager();

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
	public function tearDown(): void {
		parent::tearDown();

		global $wp_rest_server;
		$wp_rest_server = null;

		// Restore Usage tracking option.
		Sensei()->usage_tracking->set_tracking_enabled( true );
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		self::resetEnrolmentProviders();
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
	 * Tests that submitting to welcome endpoint creates Sensei Courses and My Courses pages.
	 *
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::submit_welcome
	 * @covers Sensei_Setup_Wizard_Pages::create_pages
	 */
	public function testSubmitWelcomeCreatesSenseiPages() {

		$this->request( 'POST', 'welcome', [ 'usage_tracking' => false ] );

		$courses_page          = get_page_by_path( 'courses-overview' );
		$my_courses_page       = get_page_by_path( 'my-courses' );
		$course_completed_page = get_page_by_path( 'course-completed' );

		$this->assertNotNull( $courses_page, 'Course archive page' );
		$this->assertNotNull( $my_courses_page, 'My Courses page' );
		$this->assertNotNull( $course_completed_page, 'Course completed page' );
	}

	public function testMyCoursesPage_WhenCreated_ContainsQueryListBlock() {

		$this->request( 'POST', 'welcome', [ 'usage_tracking' => false ] );

		$my_courses_page = get_page_by_path( 'my-courses' );

		$this->assertStringContainsString( 'wp:query', $my_courses_page->post_content );
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
				'purpose'  => [
					'selected' => [ 'sell_courses', 'other' ],
					'other'    => 'Test',
				],
				'features' => [
					'selected' => [ 'woocommerce' ],
				],
			]
		);

		$data = Sensei()->setup_wizard->get_wizard_user_data();

		$this->assertEquals( [ 'sell_courses', 'other' ], $data['purpose']['selected'] );
		$this->assertEquals( 'Test', $data['purpose']['other'] );
		$this->assertEquals( [ 'woocommerce' ], $data['features']['selected'] );
	}

	/**
	 * Tests that not selecting other clears text value.
	 *
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::submit_purpose
	 */
	public function testSubmitPurposeOtherClearedWhenNotSelected() {

		Sensei()->setup_wizard->update_wizard_user_data(
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
				'purpose' => [
					'selected' => [ 'sell_courses' ],
					'other'    => 'Discard this',
				],
			]
		);

		$data = Sensei()->setup_wizard->get_wizard_user_data();

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
				'purpose'  => [
					'selected' => [ 'invalid_data' ],
					'other'    => '',
				],
				'features' => [
					'selected' => [ 'invalid_data' ],
				],
			]
		);

		$data = Sensei()->setup_wizard->get_wizard_user_data();

		$this->assertEmpty( $data['purpose']['selected'] );
		$this->assertEmpty( $data['features']['selected'] );
	}


	/**
	 * Tests that purpose get endpoint returns user data
	 *
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::get_data
	 */
	public function testGetPurposeReturnsUserData() {

		Sensei()->setup_wizard->update_wizard_user_data(
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
	 * Tests that theme get endpoint returns the install sensei theme option.
	 *
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::get_data
	 */
	public function testGetThemeReturnsUserData() {

		Sensei()->setup_wizard->update_wizard_user_data(
			[ 'theme' => [ 'install_sensei_theme' => true ] ]
		);

		$data = $this->request( 'GET', '' );

		$this->assertEquals(
			[ 'install_sensei_theme' => true ],
			$data['theme']
		);
	}

	/**
	 * Tests that submitting to theme endpoint updates the install sensei theme option.
	 *
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::submit_theme
	 */
	public function testSubmitThemeUpdatesInstallSenseiThemeOption() {

		Sensei()->usage_tracking->set_tracking_enabled( false );
		$this->request( 'POST', 'theme', [ 'theme' => [ 'install_sensei_theme' => true ] ] );

		$data = $this->request( 'GET', '' );

		$this->assertEquals(
			[ 'install_sensei_theme' => true ],
			$data['theme']
		);
	}

	/**
	 * Tests tracking endpoint returning the current usage tracking setting.
	 *
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::get_data
	 */
	public function testGetTrackingReturnsUsageTrackingData() {

		Sensei()->usage_tracking->set_tracking_enabled( true );
		$result = $this->request( 'GET', '' );

		$this->assertEquals( array( 'usage_tracking' => true ), $result['tracking'] );

		Sensei()->usage_tracking->set_tracking_enabled( false );
		$result = $this->request( 'GET', '' );

		$this->assertEquals( array( 'usage_tracking' => false ), $result['tracking'] );
	}

	/**
	 * Tests that submitting to tracking endpoint updates usage tracking preference.
	 *
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::submit_tracking
	 */
	public function testSubmitTrackingUpdatesUsageTrackingSetting() {

		$this->request(
			'POST',
			'purpose',
			[
				'purpose' => [
					'selected' => [ 'sell_courses', 'other' ],
					'other'    => 'Test',
				],
			]
		);

		Sensei()->usage_tracking->set_tracking_enabled( false );
		$this->request( 'POST', 'tracking', [ 'tracking' => [ 'usage_tracking' => true ] ] );

		$this->assertEquals( true, Sensei()->usage_tracking->get_tracking_enabled() );

		$events = Sensei_Test_Events::get_logged_events( 'sensei_setup_wizard_purpose_continue' );
		$this->assertCount( 1, $events );
		$this->assertEquals( 'sell_courses,other', $events[0]['url_args']['purpose'] );
		$this->assertEquals( 'Test', $events[0]['url_args']['purpose_details'] );
	}

	/**
	 * Tests that the submit features clears setup wizard prompts.
	 *
	 * @covers Sensei_REST_API_Setup_Wizard_Controller::submit_features
	 */
	public function testSubmitFeatures() {
		update_option( 'sensei_suggest_setup_wizard', 1 );
		$this->request( 'POST', 'features' );

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

	public function testCourseArchivePage_WhenCreated_ContainsQueryListBlock() {

		$this->request( 'POST', 'welcome', [ 'usage_tracking' => false ] );

		$my_courses_page = get_page_by_path( 'courses-overview' );

		$this->assertEquals( 'courses-overview', $my_courses_page->post_name );
		$this->assertStringContainsString( 'wp:query', $my_courses_page->post_content );
	}
}

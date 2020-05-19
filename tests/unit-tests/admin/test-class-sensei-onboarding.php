<?php
/**
 * This file contains the Sensei_Onboarding_Test class.
 *
 * @package sensei
 */

/**
 * Tests for Sensei_Onboarding_Test class.
 *
 * @group onboarding
 */
class Sensei_Onboarding_Test extends WP_UnitTestCase {
	/**
	 * Set up before each test.
	 */
	public function setup() {
		parent::setup();

		// Save original current screen.
		global $current_screen;
		$this->original_screen = $current_screen;
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown() {
		parent::tearDown();

		// Restore current screen.
		global $current_screen;
		$current_screen = $this->original_screen;
	}

	/**
	 * Testing the onboarding class to make sure it is loaded.
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( 'Sensei_Onboarding' ), 'Sensei Onboarding class does not exist' );
	}

	/**
	 * Test setup wizard notice in dashboard.
	 *
	 * @covers Sensei_Onboarding::setup_wizard_notice
	 * @covers Sensei_Onboarding::should_current_page_display_setup_wizard
	 */
	public function testSetupWizardNoticeInDashboard() {
		// Create and login as admin.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'dashboard' );
		update_option( \Sensei_Onboarding::SUGGEST_SETUP_WIZARD_OPTION, 1 );

		ob_start();
		Sensei()->onboarding->setup_wizard_notice();
		$html = ob_get_clean();

		$pos_setup_button = strpos( $html, 'Run the Setup Wizard' );

		$this->assertNotFalse( $pos_setup_button, 'Should return the notice HTML' );
	}

	/**
	 * Test setup wizard notice in screen with Sensei prefix.
	 *
	 * @covers Sensei_Onboarding::setup_wizard_notice
	 * @covers Sensei_Onboarding::should_current_page_display_setup_wizard
	 */
	public function testSetupWizardNoticeInSenseiScreen() {
		// Create and login as admin.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'sensei-lms_page_sensei_test' );
		update_option( \Sensei_Onboarding::SUGGEST_SETUP_WIZARD_OPTION, 1 );

		ob_start();
		Sensei()->onboarding->setup_wizard_notice();
		$html = ob_get_clean();

		$pos_setup_button = strpos( $html, 'Run the Setup Wizard' );

		$this->assertNotFalse( $pos_setup_button, 'Should return the notice HTML' );
	}

	/**
	 * Test setup wizard notice in no Sensei screen.
	 *
	 * @covers Sensei_Onboarding::setup_wizard_notice
	 * @covers Sensei_Onboarding::should_current_page_display_setup_wizard
	 */
	public function testSetupWizardNoticeInOtherScreen() {
		// Create and login as admin.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'other' );
		update_option( \Sensei_Onboarding::SUGGEST_SETUP_WIZARD_OPTION, 1 );

		ob_start();
		Sensei()->onboarding->setup_wizard_notice();
		$html = ob_get_clean();

		$this->assertEmpty( $html, 'Should return empty string' );
	}

	/**
	 * Test setup wizard notice with suggest option as 0.
	 *
	 * @covers Sensei_Onboarding::setup_wizard_notice
	 * @covers Sensei_Onboarding::should_current_page_display_setup_wizard
	 */
	public function testSetupWizardNoticeSuggestOptionAsZero() {
		// Create and login as admin.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'dashboard' );
		update_option( \Sensei_Onboarding::SUGGEST_SETUP_WIZARD_OPTION, 0 );

		ob_start();
		Sensei()->onboarding->setup_wizard_notice();
		$html = ob_get_clean();

		$this->assertEmpty( $html, 'Should return empty string' );
	}

	/**
	 * Test setup wizard notice with suggest option empty.
	 *
	 * @covers Sensei_Onboarding::setup_wizard_notice
	 * @covers Sensei_Onboarding::should_current_page_display_setup_wizard
	 */
	public function testSetupWizardNoticeSuggestOptionEmpty() {
		// Create and login as admin.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'dashboard' );

		ob_start();
		Sensei()->onboarding->setup_wizard_notice();
		$html = ob_get_clean();

		$this->assertEmpty( $html, 'Should return empty string' );
	}

	/**
	 * Test setup wizard notice for no admin user.
	 *
	 * @covers Sensei_Onboarding::setup_wizard_notice
	 * @covers Sensei_Onboarding::should_current_page_display_setup_wizard
	 */
	public function testSetupWizardNoticeNoAdmin() {
		// Create and login as teacher.
		$teacher_id = $this->factory->user->create( array( 'role' => 'teacher' ) );
		wp_set_current_user( $teacher_id );

		set_current_screen( 'dashboard' );
		update_option( \Sensei_Onboarding::SUGGEST_SETUP_WIZARD_OPTION, 0 );

		ob_start();
		Sensei()->onboarding->setup_wizard_notice();
		$html = ob_get_clean();

		$this->assertEmpty( $html, 'Should return empty string' );
	}

	/**
	 * Test skip setup wizard.
	 *
	 * @covers Sensei_Onboarding::skip_setup_wizard
	 */
	public function testSkipSetupWizard() {
		// Create and login as admin.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$_GET['sensei_skip_setup_wizard'] = '1';
		$_GET['_wpnonce']                 = wp_create_nonce( 'sensei_skip_setup_wizard' );

		Sensei()->onboarding->skip_setup_wizard();
		$option_value = get_option( \Sensei_Onboarding::SUGGEST_SETUP_WIZARD_OPTION, false );

		$this->assertEquals( '0', $option_value, 'Should update option to 0' );
	}

	/**
	 * Test skip setup wizard.
	 *
	 * @covers Sensei_Onboarding::skip_setup_wizard
	 */
	public function testSkipSetupWizardNoAdmin() {
		// Create and login as teacher.
		$teacher_id = $this->factory->user->create( array( 'role' => 'teacher' ) );
		wp_set_current_user( $teacher_id );

		$_GET['sensei_skip_setup_wizard'] = '1';
		$_GET['_wpnonce']                 = wp_create_nonce( 'sensei_skip_setup_wizard' );

		Sensei()->onboarding->skip_setup_wizard();
		$option_value = get_option( \Sensei_Onboarding::SUGGEST_SETUP_WIZARD_OPTION, false );

		$this->assertFalse( $option_value, 'Should not update option' );
	}

	/*
	 * Testing if activation redirect works properly.
	 */
	public function testActivationRedirect() {
		// Create and login as administrator.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_transient( 'sensei_activation_redirect', 1, 30 );

		$onboarding_mock = $this->getMockBuilder( 'Sensei_Onboarding' )
			->setMethods( [ 'redirect_to_setup_wizard' ] )
			->getMock();

		$onboarding_mock->expects( $this->once() )
			->method( 'redirect_to_setup_wizard' );

		$onboarding_mock->activation_redirect();

		$this->assertFalse( get_transient( 'sensei_activation_redirect' ), 'Transient should be removed' );
	}

	/**
	 * Testing if activation doesn't redirect for no admin user.
	 */
	public function testActivationRedirectNoAdmin() {
		// Create and login as subscriber.
		$subscriber_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		set_transient( 'sensei_activation_redirect', 1, 30 );

		$onboarding_mock = $this->getMockBuilder( 'Sensei_Onboarding' )
			->setMethods( [ 'redirect_to_setup_wizard' ] )
			->getMock();

		$onboarding_mock->expects( $this->never() )
			->method( 'redirect_to_setup_wizard' );

		$onboarding_mock->activation_redirect();

		$this->assertNotFalse( get_transient( 'sensei_activation_redirect' ), 'Transient should not be removed' );
	}

	/**
	 * Testing if activation doesn't redirect when transient is not defined.
	 */
	public function testActivationRedirectWithoutTransient() {
		// Create and login as administrator.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$onboarding_mock = $this->getMockBuilder( 'Sensei_Onboarding' )
			->setMethods( [ 'redirect_to_setup_wizard' ] )
			->getMock();

		$onboarding_mock->expects( $this->never() )
			->method( 'redirect_to_setup_wizard' );

		$onboarding_mock->activation_redirect();
	}

	/**
	 * Test if WooCommerce help tab is being prevented in the Sensei pages.
	 *
	 * @covers Sensei_Onboarding::should_enable_woocommerce_help_tab
	 */
	public function testShouldEnableWooCommerceHelpTab() {
		$_GET['post_type'] = 'course';

		$this->assertFalse(
			Sensei()->onboarding->should_enable_woocommerce_help_tab( true ),
			'Should not allow WooCommerce help tab for course post type'
		);
	}

	/**
	 * Test if WooCommerce help tab is being untouched in no Sensei pages.
	 *
	 * @covers Sensei_Onboarding::should_enable_woocommerce_help_tab
	 */
	public function testShouldEnableWooCommerceHelpTabNoSenseiPage() {
		$_GET['post_type'] = 'woocommerce';

		$this->assertTrue(
			Sensei()->onboarding->should_enable_woocommerce_help_tab( true ),
			'Should not touch WooCommerce help tab for no Sensei pages'
		);
	}

	/**
	 * Test add setup wizard help tab to edit course screen.
	 *
	 * @covers Sensei_Onboarding::add_setup_wizard_help_tab
	 */
	public function testAddSetupWizardHelpTab() {
		// Create and login as administrator.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'edit-course' );
		$screen = get_current_screen();

		$screen->remove_help_tab( 'sensei_lms_setup_wizard_tab' );
		Sensei()->onboarding->add_setup_wizard_help_tab( $screen );
		$created_tab = $screen->get_help_tab( 'sensei_lms_setup_wizard_tab' );

		$this->assertNotNull( $created_tab, 'Should create the setup wizard tab to edit course screens.' );
	}

	/**
	 * Test add setup wizard help tab in non edit course screens.
	 *
	 * @covers Sensei_Onboarding::add_setup_wizard_help_tab
	 */
	public function testAddSetupWizardHelpTabNonEditCourseScreen() {
		// Create and login as administrator.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'edit-lesson' );
		$screen = get_current_screen();

		$screen->remove_help_tab( 'sensei_lms_setup_wizard_tab' );
		Sensei()->onboarding->add_setup_wizard_help_tab( $screen );
		$created_tab = $screen->get_help_tab( 'sensei_lms_setup_wizard_tab' );

		$this->assertNull( $created_tab, 'Should not create the setup wizard tab to non edit course screens.' );
	}

	/**
	 * Test add setup wizard help tab for no admin user.
	 *
	 * @covers Sensei_Onboarding::add_setup_wizard_help_tab
	 */
	public function testAddSetupWizardHelpTabNoAdmin() {
		// Create and login as teacher.
		$teacher_id = $this->factory->user->create( array( 'role' => 'teacher' ) );
		wp_set_current_user( $teacher_id );

		set_current_screen( 'edit-course' );
		$screen = get_current_screen();

		$screen->remove_help_tab( 'sensei_lms_setup_wizard_tab' );
		Sensei()->onboarding->add_setup_wizard_help_tab( $screen );
		$created_tab = $screen->get_help_tab( 'sensei_lms_setup_wizard_tab' );

		$this->assertNull( $created_tab, 'Should not create the setup wizard tab to no admin user.' );
	}

	/**
	 * Tests that get sensei extensions returns normalized and overriden object.
	 *
	 * @covers Sensei_Onboarding::get_sensei_extensions
	 * @covers Sensei_Onboarding::normalize_sensei_extensions
	 * @covers Sensei_Onboarding::override_sensei_extensions
	 */
	public function testGetSenseiExtensionsReturnsNormalizedAndOverridenObject() {
		$expected_extensions = [
			[
				'id'            => 'slug-1',
				'title'         => 'Title 1',
				'description'   => 'Excerpt 1',
				'learnMoreLink' => 'https://senseilms.com/product/test-1/',
				'price'         => '$1.00',
				'plugin_file'   => 'path/file-1.php',
			],
			[
				'id'    => 'slug-2',
				'price' => 0,
			],
			[
				'id'     => 'sensei-certificates',
				'title'  => 'Certificates',
				'status' => 'error',
			],
		];

		$response_body = '{
			"products": [
				{
					"product_slug": "slug-1",
					"title": "Title 1",
					"excerpt": "Excerpt 1",
					"link": "https:\/\/senseilms.com\/product\/test-1\/",
					"price": "&#36;1.00",
					"plugin_file": "path\/file-1.php"
				},
				{
					"product_slug": "slug-2",
					"price": 0
				},
				{
					"product_slug": "sensei-certificates",
					"title": "Sensei Certificates"
				}
			]
		}';

		// Mock fetch from senseilms.com.
		add_filter(
			'pre_http_request',
			function() use ( $response_body ) {
				return [ 'body' => $response_body ];
			}
		);

		$extensions = Sensei()->onboarding->get_sensei_extensions();

		$this->assertEquals( $expected_extensions, $extensions );

		$transient_extensions = get_transient( \Sensei_Onboarding::EXTENSIONS_TRANSIENT );
		$this->assertEquals( $expected_extensions, $transient_extensions );
	}
}

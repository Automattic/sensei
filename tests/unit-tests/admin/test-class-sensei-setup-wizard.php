<?php
/**
 * This file contains the Sensei_Setup_Wizard_Test class.
 *
 * @package sensei
 */

/**
 * Tests for Sensei_Setup_Wizard_Test class.
 *
 * @group setup_wizard
 */
class Sensei_Setup_Wizard_Test extends WP_UnitTestCase {
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
	 * Testing the setup wizard class to make sure it is loaded.
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( 'Sensei_Setup_Wizard' ), 'Sensei Setup Wizard class does not exist' );
	}

	/**
	 * Test setup wizard notice in dashboard.
	 *
	 * @covers Sensei_Setup_Wizard::setup_wizard_notice
	 * @covers Sensei_Setup_Wizard::should_current_page_display_setup_wizard
	 */
	public function testSetupWizardNoticeInDashboard() {
		// Create and login as admin.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'dashboard' );
		update_option( \Sensei_Setup_Wizard::SUGGEST_SETUP_WIZARD_OPTION, 1 );

		ob_start();
		Sensei()->setup_wizard->setup_wizard_notice();
		$html = ob_get_clean();

		$pos_setup_button = strpos( $html, 'Run the Setup Wizard' );

		$this->assertNotFalse( $pos_setup_button, 'Should return the notice HTML' );
	}

	/**
	 * Test setup wizard notice in screen with Sensei prefix.
	 *
	 * @covers Sensei_Setup_Wizard::setup_wizard_notice
	 * @covers Sensei_Setup_Wizard::should_current_page_display_setup_wizard
	 */
	public function testSetupWizardNoticeInSenseiScreen() {
		// Create and login as admin.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'sensei-lms_page_sensei_test' );
		update_option( \Sensei_Setup_Wizard::SUGGEST_SETUP_WIZARD_OPTION, 1 );

		ob_start();
		Sensei()->setup_wizard->setup_wizard_notice();
		$html = ob_get_clean();

		$pos_setup_button = strpos( $html, 'Run the Setup Wizard' );

		$this->assertNotFalse( $pos_setup_button, 'Should return the notice HTML' );
	}

	/**
	 * Test setup wizard notice in no Sensei screen.
	 *
	 * @covers Sensei_Setup_Wizard::setup_wizard_notice
	 * @covers Sensei_Setup_Wizard::should_current_page_display_setup_wizard
	 */
	public function testSetupWizardNoticeInOtherScreen() {
		// Create and login as admin.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'other' );
		update_option( \Sensei_Setup_Wizard::SUGGEST_SETUP_WIZARD_OPTION, 1 );

		ob_start();
		Sensei()->setup_wizard->setup_wizard_notice();
		$html = ob_get_clean();

		$this->assertEmpty( $html, 'Should return empty string' );
	}

	/**
	 * Test setup wizard notice with suggest option as 0.
	 *
	 * @covers Sensei_Setup_Wizard::setup_wizard_notice
	 * @covers Sensei_Setup_Wizard::should_current_page_display_setup_wizard
	 */
	public function testSetupWizardNoticeSuggestOptionAsZero() {
		// Create and login as admin.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'dashboard' );
		update_option( \Sensei_Setup_Wizard::SUGGEST_SETUP_WIZARD_OPTION, 0 );

		ob_start();
		Sensei()->setup_wizard->setup_wizard_notice();
		$html = ob_get_clean();

		$this->assertEmpty( $html, 'Should return empty string' );
	}

	/**
	 * Test setup wizard notice with suggest option empty.
	 *
	 * @covers Sensei_Setup_Wizard::setup_wizard_notice
	 * @covers Sensei_Setup_Wizard::should_current_page_display_setup_wizard
	 */
	public function testSetupWizardNoticeSuggestOptionEmpty() {
		// Create and login as admin.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'dashboard' );

		ob_start();
		Sensei()->setup_wizard->setup_wizard_notice();
		$html = ob_get_clean();

		$this->assertEmpty( $html, 'Should return empty string' );
	}

	/**
	 * Test setup wizard notice for no admin user.
	 *
	 * @covers Sensei_Setup_Wizard::setup_wizard_notice
	 * @covers Sensei_Setup_Wizard::should_current_page_display_setup_wizard
	 */
	public function testSetupWizardNoticeNoAdmin() {
		// Create and login as teacher.
		$teacher_id = $this->factory->user->create( array( 'role' => 'teacher' ) );
		wp_set_current_user( $teacher_id );

		set_current_screen( 'dashboard' );
		update_option( \Sensei_Setup_Wizard::SUGGEST_SETUP_WIZARD_OPTION, 0 );

		ob_start();
		Sensei()->setup_wizard->setup_wizard_notice();
		$html = ob_get_clean();

		$this->assertEmpty( $html, 'Should return empty string' );
	}

	/**
	 * Test skip setup wizard.
	 *
	 * @covers Sensei_Setup_Wizard::skip_setup_wizard
	 */
	public function testSkipSetupWizard() {
		// Create and login as admin.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$_GET['sensei_skip_setup_wizard'] = '1';
		$_GET['_wpnonce']                 = wp_create_nonce( 'sensei_skip_setup_wizard' );

		Sensei()->setup_wizard->skip_setup_wizard();
		$option_value = get_option( \Sensei_Setup_Wizard::SUGGEST_SETUP_WIZARD_OPTION, false );

		$this->assertEquals( '0', $option_value, 'Should update option to 0' );
	}

	/**
	 * Test skip setup wizard.
	 *
	 * @covers Sensei_Setup_Wizard::skip_setup_wizard
	 */
	public function testSkipSetupWizardNoAdmin() {
		// Create and login as teacher.
		$teacher_id = $this->factory->user->create( array( 'role' => 'teacher' ) );
		wp_set_current_user( $teacher_id );

		$_GET['sensei_skip_setup_wizard'] = '1';
		$_GET['_wpnonce']                 = wp_create_nonce( 'sensei_skip_setup_wizard' );

		Sensei()->setup_wizard->skip_setup_wizard();
		$option_value = get_option( \Sensei_Setup_Wizard::SUGGEST_SETUP_WIZARD_OPTION, false );

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

		$setup_wizard_mock = $this->getMockBuilder( 'Sensei_Setup_Wizard' )
			->setMethods( [ 'redirect_to_setup_wizard' ] )
			->getMock();

		$setup_wizard_mock->expects( $this->once() )
			->method( 'redirect_to_setup_wizard' );

		$setup_wizard_mock->activation_redirect();

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

		$setup_wizard_mock = $this->getMockBuilder( 'Sensei_Setup_Wizard' )
			->setMethods( [ 'redirect_to_setup_wizard' ] )
			->getMock();

		$setup_wizard_mock->expects( $this->never() )
			->method( 'redirect_to_setup_wizard' );

		$setup_wizard_mock->activation_redirect();

		$this->assertNotFalse( get_transient( 'sensei_activation_redirect' ), 'Transient should not be removed' );
	}

	/**
	 * Testing if activation doesn't redirect when transient is not defined.
	 */
	public function testActivationRedirectWithoutTransient() {
		// Create and login as administrator.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$setup_wizard_mock = $this->getMockBuilder( 'Sensei_Setup_Wizard' )
			->setMethods( [ 'redirect_to_setup_wizard' ] )
			->getMock();

		$setup_wizard_mock->expects( $this->never() )
			->method( 'redirect_to_setup_wizard' );

		$setup_wizard_mock->activation_redirect();
	}

	/**
	 * Test if WooCommerce help tab is being prevented in the Sensei pages.
	 *
	 * @covers Sensei_Setup_Wizard::should_enable_woocommerce_help_tab
	 */
	public function testShouldEnableWooCommerceHelpTab() {
		$_GET['post_type'] = 'course';

		$this->assertFalse(
			Sensei()->setup_wizard->should_enable_woocommerce_help_tab( true ),
			'Should not allow WooCommerce help tab for course post type'
		);
	}

	/**
	 * Test if WooCommerce help tab is being untouched in no Sensei pages.
	 *
	 * @covers Sensei_Setup_Wizard::should_enable_woocommerce_help_tab
	 */
	public function testShouldEnableWooCommerceHelpTabNoSenseiPage() {
		$_GET['post_type'] = 'woocommerce';

		$this->assertTrue(
			Sensei()->setup_wizard->should_enable_woocommerce_help_tab( true ),
			'Should not touch WooCommerce help tab for no Sensei pages'
		);
	}

	/**
	 * Test add setup wizard help tab to edit course screen.
	 *
	 * @covers Sensei_Setup_Wizard::add_setup_wizard_help_tab
	 */
	public function testAddSetupWizardHelpTab() {
		// Create and login as administrator.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'edit-course' );
		$screen = get_current_screen();

		$screen->remove_help_tab( 'sensei_lms_setup_wizard_tab' );
		Sensei()->setup_wizard->add_setup_wizard_help_tab( $screen );
		$created_tab = $screen->get_help_tab( 'sensei_lms_setup_wizard_tab' );

		$this->assertNotNull( $created_tab, 'Should create the setup wizard tab to edit course screens.' );
	}

	/**
	 * Test add setup wizard help tab in non edit course screens.
	 *
	 * @covers Sensei_Setup_Wizard::add_setup_wizard_help_tab
	 */
	public function testAddSetupWizardHelpTabNonEditCourseScreen() {
		// Create and login as administrator.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'edit-lesson' );
		$screen = get_current_screen();

		$screen->remove_help_tab( 'sensei_lms_setup_wizard_tab' );
		Sensei()->setup_wizard->add_setup_wizard_help_tab( $screen );
		$created_tab = $screen->get_help_tab( 'sensei_lms_setup_wizard_tab' );

		$this->assertNull( $created_tab, 'Should not create the setup wizard tab to non edit course screens.' );
	}

	/**
	 * Test add setup wizard help tab for no admin user.
	 *
	 * @covers Sensei_Setup_Wizard::add_setup_wizard_help_tab
	 */
	public function testAddSetupWizardHelpTabNoAdmin() {
		// Create and login as teacher.
		$teacher_id = $this->factory->user->create( array( 'role' => 'teacher' ) );
		wp_set_current_user( $teacher_id );

		set_current_screen( 'edit-course' );
		$screen = get_current_screen();

		$screen->remove_help_tab( 'sensei_lms_setup_wizard_tab' );
		Sensei()->setup_wizard->add_setup_wizard_help_tab( $screen );
		$created_tab = $screen->get_help_tab( 'sensei_lms_setup_wizard_tab' );

		$this->assertNull( $created_tab, 'Should not create the setup wizard tab to no admin user.' );
	}

	/**
	 * Tests that get sensei extensions fetch from the correct URL
	 * filtering by the dotorg only as default.
	 *
	 * @covers Sensei_Setup_Wizard::get_sensei_extensions
	 */
	public function testGetSenseiExtensionsDotOrgExtensions() {
		// Mock fetch from senseilms.com.
		$request_url = null;
		add_filter(
			'pre_http_request',
			function( $preempt, $parsed_args, $url ) use ( &$request_url ) {
				$request_url = $url;
				return [ 'body' => '{}' ];
			},
			10,
			3
		);

		$extensions = Sensei()->setup_wizard->get_sensei_extensions();

		$this->assertEquals( 'https://senseilms.com/wp-json/senseilms-products/1.0/search?category=setup-wizard-extensions&type=plugin&hosted-location=dotorg', $request_url );
	}

	/**
	 * Tests that get sensei extensions fetch from the correct URL
	 * with all extensions type.
	 *
	 * @covers Sensei_Setup_Wizard::get_sensei_extensions
	 */
	public function testGetSenseiExtensionsAllExtensions() {
		// Activate feature.
		// It is usually activated by defining a const.
		add_filter( 'sensei_feature_flag_setup_wizard_all_extensions', '__return_true' );

		// Mock fetch from senseilms.com.
		$request_url = null;
		add_filter(
			'pre_http_request',
			function( $preempt, $parsed_args, $url ) use ( &$request_url ) {
				$request_url = $url;
				return [ 'body' => '{}' ];
			},
			10,
			3
		);

		$extensions = Sensei()->setup_wizard->get_sensei_extensions();

		$this->assertEquals( 'https://senseilms.com/wp-json/senseilms-products/1.0/search?category=setup-wizard-extensions&type=plugin', $request_url );
	}

	/**
	 * Tests that get sensei extensions and returns with decoded prices.
	 *
	 * @covers Sensei_Setup_Wizard::get_sensei_extensions
	 */
	public function testGetSenseiExtensionsAndReturnsWithDecodedPrices() {
		// Mock fetch from senseilms.com.
		$response_body = '{
			"products": [
				{ "product_slug": "slug-1", "price": "&#36;1.00", "plugin_file": "test/test.php" },
				{ "product_slug": "slug-2", "price": 0, "plugin_file": "test/test.php" }
			]
		}';
		add_filter(
			'pre_http_request',
			function() use ( $response_body ) {
				return [ 'body' => $response_body ];
			}
		);

		$extensions = Sensei()->setup_wizard->get_sensei_extensions();

		$this->assertEquals( $extensions[0]->price, '$1.00' );
		$this->assertEquals( $extensions[1]->price, 0 );
	}

	/**
	 * Tests that get sensei extensions and returns with decoded prices.
	 *
	 * @covers Sensei_Setup_Wizard::get_feature_with_status
	 * @covers Sensei_Setup_Wizard::get_sensei_extensions
	 */
	public function testGetSenseiExtensionsWithStatuses() {
		// Set installing plugins
		$installing_plugins = [
			(object) [
				'product_slug' => 'slug-1',
			],
			(object) [
				'product_slug' => 'slug-2',
				'error'        => 'Error message',
			],
		];
		$transient          = Sensei_Plugins_Installation::INSTALLING_PLUGINS_TRANSIENT;
		set_transient( $transient, $installing_plugins, DAY_IN_SECONDS );

		// Mock fetch from senseilms.com.
		$response_body = '{
			"products": [
				{ "product_slug": "slug-1", "plugin_file": "test/test.php" },
				{ "product_slug": "slug-2", "plugin_file": "test/test.php" },
				{ "product_slug": "slug-3", "plugin_file": "test/test-installed.php" },
				{ "product_slug": "slug-4", "plugin_file": "test/test.php" }
			]
		}';
		add_filter(
			'pre_http_request',
			function() use ( $response_body ) {
				return [ 'body' => $response_body ];
			}
		);

		// Mock active plugins.
		$mock = $this->getMockBuilder( Sensei_Plugins_Installation::class )
			->disableOriginalConstructor()
			->setMethods( [ 'is_plugin_active' ] )
			->getMock();

		$mock->method( 'is_plugin_active' )
			->will( $this->onConsecutiveCalls( false, false, true, false ) );

		$property = new ReflectionProperty( 'Sensei_Plugins_Installation', 'instance' );
		$property->setAccessible( true );
		$real_instance = $property->getValue();
		$property->setValue( $mock );

		$expected_extensions = [
			(object) [
				'product_slug' => 'slug-1',
				'status'       => 'installing',
				'plugin_file'  => 'test/test.php',
			],
			(object) [
				'product_slug' => 'slug-2',
				'status'       => 'error',
				'error'        => 'Error message',
				'plugin_file'  => 'test/test.php',
			],
			(object) [
				'product_slug' => 'slug-3',
				'status'       => 'installed',
				'plugin_file'  => 'test/test-installed.php',
			],
			(object) [
				'product_slug' => 'slug-4',
				'plugin_file'  => 'test/test.php',
			],
		];
		$extensions          = Sensei()->setup_wizard->get_sensei_extensions();

		// Revert mocked instance
		$property->setValue( $real_instance );

		$this->assertEquals( $expected_extensions, $extensions );
	}

	/**
	 * Tests that not allowed extensions are not installed.
	 *
	 * @covers Sensei_Setup_Wizard::install_extensions
	 */
	public function testInstallNotAllowedExtension() {
		// Mock fetch from senseilms.com.
		$response_body = '{
			"products": [
				{ "product_slug": "allowed", "plugin_file": "test/test.php" },
				{ "product_slug": "allowed-2", "plugin_file": "test/test.php" }
			]
		}';
		add_filter(
			'pre_http_request',
			function() use ( $response_body ) {
				return [ 'body' => $response_body ];
			}
		);

		$expected_extensions = [
			(object) [
				'product_slug' => 'allowed',
				'plugin_file'  => 'test/test.php',
			],
		];

		// Mock install plugins method
		$mock = $this->getMockBuilder( Sensei_Plugins_Installation::class )
			->disableOriginalConstructor()
			->setMethods( [ 'install_plugins' ] )
			->getMock();

		$property = new ReflectionProperty( 'Sensei_Plugins_Installation', 'instance' );
		$property->setAccessible( true );
		$real_instance = $property->getValue();
		$property->setValue( $mock );

		$mock->expects( $this->once() )->method( 'install_plugins' )->with( $this->equalTo( $expected_extensions ) );

		Sensei()->setup_wizard->install_extensions( [ 'allowed', 'not-allowed' ] );

		// Revert mocked instance
		$property->setValue( $real_instance );
	}

	/**
	 * Tests that WC is installed when a WC extension is being installed.
	 *
	 * @covers Sensei_Setup_Wizard::install_extensions
	 * @covers Sensei_Setup_Wizard::maybe_add_woocommerce_to_installation
	 */
	public function testInstallWCExtension() {
		// Mock fetch from senseilms.com.
		$response_body = '{
			"products": [
				{ "product_slug": "wc-extension", "plugin_file": "test/test.php", "wccom_product_id": 1234 }
			]
		}';
		add_filter(
			'pre_http_request',
			function() use ( $response_body ) {
				return [ 'body' => $response_body ];
			}
		);

		// Mock install plugins method
		$mock = $this->getMockBuilder( Sensei_Plugins_Installation::class )
			->disableOriginalConstructor()
			->setMethods( [ 'install_plugins' ] )
			->getMock();

		$property = new ReflectionProperty( 'Sensei_Plugins_Installation', 'instance' );
		$property->setAccessible( true );
		$real_instance = $property->getValue();
		$property->setValue( $mock );

		$mock
			->expects( $this->once() )
			->method( 'install_plugins' )
			->will(
				$this->returnCallback(
					function( $extensions ) {
						$this->assertEquals( 'woocommerce', $extensions[0]->product_slug );
					}
				)
			);
		Sensei()->setup_wizard->install_extensions( [ 'wc-extension' ] );

		// Revert mocked instance
		$property->setValue( $real_instance );
	}
}

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
 * @covers Sensei_Setup_Wizard
 */
class Sensei_Setup_Wizard_Test extends WP_UnitTestCase {
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
			],
			DAY_IN_SECONDS
		);
	}

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Save original current screen.
		global $current_screen;
		$this->original_screen = $current_screen;

		Sensei_Test_Events::reset();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
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
	 * Return 'en_US' to be used in filters.
	 *
	 * @return string
	 */
	public function return_en_US() {
		return 'en_US';
	}
}

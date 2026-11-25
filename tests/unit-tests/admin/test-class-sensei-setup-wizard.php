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
	use Sensei_Test_Redirect_Helpers;

	/**
	 * The original screen.
	 *
	 * @var WP_Screen
	 */
	private $original_screen;

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
	public function testClassInstance_Always_Exists() {
		// Assert.
		$this->assertTrue( class_exists( 'Sensei_Setup_Wizard' ), 'Sensei Setup Wizard class does not exist' );
	}

	/**
	 * Test setup wizard notice in dashboard.
	 */
	public function testSetupWizardNotice_WhenSuggestSetupWizardOptionIsOneAndScreenIsDashboard_DisplaysNotice() {
		// Arrange.
		// Create and login as admin.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'dashboard' );
		update_option( \Sensei_Setup_Wizard::SUGGEST_SETUP_WIZARD_OPTION, 1 );

		// Act.
		ob_start();
		Sensei()->setup_wizard->setup_wizard_notice();
		$html = ob_get_clean();

		$pos_setup_button = strpos( $html, 'Run the Setup Wizard' );

		// Assert.
		$this->assertNotFalse( $pos_setup_button, 'Should return the notice HTML' );
	}

	/**
	 * Test setup wizard notice in screen with Sensei prefix.
	 */
	public function testSetupWizardNotice_WhenSuggestSetupWizardOptionIsOneAndScreenIsSenseiPage_DisplaysNotice() {
		// Arrange.
		// Create and login as admin.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'sensei-lms_page_sensei_test' );
		update_option( \Sensei_Setup_Wizard::SUGGEST_SETUP_WIZARD_OPTION, 1 );

		// Act.
		ob_start();
		Sensei()->setup_wizard->setup_wizard_notice();
		$html = ob_get_clean();

		$pos_setup_button = strpos( $html, 'Run the Setup Wizard' );

		// Assert.
		$this->assertNotFalse( $pos_setup_button, 'Should return the notice HTML' );
	}

	/**
	 * Test setup wizard notice in no Sensei screen.
	 */
	public function testSetupWizardNotice_WhenInOtherScreen_DoesNotDisplayNotice() {
		// Arrange.
		// Create and login as admin.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'other' );
		update_option( \Sensei_Setup_Wizard::SUGGEST_SETUP_WIZARD_OPTION, 1 );

		// Act.
		ob_start();
		Sensei()->setup_wizard->setup_wizard_notice();
		$html = ob_get_clean();

		// Assert.
		$this->assertEmpty( $html, 'Should return empty string' );
	}

	/**
	 * Test setup wizard notice with suggest option as 0.
	 */
	public function testSetupWizardNotice_WhenSuggestOptionIsZero_DoesNotDisplayNotice() {
		// Arrange.
		// Create and login as admin.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'dashboard' );
		update_option( \Sensei_Setup_Wizard::SUGGEST_SETUP_WIZARD_OPTION, 0 );

		// Act.
		ob_start();
		Sensei()->setup_wizard->setup_wizard_notice();
		$html = ob_get_clean();

		// Assert.
		$this->assertEmpty( $html, 'Should return empty string' );
	}

	/**
	 * Test setup wizard notice with suggest option empty.
	 */
	public function testSetupWizardNotice_WhenSuggestOptionIsEmpty_DoesNotDisplayNotice() {
		// Arrange.
		// Create and login as admin.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'dashboard' );

		// Act.
		ob_start();
		Sensei()->setup_wizard->setup_wizard_notice();
		$html = ob_get_clean();

		// Assert.
		$this->assertEmpty( $html, 'Should return empty string' );
	}

	/**
	 * Test setup wizard notice for no admin user.
	 */
	public function testSetupWizardNotice_WhenUserIsNoAdmin_DoesNotDisplayNotice() {
		// Arrange.
		// Create and login as teacher.
		$teacher_id = $this->factory->user->create( array( 'role' => 'teacher' ) );
		wp_set_current_user( $teacher_id );

		set_current_screen( 'dashboard' );
		update_option( \Sensei_Setup_Wizard::SUGGEST_SETUP_WIZARD_OPTION, 1 );

		// Act.
		ob_start();
		Sensei()->setup_wizard->setup_wizard_notice();
		$html = ob_get_clean();

		// Assert.
		$this->assertEmpty( $html, 'Should return empty string' );
	}

	/**
	 * Test skip setup wizard.
	 */
	public function testSkipSetupWizard_WhenArgumentsAreSet_UpdatesOptionToZero() {
		// Arrange.
		// Create and login as admin.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$_GET['sensei_skip_setup_wizard'] = '1';
		$_GET['_wpnonce']                 = wp_create_nonce( 'sensei_skip_setup_wizard' );

		// Act.
		Sensei()->setup_wizard->skip_setup_wizard();
		$option_value = get_option( \Sensei_Setup_Wizard::SUGGEST_SETUP_WIZARD_OPTION, false );

		// Assert.
		$this->assertEquals( '0', $option_value, 'Should update option to 0' );
	}

	public function testUpdateWizardUserData_WhenHtmlVersionAndUnknownKeyGiven_StoresOnlyTheSanitizedKnownData() {
		// Act.
		Sensei()->setup_wizard->update_wizard_user_data(
			array(
				'purpose'   => array(
					'selected' => array( 'other' ),
					'other'    => 'Teaching <script>alert(1)</script>',
				),
				'__version' => '0',
				'steps'     => array( 'x' ),
			)
		);
		$data = get_option( Sensei_Setup_Wizard::USER_DATA_OPTION );

		// Assert.
		$this->assertSame( 'Teaching', $data['purpose']['other'], 'The purpose text should be sanitized.' );
		$this->assertSame( '3', $data['__version'], 'The schema version should not be writable.' );
		$this->assertArrayNotHasKey( 'steps', $data, 'An unknown key should not be stored.' );
	}

	/**
	 * Test skip setup wizard.
	 */
	public function testSkipSetupWizard_WhenUserIsNoAdmin_DoesNotUpdateOption() {
		// Arrange.
		// Create and login as teacher.
		$teacher_id = $this->factory->user->create( array( 'role' => 'teacher' ) );
		wp_set_current_user( $teacher_id );

		$_GET['sensei_skip_setup_wizard'] = '1';
		$_GET['_wpnonce']                 = wp_create_nonce( 'sensei_skip_setup_wizard' );

		// Act.
		Sensei()->setup_wizard->skip_setup_wizard();
		$option_value = get_option( \Sensei_Setup_Wizard::SUGGEST_SETUP_WIZARD_OPTION, false );

		// Assert.
		$this->assertFalse( $option_value, 'Should not update option' );
	}

	/*
	 * Testing if activation redirect works properly.
	 */
	public function testActivationRedirect_WhenRedirectOptionIsOne_CallsRedirect() {
		// Arrange.
		// Create and login as administrator.
		$expected_redirect = admin_url( 'admin.php?page=sensei_setup_wizard' );
		$admin_id          = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->prevent_wp_redirect();
		wp_set_current_user( $admin_id );
		set_current_screen( 'dashboard' );

		update_option( 'sensei_activation_redirect', 1 );

		// Act.
		$redirect_location = '';
		try {
			Sensei()->setup_wizard->activation_redirect();
		} catch ( Sensei_WP_Redirect_Exception $e ) {
			$redirect_location = $e->getMessage();
		}

		// Assert.
		$this->assertSame( $expected_redirect, $redirect_location );
	}

	/*
	 * Testing if activation doesn't redirect for no Sensei screens.
	 */
	public function testActivationRedirect_WhenInAPageNotRelatedToSensei_DoesNotCallRedirect() {
		// Arrange.
		// Create and login as administrator.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->prevent_wp_redirect();
		wp_set_current_user( $admin_id );
		set_current_screen( 'any_other' );

		update_option( 'sensei_activation_redirect', 1 );

		// Act.
		$redirect_location = '';
		try {
			Sensei()->setup_wizard->activation_redirect();
		} catch ( Sensei_WP_Redirect_Exception $e ) {
			$redirect_location = $e->getMessage();
		}

		// Assert.
		$this->assertEmpty( $redirect_location );
	}

	/**
	 * Testing if activation doesn't redirect for no admin user.
	 */
	public function testActivationRedirect_WhenUserIsNoAdmin_DoesNotCallRedirect() {
		// Arrange.
		// Create and login as subscriber.
		$subscriber_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$this->prevent_wp_redirect();
		wp_set_current_user( $subscriber_id );

		update_option( 'sensei_activation_redirect', 1 );

		// Act.
		$redirect_location = '';
		try {
			Sensei()->setup_wizard->activation_redirect();
		} catch ( Sensei_WP_Redirect_Exception $e ) {
			$redirect_location = $e->getMessage();
		}

		// Assert.
		$this->assertEmpty( $redirect_location );
	}

	/**
	 * Testing if activation doesn't redirect when option does not exist.
	 */
	public function testActivationRedirect_WhenRedirectOptionDoesNotExist_DoesNotCallRedirect() {
		// Arrange.
		// Create and login as administrator.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->prevent_wp_redirect();
		wp_set_current_user( $admin_id );

		// Act.
		$redirect_location = '';
		try {
			Sensei()->setup_wizard->activation_redirect();
		} catch ( Sensei_WP_Redirect_Exception $e ) {
			$redirect_location = $e->getMessage();
		}

		// Assert.
		$this->assertEmpty( $redirect_location );
	}

	/**
	 * Testing if redirect option is cleared on setup wizard rendering.
	 */
	public function testRenderWizardPage_WhenRendered_ClearsRedirectOption() {
		// Arrange.
		update_option( 'sensei_activation_redirect', 1 );

		// Act.
		ob_start();
		Sensei()->setup_wizard->render_wizard_page();
		ob_end_clean();

		// Assert.
		$this->assertFalse( get_option( 'sensei_activation_redirect', false ) );
	}

	/**
	 * Test if WooCommerce help tab is being prevented in the Sensei pages.
	 */
	public function testWooCommerceHelpTab_WhenOnCoursePage_ShouldNotPreventTab() {
		// Arrange.
		$_GET['post_type'] = 'course';

		// Act & Assert.
		$this->assertFalse(
			Sensei()->setup_wizard->should_enable_woocommerce_help_tab( true ),
			'Should not allow WooCommerce help tab for course post type'
		);
	}

	/**
	 * Test if WooCommerce help tab is being untouched in no Sensei pages.
	 */
	public function testWooCommerceHelpTab_WhenOnNoSenseiPage_ShouldNotChangeValue() {
		// Arrange.
		$_GET['post_type'] = 'woocommerce';

		// Act & Assert.
		$this->assertTrue(
			Sensei()->setup_wizard->should_enable_woocommerce_help_tab( true ),
			'Should not touch WooCommerce help tab for no Sensei pages'
		);
	}

	/**
	 * Test add setup wizard help tab to edit course screen.
	 */
	public function testAddSetupWizardHelpTab_WhenInEditCourse_ShouldAddTab() {
		// Arrange.
		// Create and login as administrator.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'edit-course' );
		$screen = get_current_screen();

		$screen->remove_help_tab( 'sensei_lms_setup_wizard_tab' );

		// Act.
		Sensei()->setup_wizard->add_setup_wizard_help_tab( $screen );
		$created_tab = $screen->get_help_tab( 'sensei_lms_setup_wizard_tab' );

		// Assert.
		$this->assertNotNull( $created_tab, 'Should create the setup wizard tab to edit course screens.' );
	}

	/**
	 * Test add setup wizard help tab in non edit course screens.
	 */
	public function testAddSetupWizardHelpTab_WhenNotInEditCourse_ShouldNotAddTab() {
		// Arrange.
		// Create and login as administrator.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'edit-lesson' );
		$screen = get_current_screen();

		$screen->remove_help_tab( 'sensei_lms_setup_wizard_tab' );

		// Act.
		Sensei()->setup_wizard->add_setup_wizard_help_tab( $screen );
		$created_tab = $screen->get_help_tab( 'sensei_lms_setup_wizard_tab' );

		// Assert.
		$this->assertNull( $created_tab, 'Should not create the setup wizard tab to non edit course screens.' );
	}

	/**
	 * Test add setup wizard help tab for no admin user.
	 */
	public function testAddSetupWizardHelpTab_WhenUserIsNoAdmin_ShouldNotAddTab() {
		// Arrange.
		// Create and login as teacher.
		$teacher_id = $this->factory->user->create( array( 'role' => 'teacher' ) );
		wp_set_current_user( $teacher_id );

		set_current_screen( 'edit-course' );
		$screen = get_current_screen();

		$screen->remove_help_tab( 'sensei_lms_setup_wizard_tab' );

		// Act.
		Sensei()->setup_wizard->add_setup_wizard_help_tab( $screen );
		$created_tab = $screen->get_help_tab( 'sensei_lms_setup_wizard_tab' );

		// Assert.
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

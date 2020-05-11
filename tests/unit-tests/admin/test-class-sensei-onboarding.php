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

	/**
	 * Test add onboarding help tab to edit course screen.
	 *
	 * @covers Sensei_Onboarding::add_onboarding_help_tab
	 */
	public function testAddOnboardingHelpTab() {
		// Create and login as administrator.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'edit-course' );
		$screen = get_current_screen();

		$screen->remove_help_tab( 'sensei_lms_onboarding_tab' );
		Sensei()->onboarding->add_onboarding_help_tab( $screen );
		$created_tab = $screen->get_help_tab( 'sensei_lms_onboarding_tab' );

		$this->assertNotNull( $created_tab, 'Should create the onboarding tab to edit course screens.' );
	}

	/**
	 * Test add onboarding help tab in non edit course screens.
	 *
	 * @covers Sensei_Onboarding::add_onboarding_help_tab
	 */
	public function testAddOnboardingHelpTabNonEditCourseScreen() {
		// Create and login as administrator.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		set_current_screen( 'edit-lesson' );
		$screen = get_current_screen();

		$screen->remove_help_tab( 'sensei_lms_onboarding_tab' );
		Sensei()->onboarding->add_onboarding_help_tab( $screen );
		$created_tab = $screen->get_help_tab( 'sensei_lms_onboarding_tab' );

		$this->assertNull( $created_tab, 'Should not create the onboarding tab to non edit course screens.' );
	}

	/**
	 * Test add onboarding help tab for no admin user.
	 *
	 * @covers Sensei_Onboarding::add_onboarding_help_tab
	 */
	public function testAddOnboardingHelpTabNoAdmin() {
		// Create and login as teacher.
		$teacher_id = $this->factory->user->create( array( 'role' => 'teacher' ) );
		wp_set_current_user( $teacher_id );

		set_current_screen( 'edit-course' );
		$screen = get_current_screen();

		$screen->remove_help_tab( 'sensei_lms_onboarding_tab' );
		Sensei()->onboarding->add_onboarding_help_tab( $screen );
		$created_tab = $screen->get_help_tab( 'sensei_lms_onboarding_tab' );

		$this->assertNull( $created_tab, 'Should not create the onboarding tab to no admin user.' );
	}
}

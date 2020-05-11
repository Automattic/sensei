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

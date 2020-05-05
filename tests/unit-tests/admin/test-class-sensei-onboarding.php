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

		$this->onboarding_instance = new Sensei_Onboarding();

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
		set_current_screen( 'edit-course' );
		$screen = get_current_screen();

		$this->onboarding_instance->add_onboarding_help_tab( $screen );
		$created_tab = $screen->get_help_tab( 'sensei_lms_onboarding_tab' );

		$this->assertNotNull( $created_tab, 'Should create the onboarding tab to edit course screens.' );
	}

	/**
	 * Test add onboarding help tab in non edit course screens.
	 *
	 * @covers Sensei_Onboarding::add_onboarding_help_tab
	 */
	public function testAddOnboardingHelpTabNonEditCourseScreen() {
		set_current_screen( 'edit-lesson' );
		$screen = get_current_screen();

		$this->onboarding_instance->add_onboarding_help_tab( $screen );
		$created_tab = $screen->get_help_tab( 'sensei_lms_onboarding_tab' );

		$this->assertNull( $created_tab, 'Should not create the onboarding tab to non edit course screens.' );
	}
}

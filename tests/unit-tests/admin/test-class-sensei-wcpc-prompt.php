<?php
/**
 * This file contains the Sensei_Setup_Wizard_Test class.
 *
 * @package sensei
 */

/**
 * Tests for Sensei_WCPC_Prompt class.
 */
class Sensei_WCPC_Prompt_Test extends WP_UnitTestCase {

	use Sensei_Test_Login_Helpers;

	/**
	 * Set up before each test.
	 */
	public function setup() {
		parent::setup();

		$this->factory = new Sensei_Factory();

		$this->login_as_admin();

		// Mock that WooCommerce is active.
		update_option( 'active_plugins', [ 'woocommerce/woocommerce.php' => 'woocommerce/woocommerce.php' ] );

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
	 * Testing the WCPC prompt class to make sure it is loaded.
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( 'Sensei_WCPC_Prompt' ), 'Sensei Setup Wizard class does not exist' );
	}

	/**
	 * Tests that WCPC installation notice is displayed.
	 *
	 * @return void
	 */
	public function testWCPCNoticeIsDisplayed() {
		$instance = new Sensei_WCPC_Prompt();

		set_current_screen( 'edit-course' );
		$this->factory->course->create();

		ob_start();
		$instance->wcpc_prompt();
		$output = ob_get_clean();

		$this->assertContains( 'Install now', $output );
	}

	/**
	 * Tests that WCPC installation notice is not displayed without any course.
	 *
	 * @return void
	 */
	public function testWCPCNoticeIsNotDisplayedWithoutCourse() {
		$instance = new Sensei_WCPC_Prompt();

		set_current_screen( 'edit-course' );

		ob_start();
		$instance->wcpc_prompt();
		$output = ob_get_clean();

		$this->assertEmpty( $output );
	}

	/**
	 * Tests that WCPC installation notice is not displayed in no course pages.
	 *
	 * @return void
	 */
	public function testWCPCNoticeIsNotDisplayedInNoCoursesPages() {
		$instance = new Sensei_WCPC_Prompt();

		set_current_screen( 'no-course-page' );
		$this->factory->course->create();

		ob_start();
		$instance->wcpc_prompt();
		$output = ob_get_clean();

		$this->assertEmpty( $output );
	}

	/**
	 * Tests that WCPC installation notice is not displayed when WooCommerce is not active.
	 *
	 * @return void
	 */
	public function testWCPCNoticeIsNotDisplayedWithoutWooCommerce() {
		update_option( 'active_plugins', [] );

		$instance = new Sensei_WCPC_Prompt();

		set_current_screen( 'edit-course' );
		$this->factory->course->create();

		ob_start();
		$instance->wcpc_prompt();
		$output = ob_get_clean();

		$this->assertEmpty( $output );
	}

	/**
	 * Tests that WCPC installation notice is not displayed when dismissed.
	 *
	 * @return void
	 */
	public function testWCPCNoticeIsNotDisplayedWhenDismissed() {
		$instance = new Sensei_WCPC_Prompt();

		set_current_screen( 'edit-course' );
		$this->factory->course->create();

		// Dismiss prompt.
		$_GET['sensei_dismiss_wcpc_prompt'] = '1';
		$_GET['_wpnonce']                   = wp_create_nonce( 'sensei_dismiss_wcpc_prompt' );
		$instance->dismiss_prompt();

		ob_start();
		$instance->wcpc_prompt();
		$output = ob_get_clean();

		$this->assertEmpty( $output );
	}
}

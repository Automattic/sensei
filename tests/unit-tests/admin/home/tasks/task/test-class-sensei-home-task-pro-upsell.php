<?php
/**
 * This file contains the Sensei_Home_Task_Pro_Upsell_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Tests for Sensei_Home_Task_Pro_Upsell_Test class.
 *
 * @covers Sensei_Home_Task_Pro_Upsell
 */
class Sensei_Home_Task_Pro_Upsell_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;
	use Sensei_Test_Redirect_Helpers;

	/**
	 * The task under test.
	 *
	 * @var Sensei_Home_Task_Pro_Upsell
	 */
	private $task;

	public function setUp(): void {
		parent::setUp();
		$this->task = new Sensei_Home_Task_Pro_Upsell();
	}

	public function tearDown(): void {
		delete_option( Sensei_Home_Task_Pro_Upsell::get_id() );
		parent::tearDown();
	}

	public function testGetId_WhenCalled_ReturnsCorrectId() {
		$this->assertSame( 'sensei-home-task-pro-upsell', Sensei_Home_Task_Pro_Upsell::get_id() );
	}

	public function testGetTitle_WhenCalled_ReturnsCorrectTitle() {
		$this->assertSame( 'Sell your course with Sensei Pro', $this->task->get_title() );
	}

	public function testGetUrl_WhenCalled_ReturnsCorrectUrl() {
		$this->assertStringContainsString( 'sensei-internal/v1/home/sensei-pro-upsell-redirect?_wpnonce=', $this->task->get_url() );
	}

	public function testIsCompleted_WhenCalled_IsNotCompletedByDefault() {
		$this->assertFalse( $this->task->is_completed() );
	}

	public function testMarkCompleteAndRedirect_WhenCalled_TriesToRedirectToRightPage() {
		/* Arrange. */
		$this->login_as_admin();
		$this->prevent_wp_redirect();
		$redirect_location = '';

		/* Act. */
		try {
			Sensei_Home_Task_Pro_Upsell::mark_completed_and_redirect();
		} catch ( Sensei_WP_Redirect_Exception $e ) {
			$redirect_location = $e->getMessage();
		}

		/* Assert. */
		$this->assertSame( 'https://senseilms.com/sensei-pro/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=sensei-home', $redirect_location );
	}

	public function testMarkCompleteAndRedirect_WhenCalled_SetsTheOptionProperly() {
		/* Arrange. */
		$this->login_as_admin();
		$this->prevent_wp_redirect();
		$before_completed = $this->task->is_completed();

		/* Act. */
		try {
			Sensei_Home_Task_Pro_Upsell::mark_completed_and_redirect();
		} catch ( Sensei_WP_Redirect_Exception $e ) {
			$after_completed = $this->task->is_completed();
		}

		/* Assert. */
		$this->assertFalse( $before_completed );
		$this->assertTrue( $after_completed );
	}
}

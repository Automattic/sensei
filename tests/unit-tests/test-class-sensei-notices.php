<?php
/**
 * Tests for Sensei_Notices class.
 *
 * @group notices
 */
class Sensei_Notices_Test extends WP_UnitTestCase {

	/**
	 * Notices instance under test.
	 *
	 * @var Sensei_Notices
	 */
	private $notices;

	public function setUp(): void {
		parent::setUp();

		$this->notices = new Sensei_Notices();
	}

	public function testMaybePrintNotices_KeyedNoticeAddedTwiceBeforePrinting_PrintsNoticeOnce() {
		$this->notices->add_notice( 'Log in please.', 'info', 'login-notice' );
		$this->notices->add_notice( 'Log in please.', 'info', 'login-notice' );

		$output = $this->print_notices();

		$this->assertSame( 1, substr_count( $output, 'Log in please.' ) );
	}

	public function testAddNotice_KeyedNoticeAddedTwiceAfterPrintingStarted_PrintsNoticeOnce() {
		// Start printing with an empty queue, as happens on `wp_body_open` in classic themes.
		$this->print_notices();

		ob_start();
		$this->notices->add_notice( 'Log in please.', 'info', 'login-notice' );
		$this->notices->add_notice( 'Log in please.', 'info', 'login-notice' );
		$output = ob_get_clean();

		$this->assertSame( 1, substr_count( $output, 'Log in please.' ) );
	}

	public function testAddNotice_KeyedNoticeReAddedAfterItWasPrinted_DoesNotPrintAgain() {
		$this->notices->add_notice( 'Log in please.', 'info', 'login-notice' );
		$this->print_notices();

		ob_start();
		$this->notices->add_notice( 'Log in please.', 'info', 'login-notice' );
		$output = ob_get_clean();

		$this->assertStringNotContainsString( 'Log in please.', $output );
	}

	public function testAddNotice_UnkeyedNoticesAddedAfterPrintingStarted_StillPrint() {
		$this->print_notices();

		ob_start();
		$this->notices->add_notice( 'First message.' );
		$this->notices->add_notice( 'Second message.' );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'First message.', $output, 'First unkeyed notice should print immediately once printing has started.' );
		$this->assertStringContainsString( 'Second message.', $output, 'Second unkeyed notice should print immediately once printing has started.' );
	}

	/**
	 * Print the queued notices and return the output.
	 *
	 * @return string
	 */
	private function print_notices(): string {
		ob_start();
		$this->notices->maybe_print_notices();

		return ob_get_clean();
	}
}

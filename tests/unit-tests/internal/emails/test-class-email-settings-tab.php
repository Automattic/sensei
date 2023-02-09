<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Settings_Tab;

/**
 * Tests for Sensei\Internal\Emails\Email_Settings_Tab.
 *
 * @covers \Sensei\Internal\Emails\Email_Settings_Tab
 */
class Email_Settings_Tab_Test extends \WP_UnitTestCase {
	public function testInit_WhenCalled_AddsFilter() {
		/* Arrange. */
		$email_settings_tab = new Email_Settings_Tab();

		/* Act. */
		$sections = $email_settings_tab->init();

		/* Assert. */
		$priority = has_filter( 'sensei_settings_tab_content', [ $email_settings_tab, 'tab_content' ] );
		self::assertSame( 10, $priority );
	}

	public function testTabContent_WhenCalledWithEmailNotificationSettings_ReturnsContentWithTable() {
		/* Arrange. */
		$email_settings_tab = new Email_Settings_Tab();

		/* Act. */
		$content = $email_settings_tab->tab_content( 'a', 'email-notification-settings' );

		/* Assert. */
		self::assertStringContainsString( '<table', $content );
	}

	public function testTabContent_WhenCalledWithAnotherTab_ReturnsDefaultContent() {
		/* Arrange. */
		$email_settings_tab = new Email_Settings_Tab();

		/* Act. */
		$content = $email_settings_tab->tab_content( 'a', 'other-tab' );

		/* Assert. */
		self::assertSame( 'a', $content );
	}
}

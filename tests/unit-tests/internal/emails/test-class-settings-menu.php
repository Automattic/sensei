<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Settings_Menu;

class Settings_Menu_Test extends \WP_UnitTestCase {
	public function testInit_WhenCalled_AddsFilter() {
		/* Arrange. */
		$settings_menu = new Settings_Menu();

		/* Act. */
		$settings_menu->init();

		/* Assert. */
		$actual_priority = has_filter( 'sensei_settings_tabs', [ $settings_menu, 'replace_email_tab' ] );
		self::assertSame( 10, $actual_priority );
	}


	public function testReplaceEmailTab_SectionsGiven_ReplacesEmailNoficationSettings() {
		/* Arrange. */
		$settings_menu = new Settings_Menu();

		/* Act. */
		$sections = $settings_menu->replace_email_tab(
			[
				'email-notification-settings' => 'a',
			]
		);

		/* Assert. */
		$expected = [
			'email-notification-settings' => [
				'name'        => 'Emails',
				'description' => 'Settings for email notifications sent from your site.',
				'href'        => admin_url( 'admin.php?page=sensei-settings&tab=email-notification-settings' ),
				'external'    => true,
			],
		];
		self::assertSame( $expected, $sections );
	}
}

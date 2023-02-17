<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Settings_Menu;

class Settings_Menu_Test extends \WP_UnitTestCase {
	/**
	 * Tests that the email tab is replaced.
	 */
	public function testReplaceEmailTab() {
		/* Arrange. */
		$settings_menu = new Settings_Menu();

		/* Act. */
		$sections = $settings_menu->replace_email_tab( [] );

		/* Assert. */
		$expected = [
			'email-notification-settings' => [
				'name'        => 'Emails',
				'description' => 'Settings for email notifications sent from your site.',
				'href'        => admin_url( 'admin.php?page=sensei-settings&tab=email-notification-settings' ),
			],
		];
		self::assertSame( $expected, $sections );
	}
}

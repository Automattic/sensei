<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Settings_Tab;
use Sensei_Factory;
use Sensei_Settings;

/**
 * Tests for Sensei\Internal\Emails\Email_Settings_Tab.
 *
 * @covers \Sensei\Internal\Emails\Email_Settings_Tab
 */
class Email_Settings_Tab_Test extends \WP_UnitTestCase {
	public function setUp(): void {
		parent::setUp();
		$this->factory = new Sensei_Factory();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testInit_WhenCalled_AddsFilter() {
		/* Arrange. */
		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );

		/* Act. */
		$sections = $email_settings_tab->init();

		/* Assert. */
		$priority = has_filter( 'sensei_settings_tab_content', [ $email_settings_tab, 'tab_content' ] );
		self::assertSame( 10, $priority );
	}

	public function testTabContent_WhenCalledWithEmailNotificationSettings_ReturnsContentWithTable() {
		/* Arrange. */
		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );

		/* Act. */
		$content = $email_settings_tab->tab_content( 'a', 'email-notification-settings' );

		/* Assert. */
		self::assertStringContainsString( '<table', $content );
	}

	public function testTabContent_WhenCalledWithAnotherTab_ReturnsDefaultContent() {
		/* Arrange. */
		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );

		/* Act. */
		$content = $email_settings_tab->tab_content( 'a', 'other-tab' );

		/* Assert. */
		self::assertSame( 'a', $content );
	}

	public function testTabContent_WhenInStudentSubtabAndHasAnEmailOfThatType_ReturnsContentWithTheEmail() {
		/* Arrange. */
		$post               = $this->factory->email->create_and_get();
		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );
		$_GET['subtab']     = 'student';

		update_post_meta( $post->ID, 'sensei_email_type', 'student' );

		/* Act. */
		$content = $email_settings_tab->tab_content( '', 'email-notification-settings' );

		/* Assert. */
		self::assertStringContainsString( $post->post_title, $content );
	}

	public function testTabContent_WhenInStudentSubtabAndHasAnEmailOfAnotherType_ReturnsContentWithoutTheEmail() {
		/* Arrange. */
		$post               = $this->factory->email->create_and_get();
		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );
		$_GET['subtab']     = 'student';

		update_post_meta( $post->ID, 'sensei_email_type', 'teacher' );

		/* Act. */
		$content = $email_settings_tab->tab_content( '', 'email-notification-settings' );

		/* Assert. */
		self::assertStringNotContainsString( $post->post_title, $content );
	}

	public function testTabContent_WhenInTeacherSubtabAndHasAnEmailOfThatType_ReturnsContentWithTheEmail() {
		/* Arrange. */
		$post               = $this->factory->email->create_and_get();
		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );
		$_GET['subtab']     = 'teacher';

		update_post_meta( $post->ID, 'sensei_email_type', 'teacher' );

		/* Act. */
		$content = $email_settings_tab->tab_content( '', 'email-notification-settings' );

		/* Assert. */
		self::assertStringContainsString( $post->post_title, $content );
	}

	public function testTabContent_WhenInTeacherSubtabAndHasAnEmailOfAnotherType_ReturnsContentWithoutTheEmail() {
		/* Arrange. */
		$post               = $this->factory->email->create_and_get();
		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );
		$_GET['subtab']     = 'teacher';

		update_post_meta( $post->ID, 'sensei_email_type', 'student' );

		/* Act. */
		$content = $email_settings_tab->tab_content( '', 'email-notification-settings' );

		/* Assert. */
		self::assertStringNotContainsString( $post->post_title, $content );
	}

	public function testTabContent_WhenInSettingsSubtab_ReturnsContentWithSettingsForm() {
		/* Arrange. */
		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );
		$_GET['subtab']     = 'settings';

		/* Act. */
		$content = $email_settings_tab->tab_content( '', 'email-notification-settings' );

		/* Assert. */
		self::assertStringContainsString(
			'<form id="email-notification-settings-form" action="options.php" method="post">',
			$content
		);
	}

	/**
	 * Test hidden fields are added to the form.
	 *
	 * @dataProvider provideTabContent_WhenInSettingsSubtab_ReturnsContentWithHiddenFields
	 */
	public function testTabContent_WhenInSettingsSubtab_ReturnsContentWithHiddenFields( $expected_field ) {
		/* Arrange. */
		$settings = $this->createMock( Sensei_Settings::class );
		$settings
			->method( 'get_settings' )
			->willReturn(
				[
					'email_from_name'    => 'a',
					'email_from_address' => 'b',
					'hidden_field1'      => 'c',
					'hidden_field2'      => 'd',
					'hidden_field3'      => [ 'e', 'f' ],
				]
			);

		$email_settings_tab = new Email_Settings_Tab( $settings );
		$_GET['subtab']     = 'settings';

		/* Act. */
		$content = $email_settings_tab->tab_content( '', 'email-notification-settings' );

		/* Assert. */
		self::assertStringContainsString( $expected_field, $content );
	}

	public function provideTabContent_WhenInSettingsSubtab_ReturnsContentWithHiddenFields(): array {
		return [
			[ "<input type='hidden' name='option_page' value='sensei-settings' />" ],
			[ '<input type="hidden" name="action" value="update" />' ],
			[ '<input name="sensei-settings[hidden_field1]" type="hidden" value="c" />' ],
			[ '<input name="sensei-settings[hidden_field2]" type="hidden" value="d" />' ],
			[ '<input name="sensei-settings[hidden_field3][]" type="hidden" value="e" />' ],
			[ '<input name="sensei-settings[hidden_field3][]" type="hidden" value="f" />' ],
		];
	}

	/**
	 * Test email hidden fields are not added to the form.
	 *
	 * @dataProvider provideTabContent_WhenInSettingsSubtab_ReturnsContentWithHiddenEmailFields
	 */
	public function testTabContent_WhenInSettingsSubtab_ReturnsContentWithoutHiddenEmailFields( $expected_field ) {
		/* Arrange. */
		$settings = $this->createMock( Sensei_Settings::class );
		$settings
			->method( 'get_settings' )
			->willReturn(
				[
					'email_from_name'    => 'a',
					'email_from_address' => 'b',
					'hidden_field1'      => 'c',
					'hidden_field2'      => 'd',
				]
			);

		$email_settings_tab = new Email_Settings_Tab( $settings );
		$_GET['subtab']     = 'settings';

		/* Act. */
		$content = $email_settings_tab->tab_content( '', 'email-notification-settings' );

		/* Assert. */
		self::assertStringNotContainsString( $expected_field, $content );
	}

	public function provideTabContent_WhenInSettingsSubtab_ReturnsContentWithHiddenEmailFields(): array {
		return [
			[ '<input name="sensei-settings[email_from_name]" type="hidden"' ],
			[ '<input name="sensei-settings[email_from_address]" type="hidden"' ],
		];
	}

	public function testTabContent_WhenInSettingsSubtab_ReturnsContentWithSubmitButton() {
		/* Arrange. */
		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );
		$_GET['subtab']     = 'settings';

		/* Act. */
		$content = $email_settings_tab->tab_content( '', 'email-notification-settings' );

		/* Assert. */
		self::assertStringContainsString( '<input type="submit" name="submit" ', $content );
	}
}

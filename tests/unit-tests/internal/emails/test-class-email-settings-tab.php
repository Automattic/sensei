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
	/**
	 * Factory for creating test data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

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
		$priority = has_filter( 'sensei_settings_content', [ $email_settings_tab, 'get_content' ] );
		self::assertSame( 10, $priority );
	}

	public function testGetContent_WhenCalledWithEmailNotificationSettings_ReturnsContentWithTable() {
		/* Arrange. */
		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );

		/* Act. */
		$content = $email_settings_tab->get_content( 'email-notification-settings', 'a' );

		/* Assert. */
		self::assertStringContainsString( '<table', $content );
	}

	public function testGetContent_WhenCalledWithAnotherTab_ReturnsEmptyContent() {
		/* Arrange. */
		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );

		/* Act. */
		$content = $email_settings_tab->get_content( 'other-tab', '' );

		/* Assert. */
		self::assertSame( '', $content );
	}

	public function testGetContent_WhenInStudentSubtabAndHasAnEmailOfThatType_ReturnsContentWithTheEmail() {
		/* Arrange. */
		$post               = $this->factory->email->create_and_get();
		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );
		$_GET['subtab']     = 'student';

		update_post_meta( $post->ID, '_sensei_email_type', 'student' );
		update_post_meta( $post->ID, '_sensei_email_description', 'description' );

		/* Act. */
		$content = $email_settings_tab->get_content( 'email-notification-settings', '' );

		/* Assert. */
		self::assertStringContainsString( $post->post_title, $content );
	}

	public function testGetContent_WhenInStudentSubtabAndHasAnEmailOfAnotherType_ReturnsContentWithoutTheEmail() {
		/* Arrange. */
		$post               = $this->factory->email->create_and_get();
		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );
		$_GET['subtab']     = 'student';

		update_post_meta( $post->ID, '_sensei_email_type', 'teacher' );

		/* Act. */
		$content = $email_settings_tab->get_content( 'email-notification-settings', '' );

		/* Assert. */
		self::assertStringNotContainsString( $post->post_title, $content );
	}

	public function testGetContent_WhenInTeacherSubtabAndHasAnEmailOfThatType_ReturnsContentWithTheEmail() {
		/* Arrange. */
		$post               = $this->factory->email->create_and_get();
		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );
		$_GET['subtab']     = 'teacher';

		update_post_meta( $post->ID, '_sensei_email_type', 'teacher' );
		update_post_meta( $post->ID, '_sensei_email_description', 'description' );

		/* Act. */
		$content = $email_settings_tab->get_content( 'email-notification-settings', '' );

		/* Assert. */
		self::assertStringContainsString( $post->post_title, $content );
	}

	public function testGetContent_WhenInTeacherSubtabAndHasAnEmailOfAnotherType_ReturnsContentWithoutTheEmail() {
		/* Arrange. */
		$post               = $this->factory->email->create_and_get();
		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );
		$_GET['subtab']     = 'teacher';

		update_post_meta( $post->ID, '_sensei_email_type', 'student' );

		/* Act. */
		$content = $email_settings_tab->get_content( 'email-notification-settings', '' );

		/* Assert. */
		self::assertStringNotContainsString( $post->post_title, $content );
	}

	public function testGetContent_WhenInSettingsSubtab_ReturnsContentWithSettingsForm() {
		/* Arrange. */
		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );
		$_GET['subtab']     = 'settings';

		/* Act. */
		$content = $email_settings_tab->get_content( 'email-notification-settings', '' );

		/* Assert. */
		self::assertStringContainsString(
			'<form id="email-notification-settings-form" action="options.php" method="post">',
			$content
		);
	}

	/**
	 * Test hidden fields are added to the form.
	 *
	 * @dataProvider provideGetContent_WhenInSettingsSubtab_ReturnsContentWithHiddenFields
	 */
	public function testGetContent_WhenInSettingsSubtab_ReturnsContentWithHiddenFields( $expected_field ) {
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
		$content = $email_settings_tab->get_content( 'email-notification-settings', '' );

		/* Assert. */
		self::assertStringContainsString( $expected_field, $content );
	}

	public function provideGetContent_WhenInSettingsSubtab_ReturnsContentWithHiddenFields(): array {
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
	 * @dataProvider provideGetContent_WhenInSettingsSubtab_ReturnsContentWithHiddenEmailFields
	 */
	public function testGetContent_WhenInSettingsSubtab_ReturnsContentWithoutHiddenEmailFields( $expected_field ) {
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
		$content = $email_settings_tab->get_content( 'email-notification-settings', '' );

		/* Assert. */
		self::assertStringNotContainsString( $expected_field, $content );
	}

	public function provideGetContent_WhenInSettingsSubtab_ReturnsContentWithHiddenEmailFields(): array {
		return [
			[ '<input name="sensei-settings[email_from_name]" type="hidden"' ],
			[ '<input name="sensei-settings[email_from_address]" type="hidden"' ],
		];
	}

	public function testGetContent_WhenInSettingsSubtab_ReturnsContentWithSubmitButton() {
		/* Arrange. */
		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );
		$_GET['subtab']     = 'settings';

		/* Act. */
		$content = $email_settings_tab->get_content( 'email-notification-settings', '' );

		/* Assert. */
		self::assertStringContainsString( '<input type="submit" name="submit" ', $content );
	}

	public function testTabContent_WhenInSettingsSubtab_HasReplyToInContent() {
		/* Arrange. */
		$settings = $this->createMock( Sensei_Settings::class );
		$settings
			->method( 'get_settings' )
			->willReturn(
				[
					'email_reply_to_name' => [
						'name' => __( '"Reply To" Name', 'sensei-lms' ),
						'type' => 'email',
					],
				],
				[
					'email_reply_to_address' => [
						'name' => __( '"Reply To" Address', 'sensei-lms' ),
						'type' => 'email',
					],
				]
			);

		$email_settings_tab = new Email_Settings_Tab( $settings );
		$email_settings_tab->init();
		Sensei()->settings->general_init();
		Sensei()->settings->settings_fields();
		$_GET['subtab'] = 'settings';

		/* Act. */
		$content = $email_settings_tab->get_content( 'email-notification-settings' );

		/* Assert. */
		self::assertStringContainsString( '<input id="email_reply_to_address" name="sensei-settings[email_reply_to_address]" size="40" type="email"', $content );
		self::assertStringContainsString( '<input id="email_reply_to_name" name="sensei-settings[email_reply_to_name]" size="40" ', $content );
	}

	public function testTabContent_WhenInSettingsSubtab_HasEmailFromAddressAsTypeEmail() {
		/* Arrange. */
		$settings = $this->createMock( Sensei_Settings::class );
		$settings
			->method( 'get_settings' )
			->willReturn(
				[
					'email_from_address' => 'a',
				]
			);

		$email_settings_tab = new Email_Settings_Tab( $settings );
		$_GET['subtab']     = 'settings';

		/* Act. */
		$content = $email_settings_tab->get_content( 'email-notification-settings' );

		/* Assert. */
		self::assertStringContainsString( '<input id="email_from_address" name="sensei-settings[email_from_address]" size="40" type="email"', $content );
	}

	public function testTabContent_WhenInSettingsSubtabAndMailPoetIsActive_HasMailPoetSettingsLink() {
		/* Arrange. */
		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );
		$_GET['subtab']     = 'settings';

		update_option( 'active_plugins', [ 'mailpoet/mailpoet.php' ] );

		/* Act. */
		$content = $email_settings_tab->get_content( 'email-notification-settings' );

		/* Assert. */
		self::assertStringContainsString( 'MailPoet Lists', $content );
	}

	public function testTabContent_WhenInSettingsSubtabAndMailPoetIsNotActive_HasInstallMailPoetLink() {
		/* Arrange. */
		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );
		$_GET['subtab']     = 'settings';

		/* Act. */
		$content = $email_settings_tab->get_content( 'email-notification-settings' );

		/* Assert. */
		self::assertStringContainsString( 'Install MailPoet', $content );
	}

	public function testTabContent_WhenInSettingsSubtabAndAutomateWooIsActive_HasAutomateWooSettingsLink() {
		/* Arrange. */
		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );
		$_GET['subtab']     = 'settings';

		update_option( 'active_plugins', [ 'automatewoo/automatewoo.php' ] );

		/* Act. */
		$content = $email_settings_tab->get_content( 'email-notification-settings' );

		/* Assert. */
		self::assertStringContainsString( 'AutomateWoo Settings', $content );
	}

	public function testTabContent_WhenInSettingsSubtabAndAutomateWooIsNotActive_HasGetAutomateWooLink() {
		/* Arrange. */
		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );
		$_GET['subtab']     = 'settings';

		/* Act. */
		$content = $email_settings_tab->get_content( 'email-notification-settings' );

		/* Assert. */
		self::assertStringContainsString( 'Get AutomateWoo', $content );
	}

	public function testRenderTabs_NonEmailTabGiven_RendersNothing() {

		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );

		ob_start();
		$email_settings_tab->render_tabs( 'a' );
		$rendered = ob_get_clean();

		self::assertEmpty( $rendered );
	}

	public function testRenderTabs_EmailTabGiven_RendersTabs() {

		$settings           = $this->createMock( Sensei_Settings::class );
		$email_settings_tab = new Email_Settings_Tab( $settings );

		ob_start();
		$email_settings_tab->render_tabs( 'email-notification-settings' );
		$rendered = ob_get_clean();

		self::assertStringContainsString( 'Student Emails', $rendered );
		self::assertStringContainsString( 'Teacher Emails', $rendered );
		self::assertStringContainsString( 'Settings', $rendered );
	}
}

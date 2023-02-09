<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Settings_Tab;
use Sensei_Factory;

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

	public function testTabContent_WhenInStudentSubtabAndHasAnEmailOfThatGroup_ReturnsContantWithTheEmail() {
		/* Arrange. */
		$post               = $this->factory->email->create_and_get();
		$email_settings_tab = new Email_Settings_Tab();
		$_GET['subtab']     = 'student';

		update_post_meta( $post->ID, 'sensei_email_group', 'student' );

		/* Act. */
		$content = $email_settings_tab->tab_content( '', 'email-notification-settings' );

		/* Assert. */
		self::assertStringContainsString( $post->post_title, $content );
	}

	public function testTabContent_WhenInStudentSubtabAndHasAnEmailOfAnotherGroup_ReturnsContentWithoutTheEmail() {
		/* Arrange. */
		$post               = $this->factory->email->create_and_get();
		$email_settings_tab = new Email_Settings_Tab();
		$_GET['subtab']     = 'student';

		update_post_meta( $post->ID, 'sensei_email_group', 'teacher' );

		/* Act. */
		$content = $email_settings_tab->tab_content( '', 'email-notification-settings' );

		/* Assert. */
		self::assertStringNotContainsString( $post->post_title, $content );
	}

	public function testTabContent_WhenInTeacherSubtabAndHasAnEmailOfThatGroup_ReturnsContantWithTheEmail() {
		/* Arrange. */
		$post               = $this->factory->email->create_and_get();
		$email_settings_tab = new Email_Settings_Tab();
		$_GET['subtab']     = 'teacher';

		update_post_meta( $post->ID, 'sensei_email_group', 'teacher' );

		/* Act. */
		$content = $email_settings_tab->tab_content( '', 'email-notification-settings' );

		/* Assert. */
		self::assertStringContainsString( $post->post_title, $content );
	}

	public function testTabContent_WhenInTeacherSubtabAndHasAnEmailOfAnotherGroup_ReturnsContentWithoutTheEmail() {
		/* Arrange. */
		$post               = $this->factory->email->create_and_get();
		$email_settings_tab = new Email_Settings_Tab();
		$_GET['subtab']     = 'teacher';

		update_post_meta( $post->ID, 'sensei_email_group', 'student' );

		/* Act. */
		$content = $email_settings_tab->tab_content( '', 'email-notification-settings' );

		/* Assert. */
		self::assertStringNotContainsString( $post->post_title, $content );
	}
}

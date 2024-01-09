<?php
namespace SenseiTest;

use Sensei_Settings_API;

/**
 * File with class for testing Sensei Settings API.
 *
 * @covers \Sensei_Settings_Api
 */
class Sensei_Settings_Api_Test extends \WP_UnitTestCase {
	private $original_settings;

	protected function setUp(): void {
		parent::setUp();

		$this->original_settings = get_option( 'sensei-settings' );
		update_option( 'sensei-settings', array( 'test' => 'a' ) );
	}

	protected function tearDown(): void {
		parent::tearDown();

		update_option( 'sensei-settings', $this->original_settings );
	}

	public function testFormFieldText_MultipleSetToTrue_OutputsMupltipleProperty() {
		/** Arrange. */
		$settings = new Sensei_Settings_Api();

		/** Act. */
		ob_start();
		$settings->form_field_text(
			array(
				'key'  => 'test',
				'data' => array(
					'multiple' => true,
				),
			)
		);
		$output = ob_get_clean();

		/** Assert. */
		$this->assertStringContainsString( ' multiple ', $output );
	}

	public function testFormFieldText_MultipleNotSet_DoesntOutputMupltipleProperty() {
		/** Arrange. */
		$settings = new Sensei_Settings_Api();

		/** Act. */
		ob_start();
		$settings->form_field_text(
			array(
				'key' => 'test',
			)
		);
		$output = ob_get_clean();

		/** Assert. */
		$this->assertStringNotContainsString( ' multiple ', $output );
	}

	public function testSettingsTabs_WhenNoTabParam_AddsTheCurrentClassToTheDefaultTabLink() {
		/** Arrange. */
		$settings           = new Sensei_Settings_Api();
		$settings->has_tabs = true;
		$settings->sections = array(
			'default-settings' => array(
				'name' => 'Default Settings',
			),
			'other-settings'   => array(
				'name' => 'Other Settings',
			),
		);

		/** Act. */
		ob_start();
		$settings->general_init();
		$settings->settings_tabs();
		$tabs = ob_get_clean();

		/** Assert. */
		$this->assertStringContainsString( '<a href="http://example.org/wp-admin/admin.php?page=sensei-settings&#038;tab=default-settings" class="tab current">Default Settings</a>', $tabs );
	}

	public function testSettingsTabs_WhenHasTabParam_AddsTheCurrentClassToTheTabLink() {
		/** Arrange. */
		$settings           = new Sensei_Settings_Api();
		$settings->has_tabs = true;
		$settings->sections = array(
			'default-settings' => array(
				'name' => 'Default Settings',
			),
			'other-settings'   => array(
				'name' => 'Other Settings',
			),
		);

		$_GET['tab'] = 'other-settings';

		/** Act. */
		ob_start();
		$settings->general_init();
		$settings->settings_tabs();
		$tabs = ob_get_clean();

		/** Assert. */
		$this->assertStringContainsString( '<a href="http://example.org/wp-admin/admin.php?page=sensei-settings&#038;tab=other-settings" class="tab current">Other Settings</a>', $tabs );
	}

	public function testSettingsTabs_WhenTabIsExternal_AddsTheExternalClassToTheTabLink() {
		/** Arrange. */
		$settings           = new Sensei_Settings_Api();
		$settings->has_tabs = true;
		$settings->sections = array(
			'other-settings' => array(
				'name'     => 'Other Settings',
				'external' => true,
			),
		);

		/** Act. */
		ob_start();
		$settings->general_init();
		$settings->settings_tabs();
		$tabs = ob_get_clean();

		/** Assert. */
		$this->assertStringContainsString( '<a href="http://example.org/wp-admin/admin.php?page=sensei-settings&#038;tab=other-settings" class="tab external">Other Settings</a>', $tabs );
	}
}

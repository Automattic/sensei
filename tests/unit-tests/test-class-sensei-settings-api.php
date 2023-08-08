<?php
namespace SenseiTest;

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
		$settings = new \Sensei_Settings_Api();

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
		$settings = new \Sensei_Settings_Api();

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
}

<?php
namespace SenseiTest\WPML;

use Sensei\WPML\Settings;

/**
 * Class Settings_Test
 *
 * @covers \Sensei\WPML\Settings
 */
class Settings_Test extends \WP_UnitTestCase {

	public function testAddFields_WhenCalled_DefaultsTheSlugTranslationOptionToEnabled() {
		/* Arrange. */
		$settings = new Settings();

		/* Act. */
		$fields = $settings->add_fields( array() );

		/* Assert. */
		$this->assertTrue( $fields['wpml_slug_translation']['default'] );
	}
}

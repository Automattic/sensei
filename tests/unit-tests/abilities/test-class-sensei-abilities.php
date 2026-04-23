<?php
/**
 * Tests for Sensei_Abilities.
 *
 * @covers Sensei_Abilities
 */
class Sensei_Abilities_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	public function testInit_Always_RegistersCategoryHook() {
		Sensei_Abilities::init();

		$this->assertNotFalse(
			has_action( 'wp_abilities_api_categories_init', array( Sensei_Abilities::class, 'register_category' ) )
		);
	}

	public function testInit_Always_RegistersAbilitiesHook() {
		Sensei_Abilities::init();

		$this->assertNotFalse(
			has_action( 'wp_abilities_api_init', array( Sensei_Abilities::class, 'register_abilities' ) )
		);
	}
}

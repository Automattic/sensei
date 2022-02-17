<?php
/**
 * This file contains the Sensei_Extensions_Test class.
 *
 * @package sensei
 */

/**
 * Tests for Sensei_Extensions class.
 */
class Sensei_Extensions_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	public function tearDown() {
		parent::tearDown();

		global $submenu;
		unset( $submenu['edit.php?post_type=course'] );
	}

	/**
	 * Testing the Sensei Extensions class to make sure it is loaded.
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( 'Sensei_Extensions' ), 'Sensei Extensions class does not exist' );
	}

	/**
	 * Tests that extensions with update are counted correctly.
	 */
	public function testCountExtensionsWithUpdate() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Skip test for multisite because admin does not have the install_plugins capability used to add the submenu.' );
		}

		$this->login_as_admin();

		$extensions = [
			(object) [
				'product_slug' => 'any-A',
				'version'      => '1.0.1',
				'plugin_file'  => 'any-folder-a/sensei-extension-A.php',
			],
			(object) [
				'product_slug' => 'any-B',
				'version'      => '1.0.1',
				'plugin_file'  => 'any-folder-b/sensei-extension-B.php',
			],
			(object) [
				'product_slug' => 'any-C',
				'version'      => '1.0.1',
				'plugin_file'  => 'any-folder-c/sensei-extension-C.php',
			],
			(object) [
				'product_slug' => 'any-E',
				'version'      => '1.0.1',
				'plugin_file'  => 'any-folder-e/sensei-extension-E.php',
			],
		];

		$cache_plugins = [
			'' => [
				'any-folder-a/sensei-extension-A.php' => [
					'Version' => '1.0.0',
				],
				'any-folder-c/sensei-extension-C.php' => [
					'Version' => '1.0.0',
				],
				'any-folder-d/sensei-extension-D.php' => [
					'Version' => '1.0.0',
				],
				'any-folder-e/sensei-extension-E.php' => [
					'Version' => '1.0.1',
				],
			],
		];

		set_transient( 'sensei_extensions_' . md5( 'plugin||' . determine_locale() . '|[]|' . Sensei_Extensions::SENSEILMS_PRODUCTS_API_BASE_URL ), $extensions );
		wp_cache_set( 'plugins', $cache_plugins, 'plugins' );

		Sensei_Extensions::instance()->add_admin_menu_item();

		global $submenu;

		$this->assertTrue(
			in_array( 'Extensions <span class="awaiting-mod">2</span>', end( $submenu['edit.php?post_type=course'] ), true ),
			'Should count 2 available updates'
		);
	}

	/**
	 * Tests that extensions without update don't show counter.
	 */
	public function testCountExtensionsWithoutUpdate() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Skip test for multisite because admin does not have the install_plugins capability used to add the submenu.' );
		}

		$this->login_as_admin();

		$extensions = [
			(object) [
				'product_slug' => 'any-A',
				'version'      => '1.0.1',
				'plugin_file'  => 'any-folder-a/sensei-extension-A.php',
			],
		];

		$cache_plugins = [
			'' => [
				'any-folder-a/sensei-extension-A.php' => [
					'Version' => '1.0.1',
				],
			],
		];

		set_transient( 'sensei_extensions_' . md5( 'plugin||' . determine_locale() . '|[]|' . Sensei_Extensions::SENSEILMS_PRODUCTS_API_BASE_URL ), $extensions );
		wp_cache_set( 'plugins', $cache_plugins, 'plugins' );

		Sensei_Extensions::instance()->add_admin_menu_item();

		global $submenu;

		$this->assertTrue(
			in_array( 'Extensions', end( $submenu['edit.php?post_type=course'] ), true ),
			'Should not have counter when there is no available update'
		);
	}
}

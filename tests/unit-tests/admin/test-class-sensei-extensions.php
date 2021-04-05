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

	/**
	 * Test specific setup.
	 */
	public function setUp() {
		parent::setUp();

		add_filter( 'sensei_feature_flag_extensions_management_enhancement', '__return_true' );
	}

	/**
	 * Clean up after the test.
	 */
	public function tearDown() {
		parent::tearDown();

		remove_filter( 'sensei_feature_flag_extensions_management_enhancement', '__return_true' );
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
				'product_slug' => 'sensei-extension-A',
				'version'      => '1.0.1',
			],
			(object) [
				'product_slug' => 'sensei-extension-B',
				'version'      => '1.0.1',
			],
			(object) [
				'product_slug' => 'sensei-extension-C',
				'version'      => '1.0.1',
			],
			(object) [
				'product_slug' => 'sensei-extension-E',
				'version'      => '1.0.1',
			],
		];

		$cache_plugins = [
			'' => [
				'any-folder-a/sensei-extension-A.php' => [
					'Version' => '1.0.0',
				],
				'any-folder-a/sensei-extension-C.php' => [
					'Version' => '1.0.0',
				],
				'any-folder-a/sensei-extension-D.php' => [
					'Version' => '1.0.0',
				],
				'any-folder-a/sensei-extension-E.php' => [
					'Version' => '1.0.1',
				],
			],
		];

		set_transient( 'sensei_extensions_' . md5( 'plugin||[]' ), $extensions );
		wp_cache_set( 'plugins', $cache_plugins, 'plugins' );

		Sensei_Extensions::instance()->add_admin_menu_item();

		global $submenu;

		$this->assertTrue(
			in_array( 'Extensions <span class="awaiting-mod">2</span>', end( $submenu['sensei'] ), true ),
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
				'product_slug' => 'sensei-extension-A',
				'version'      => '1.0.1',
			],
		];

		$cache_plugins = [
			'' => [
				'any-folder-a/sensei-extension-A.php' => [
					'Version' => '1.0.1',
				],
			],
		];

		set_transient( 'sensei_extensions_' . md5( 'plugin||[]' ), $extensions );
		wp_cache_set( 'plugins', $cache_plugins, 'plugins' );

		Sensei_Extensions::instance()->add_admin_menu_item();

		global $submenu;

		$this->assertTrue(
			in_array( 'Extensions', end( $submenu['sensei'] ), true ),
			'Should not have counter when there is no available update'
		);
	}
}

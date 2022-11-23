<?php
/**
 * Sensei plugins installation tests
 *
 * @package sensei-lms
 * @since   3.1.0
 */

/**
 * Class for Sensei_Plugins_Installation tests.
 */
class Sensei_Plugins_Installation_Test extends WP_Test_REST_TestCase {
	/**
	 * Tests that get installing plugins returns data from transient.
	 *
	 * @covers Sensei_Plugins_Installation::get_installing_plugins
	 */
	public function testGetInstallingPlugins() {
		$expected_installing_plugins = [ 'test' ];

		$this->set_installing_transient( $expected_installing_plugins );

		$installing_plugins = Sensei_Plugins_Installation::instance()->get_installing_plugins();

		$this->assertEquals( $expected_installing_plugins, $installing_plugins );
	}

	/**
	 * Tests that get installing plugins returns empty array from empty transient.
	 *
	 * @covers Sensei_Plugins_Installation::get_installing_plugins
	 */
	public function testGetInstallingPluginsWithEmptyTransient() {
		$expected_installing_plugins = [];
		$installing_plugins          = Sensei_Plugins_Installation::instance()->get_installing_plugins();

		$this->assertEquals( $expected_installing_plugins, $installing_plugins );
	}

	/**
	 * Tests that set installing plugins save object in the transient.
	 *
	 * @covers Sensei_Plugins_Installation::set_installing_plugins
	 */
	public function testSetInstallingPlugins() {
		$expected_installing_plugins = [ 'test' ];
		$installing_plugins          = Sensei_Plugins_Installation::instance()->set_installing_plugins( $expected_installing_plugins );

		$installing_transient = get_transient( Sensei_Plugins_Installation::INSTALLING_PLUGINS_TRANSIENT );

		$this->assertEquals( $expected_installing_plugins, $installing_transient );
	}

	/**
	 * Tests that install plugins clean errors and add new installation to the deferred queue.
	 *
	 * @covers Sensei_Plugins_Installation::set_installing_plugins
	 */
	public function testInstallPlugins() {
		$this->set_installing_transient(
			[
				(object) [
					'product_slug' => 'already-installing',
				],
				(object) [
					'product_slug' => 'installing-with-error',
					'error'        => 'Error message',
					'status'       => 'error',
				],
				(object) [
					'product_slug' => 'installing-with-error-2',
					'error'        => 'Error message',
					'status'       => 'error',
				],
			]
		);

		$plugins_to_install = [
			(object) [
				'product_slug' => 'already-installing',
			],
			(object) [
				'product_slug' => 'installing-with-error',
			],
			(object) [
				'product_slug' => 'new-installation',
			],
		];

		$expected_installing_plugins = [
			(object) [
				'product_slug' => 'already-installing',
			],
			(object) [
				'product_slug' => 'installing-with-error',
			],
			(object) [
				'product_slug' => 'installing-with-error-2',
				'error'        => 'Error message',
				'status'       => 'error',
			],
			(object) [
				'product_slug' => 'new-installation',
			],
		];

		Sensei_Plugins_Installation::instance()->install_plugins( $plugins_to_install );

		$installing_plugins = get_transient( Sensei_Plugins_Installation::INSTALLING_PLUGINS_TRANSIENT );

		$this->assertEquals( $expected_installing_plugins, $installing_plugins );
	}

	/**
	 * Tests that error is saved to transient.
	 *
	 * @covers Sensei_Plugins_Installation::save_error
	 */
	public function testSaveError() {
		$this->set_installing_transient(
			[
				(object) [
					'product_slug' => 'without-error',
				],
				(object) [
					'product_slug' => 'receive-error',
				],
			]
		);

		$method = new ReflectionMethod( Sensei_Plugins_Installation::class, 'save_error' );
		$method->setAccessible( true );

		$method->invoke( Sensei_Plugins_Installation::instance(), 'receive-error', 'Error message' );
		$method->invoke( Sensei_Plugins_Installation::instance(), 'invalid-slug', 'Error message' );

		$expected_installing_plugins = [
			(object) [
				'product_slug' => 'without-error',
			],
			(object) [
				'product_slug' => 'receive-error',
				'error'        => 'Error message',
			],
		];
		$installing_plugins          = get_transient( Sensei_Plugins_Installation::INSTALLING_PLUGINS_TRANSIENT );

		$this->assertEquals( $expected_installing_plugins, $installing_plugins );
	}

	/**
	 * Tests that plugin is removed from transient when installation is completed.
	 *
	 * @covers Sensei_Plugins_Installation::complete_installation
	 */
	public function testCompleteInstallation() {
		$this->set_installing_transient(
			[
				(object) [
					'product_slug' => 'not-complete',
				],
				(object) [
					'product_slug' => 'completed',
				],
			]
		);

		$method = new ReflectionMethod( Sensei_Plugins_Installation::class, 'complete_installation' );
		$method->setAccessible( true );

		$method->invoke( Sensei_Plugins_Installation::instance(), 'completed' );
		$method->invoke( Sensei_Plugins_Installation::instance(), 'invalid' );

		$expected_installing_plugins = [
			(object) [
				'product_slug' => 'not-complete',
			],
		];
		$installing_plugins          = get_transient( Sensei_Plugins_Installation::INSTALLING_PLUGINS_TRANSIENT );

		$this->assertEquals( $expected_installing_plugins, $installing_plugins );
	}

	/**
	 * Set installing transient.
	 *
	 * @param array $data Data to save in the plugins installing transient.
	 */
	private function set_installing_transient( $data ) {
		set_transient( Sensei_Plugins_Installation::INSTALLING_PLUGINS_TRANSIENT, $data, DAY_IN_SECONDS );
	}
}

<?php
/**
 * This file contains the Sensei_Usage_Tracking_Test class.
 *
 * @package sensei
 */

/**
 * Tests for the class `Sensei_Usage_Tracking`.
 *
 * @group usage-tracking
 */
class Sensei_Usage_Tracking_Test extends WP_UnitTestCase {

	/**
	 * Set up before each test.
	 */
	public function setUp() {
		parent::setUp();

		Sensei_Test_Events::reset();
	}

	/**
	 * Tests that WCCOM extensions are logged as sensei_plugin_install when activated.
	 *
	 * @covers Sensei_Usage_Tracking::log_wccom_plugin_install
	 */
	public function testWccomInstallSuccessLogged() {
		// Mock WooCommerce plugin information.
		set_transient(
			Sensei_Setup_Wizard::WC_INFORMATION_TRANSIENT,
			(object) [
				'product_slug' => 'woocommerce',
				'title'        => 'WooCommerce',
				'excerpt'      => 'Lorem ipsum',
				'plugin_file'  => 'woocommerce/woocommerce.php',
				'link'         => 'https://wordpress.org/plugins/woocommerce',
				'unselectable' => true,
			],
			DAY_IN_SECONDS
		);

		$get_sensei_extensions = wp_json_encode(
			[
				'products' => [
					(object) [
						'product_slug'     => 'test-wccom-plugin',
						'plugin_file'      => 'test-wccom-plugin/test-wccom-plugin.php',
						'wccom_product_id' => '00000',
					],
				],
			]
		);

		add_filter(
			'pre_http_request',
			function() use ( $get_sensei_extensions ) {
				return [ 'body' => $get_sensei_extensions ];
			}
		);

		do_action( 'activated_plugin', 'test-wccom-plugin/test-wccom-plugin.php' );

		$events = Sensei_Test_Events::get_logged_events( 'sensei_plugin_install' );

		$this->assertEquals( 'test-wccom-plugin', $events[0]['url_args']['slug'] );
	}
}

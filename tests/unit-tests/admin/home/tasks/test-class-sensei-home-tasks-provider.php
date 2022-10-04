<?php
/**
 * This file contains the Sensei_Home_Tasks_Provider_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Tests for Sensei_Home_Tasks_Provider class.
 *
 * @covers Sensei_Home_Tasks_Provider
 */
class Sensei_Home_Tasks_Provider_Test extends WP_UnitTestCase {

	/**
	 * The provider under test.
	 *
	 * @var Sensei_Home_Tasks_Provider
	 */
	private $provider;

	/**
	 * Setup.
	 */
	public function setUp() {
		parent::setUp();
		$this->provider = new Sensei_Home_Tasks_Provider();
	}

	/**
	 * Test that provider returns Sensei_Home_Task_Setup_Site task.
	 */
	public function testProviderReturnsSetupSiteTask() {
		$tasks = $this->provider->get();

		$this->assertInstanceOf( Sensei_Home_Tasks::class, $tasks );
		$items = $tasks->get_items();
		$this->assertIsArray( $items );
		$this->assertCount( 1, $items, 'At the moment only one task is expected.' );
		$this->assertInstanceOf( Sensei_Home_Task_Setup_Site::class, $items[0] );
	}
}

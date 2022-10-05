<?php
/**
 * This file contains the Sensei_Home_Tasks_Provider_Test class.
 *
 * @package sensei
 */


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
	 * Test that provider returns Sensei_Home_Task_Setup_Site task and Sensei_Home_Task_Create_First_Course.
	 */
	public function testProviderReturnsSetupSiteTask() {
		$tasks = $this->provider->get();

		$this->assertInstanceOf( Sensei_Home_Tasks::class, $tasks );
		$items = $tasks->get_items();
		$this->assertIsArray( $items );
		$this->assertCount( 2, $items, 'At the moment only two tasks are expected.' );
		$this->assertInstanceOf( Sensei_Home_Task_Setup_Site::class, $items[0] );
		$this->assertInstanceOf( Sensei_Home_Task_Create_First_Course::class, $items[1] );

	}
}

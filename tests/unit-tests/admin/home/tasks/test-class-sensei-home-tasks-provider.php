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
	const FAKE_TASK_ID = 'fake-task-id';

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
	 * Tear down.
	 */
	public function tearDown() {
		parent::tearDown();

		remove_filter( 'sensei_home_tasks', [ $this, 'overrideWithFakeTask' ] );
	}


	/**
	 * Test that provider returns expected tasks.
	 */
	public function testProviderReturnsExpectedTasks() {
		$result = $this->provider->get();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'items', $result );
		$items = $result['items'];
		$this->assertIsArray( $items );
		$this->assertCount( 2, $items );
		$this->assertArrayHasKey( Sensei_Home_Task_Setup_Site::get_id(), $items );
		$this->assertArrayHasKey( Sensei_Home_Task_Configure_Learning_Mode::get_id(), $items );
	}

	/**
	 * Test that provider tasks can be filtered.
	 */
	public function testProviderAllowsForFilteredTasks() {
		$result = $this->provider->get();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'items', $result );
		$items = $result['items'];
		$this->assertIsArray( $items );
		$this->assertCount( 2, $items );

		add_filter( 'sensei_home_tasks', [ $this, 'overrideWithFakeTask' ] );

		$result = $this->provider->get();
		$items  = $result['items'];
		$this->assertCount( 1, $items );
		$this->assertArrayHasKey( self::FAKE_TASK_ID, $items );
	}

	/**
	 * Override tasks with a single fake one.
	 *
	 * @param array $tasks The original tasks.
	 * @return array
	 */
	public function overrideWithFakeTask( $tasks ) : array {
		return [
			self::FAKE_TASK_ID => [ 'fake' ],
		];
	}
}

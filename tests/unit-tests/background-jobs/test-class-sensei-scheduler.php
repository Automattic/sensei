<?php
/**
 * This file contains the Sensei_Scheduler_Test class.
 *
 * @package sensei
 */

/**
 * Tests for Sensei_Scheduler class.
 *
 * @group background-jobs
 */
class Sensei_Scheduler_Test extends WP_UnitTestCase {
	use Sensei_Scheduler_Test_Helpers;

	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		self::resetScheduler();
		add_filter( 'sensei_scheduler_class', [ __CLASS__, 'scheduler_use_wp_cron' ] );
	}


	/**
	 * Clean up after all tests.
	 */
	public static function tearDownAfterClass() {
		self::restoreShimScheduler();

		return parent::tearDownAfterClass();
	}

	/**
	 * Test that by default it returns the WP Cron scheduler.
	 */
	public function testInstance() {
		$this->assertTrue( Sensei_Scheduler::instance() instanceof Sensei_Scheduler_WP_Cron, 'Scheduler should be handled by the WP cron handler when told to do so' );
	}
}

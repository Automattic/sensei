<?php
/**
 * This file contains the Sensei_Scheduler_WP_Cron_Test class.
 *
 * @package sensei
 */

/**
 * Tests for Sensei_Scheduler_WP_Cron class.
 *
 * @group background-jobs
 */
class Sensei_Scheduler_WP_Cron_Test extends WP_UnitTestCase {
	use Sensei_Scheduler_Test_Helpers;

	private static $original_cron;

	/**
	 * Set up before all tests.
	 */
	public static function setUpBeforeClass() {
		self::resetScheduler();

		self::$original_cron = get_option( 'cron' );

		return parent::setUpBeforeClass();
	}

	/**
	 * Tear down after all tests.
	 */
	public static function tearDownAfterClass() {
		self::restoreScheduler();

		update_option( 'cron', self::$original_cron );

		return parent::tearDownAfterClass();
	}

	/**
	 * Test that by default it returns the WP Cron scheduler.
	 */
	public function testInstance() {
		$this->assertTrue( Sensei_Scheduler::instance() instanceof Sensei_Scheduler_WP_Cron, 'Make sure we are on the right scheduler.' );
	}

	/**
	 * Test scheduling a job results in a single entry.
	 */
	public function testScheduleJob() {
		self::resetCron();

		$job       = new Sensei_Background_Job_Stub();
		$scheduler = Sensei_Scheduler::instance();

		$cron = _get_cron_array();
		$this->assertEquals( 0, count( $cron ) );

		$scheduler->schedule_job( $job );
		$scheduler->schedule_job( $job );

		$cron = _get_cron_array();
		$this->assertEquals( 1, count( $cron ) );
	}

	/**
	 * Tests to make sure it is queued when not complete and not queued when complete.
	 */
	public function testRunNotQueuedWhenComplete() {
		self::resetCron();

		$job       = new Sensei_Background_Job_Stub();
		$scheduler = Sensei_Scheduler::instance();

		$scheduler->schedule_job( $job );
		$scheduler->run( $job );

		$this->assertTrue( $job->did_run );

		$cron = _get_cron_array();
		$this->assertEquals( 1, count( $cron ), 'The job should still be queued as it is not complete' );

		$job->is_complete = true;
		$scheduler->run( $job );

		$cron = _get_cron_array();
		$this->assertEquals( 0, count( $cron ), 'The job should no longer be queued' );
	}

	/**
	 * Tests to make sure it calls callback when complete.
	 */
	public function testRunCallsCallback() {
		$job       = new Sensei_Background_Job_Stub();
		$scheduler = Sensei_Scheduler::instance();

		$did_run_callback = false;
		$callback         = function() use ( &$did_run_callback ) {
			$did_run_callback = true;
		};

		$scheduler->run( $job, $callback );

		$this->assertFalse( $did_run_callback, 'Callback should not be called when not complete' );

		$job->is_complete = true;
		$scheduler->run( $job, $callback );
		$this->assertTrue( $did_run_callback, 'Callback should be called when complete' );
	}

	/**
	 * Tests jobs can be cancelled.
	 */
	public function testCancelScheduledJob() {
		self::resetCron();

		$job       = new Sensei_Background_Job_Stub();
		$scheduler = Sensei_Scheduler::instance();

		$scheduler->schedule_job( $job );

		$result = _get_cron_array();
		$this->assertEquals( 1, count( $result ) );

		$result = _get_cron_array();
		$this->assertEquals( 1, count( $result ) );
	}

	/**
	 * Reset the cron jobs.
	 */
	private function resetCron() {
		update_option( 'cron', [] );
	}
}

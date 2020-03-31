<?php
/**
 * This file contains the Sensei_Scheduler_Action_Scheduler_Test class.
 *
 * @package sensei
 */

/**
 * Tests for Sensei_Scheduler_Action_Scheduler class.
 *
 * @group background-jobs
 */
class Sensei_Scheduler_Action_Scheduler_Test extends WP_UnitTestCase {
	use Sensei_Scheduler_Test_Helpers;

	/**
	 * Tear down the test.
	 */
	public function tearDown() {
		parent::tearDown();

		_as_reset();
	}

	/**
	 * Set up before all tests.
	 */
	public static function setUpBeforeClass() {
		self::createMocks();
		self::resetScheduler();
		add_filter( 'sensei_scheduler_class', [ __CLASS__, 'scheduler_use_action_scheduler' ] );

		return parent::setUpBeforeClass();
	}

	/**
	 * Tear down after all tests.
	 */
	public static function tearDownAfterClass() {
		self::restoreShimScheduler();

		return parent::tearDownAfterClass();
	}

	/**
	 * Test that with the mocks we are using the Action Scheduler.
	 */
	public function testInstance() {
		$this->assertTrue( Sensei_Scheduler::instance() instanceof Sensei_Scheduler_Action_Scheduler, 'With the mocks, we should be using the action scheduler handler.' );
	}

	/**
	 * Test scheduling a job results in a single entry.
	 */
	public function testScheduleJob() {
		$job       = new Sensei_Background_Job_Stub();
		$scheduler = Sensei_Scheduler::instance();

		$scheduler->schedule_job( $job );
		$scheduler->schedule_job( $job );

		$result = _as_get_scheduled_actions( $job->get_name(), [ $job->get_args() ], null );
		$this->assertEquals( 1, count( $result ) );
	}

	/**
	 * Tests to make sure it is queued when not complete and not queued when complete.
	 */
	public function testRunNotQueuedWhenComplete() {
		$job       = new Sensei_Background_Job_Stub();
		$scheduler = Sensei_Scheduler::instance();

		$scheduler->schedule_job( $job );
		$scheduler->run( $job );

		$this->assertTrue( $job->did_run );

		$result = _as_get_scheduled_actions( $job->get_name(), [ $job->get_args() ], null );
		$this->assertEquals( 1, count( $result ), 'The job should still be queued as it is not complete' );

		$job->is_complete = true;
		$scheduler->run( $job );

		$result = _as_get_scheduled_actions( $job->get_name(), [ $job->get_args() ], null );
		$this->assertEquals( 0, count( $result ), 'The job should no longer be queued' );
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
		$job       = new Sensei_Background_Job_Stub();
		$scheduler = Sensei_Scheduler::instance();

		$scheduler->schedule_job( $job );

		$result = _as_get_scheduled_actions( $job->get_name(), [ $job->get_args() ], null );
		$this->assertEquals( 1, count( $result ) );

		$result = _as_get_scheduled_actions( $job->get_name(), [ $job->get_args() ], null );
		$this->assertEquals( 1, count( $result ) );
	}

	/**
	 * Set up mocks.
	 */
	private static function createMocks() {
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/actionscheduler-mocks.php';
	}
}

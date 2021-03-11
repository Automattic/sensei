<?php
/**
 * This file contains the Sensei_Updates_Test class.
 *
 * @package sensei
 */

/**
 * Tests for the class `Sensei_Updates`.
 *
 * @group update-tasks
 */
class Sensei_Updates_Test extends WP_UnitTestCase {
	use Sensei_Scheduler_Test_Helpers;

	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		Sensei_Scheduler_Shim::reset();
		self::restoreShimScheduler();

	}

	/**
	 * Clean up after all tests.
	 */
	public static function tearDownAfterClass() {
		self::resetScheduler();

		return parent::tearDownAfterClass();
	}

	/**
	 * Test to make sure question update fix is enqueued when coming from 3.8.0.
	 */
	public function testFixQuestionsEnqueuedWhenComingFrom38() {
		$updates = new Sensei_Updates( '3.8.0', false, true );
		$updates->run_updates();

		$job            = new Sensei_Update_Fix_Question_Author();
		$next_scheduled = Sensei_Scheduler_Shim::get_next_scheduled( $job );
		$this->assertNotFalse( $next_scheduled );
	}

	/**
	 * Test to make sure question update fix is not enqueued in future.
	 */
	public function testFixQuestionsNotEnqueuedWhenComingFrom39() {
		$updates = new Sensei_Updates( '3.9.0', false, true );
		$updates->run_updates();

		$job            = new Sensei_Update_Fix_Question_Author();
		$next_scheduled = Sensei_Scheduler_Shim::get_next_scheduled( $job );
		$this->assertFalse( $next_scheduled );

		$updates = new Sensei_Updates( '3.9.1', false, true );
		$updates->run_updates();

		$job            = new Sensei_Update_Fix_Question_Author();
		$next_scheduled = Sensei_Scheduler_Shim::get_next_scheduled( $job );
		$this->assertFalse( $next_scheduled );
	}


	/**
	 * Test to make sure question update fix is not enqueued on fresh installs.
	 */
	public function testFixQuestionsNotEnqueuedOnNewInstalls() {
		$updates = new Sensei_Updates( null, true, false );
		$updates->run_updates();

		$job            = new Sensei_Update_Fix_Question_Author();
		$next_scheduled = Sensei_Scheduler_Shim::get_next_scheduled( $job );
		$this->assertFalse( $next_scheduled );

		$updates = new Sensei_Updates( '3.9.1', false, true );
		$updates->run_updates();

		$job            = new Sensei_Update_Fix_Question_Author();
		$next_scheduled = Sensei_Scheduler_Shim::get_next_scheduled( $job );
		$this->assertFalse( $next_scheduled );
	}
}

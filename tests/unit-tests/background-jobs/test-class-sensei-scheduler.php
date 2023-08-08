<?php
/**
 * This file contains the Sensei_Scheduler_Test class.
 *
 * @package sensei
 */

require_once SENSEI_TEST_FRAMEWORK_DIR . '/class-sensei-background-job-stateful-stub.php';

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
	public function setUp(): void {
		parent::setUp();

		self::resetScheduler();
		add_filter( 'sensei_scheduler_class', [ __CLASS__, 'scheduler_use_wp_cron' ] );
	}

	/**
	 * Clean up after all tests.
	 */
	public static function tearDownAfterClass(): void {
		self::restoreShimScheduler();

		parent::tearDownAfterClass();
	}

	/**
	 * Test that by default it returns the WP Cron scheduler.
	 */
	public function testInstance() {
		$this->assertTrue( Sensei_Scheduler::instance() instanceof Sensei_Scheduler_WP_Cron, 'Scheduler should be handled by the WP cron handler when told to do so' );
	}

	/**
	 * Test running a stateful job and making sure it reschedules and saves state.
	 */
	public function testRunStatefulJob() {
		$job_args = [ 'run_for' => 1 ];
		$job_a    = new Sensei_Background_Job_Stateful_Stub( $job_args );
		$run_args = $job_a->get_args();

		$job_a->set_state( 'test', 'value' );
		$job_a->persist();

		$this->assertTrue( Sensei_Scheduler::run_stateful_job( $run_args ) );
		$job_b = new Sensei_Background_Job_Stateful_Stub( $job_args, $job_a->get_id() );

		$this->assertEquals( 'value', $job_b->get_state( 'test' ) );
		$this->assertEquals( 1, $job_b->get_state( 'run' ) );
		$this->assertTrue( Sensei_Scheduler::run_stateful_job( $run_args ) );

		// Job should have been cleaned up.
		$job_c = new Sensei_Background_Job_Stateful_Stub( $job_args, $job_a->get_id() );
		$this->assertEquals( null, $job_c->get_state( 'test' ) );
		$this->assertEquals( null, $job_c->get_state( 'run' ) );
	}

	/**
	 * Test to make sure it doesn't attempt to run jobs of an unexpected class.
	 */
	public function testRunStatefulJobIgnoreUnknownClasses() {
		$this->assertFalse(
			Sensei_Scheduler::run_stateful_job(
				[
					'id'    => 'test',
					'class' => Sensei_Main::class,
				]
			)
		);
	}

	/**
	 * Test to make sure it doesn't attempt to run jobs without an ID.
	 */
	public function testRunStatefulJobIgnoreWithoutId() {
		$this->assertFalse(
			Sensei_Scheduler::run_stateful_job(
				[
					'id'    => '',
					'class' => Sensei_Background_Job_Stateful_Stub::class,
				]
			)
		);
	}

}

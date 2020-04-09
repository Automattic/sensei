<?php

/**
 * Tests for Sensei_Enrolment_Calculation_Scheduler class.
 *
 * @covers Sensei_Enrolment_Learner_Calculation_Scheduler
 * @group course-enrolment
 */
class Sensei_Enrolment_Calculation_Scheduler_Test extends WP_UnitTestCase {
	use Sensei_Scheduler_Test_Helpers;

	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
		Sensei()->deactivation();

		self::restoreShimScheduler();
		Sensei_Scheduler_Shim::reset();
	}

	public function tearDown() {
		parent::tearDown();

		Sensei_Scheduler_Shim::reset();
		( new Sensei_Enrolment_Learner_Calculation_Job() )->end();
	}

	/**
	 * Tests that the scheduler starts when the calculation version does not exist.
	 */
	public function testLearnerCalculationStartsWhenVersionIsNotSet() {
		$job       = new Sensei_Enrolment_Learner_Calculation_Job();
		$scheduler = Sensei_Enrolment_Job_Scheduler::instance();

		$scheduler->maybe_start_learner_calculation();
		$job->end();

		$this->assertEventScheduledCount( 1, Sensei_Enrolment_Learner_Calculation_Job::NAME, 'The job should have been scheduled once.' );
	}

	/**
	 * Tests that the scheduler doesn't starts when the calculation version is the current one.
	 */
	public function testLearnerCalculationDoesntStartWhenVersionIsSet() {
		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();
		$scheduler         = Sensei_Enrolment_Job_Scheduler::instance();

		update_option(
			Sensei_Enrolment_Job_Scheduler::CALCULATION_VERSION_OPTION_NAME,
			$enrolment_manager->get_enrolment_calculation_version()
		);

		$scheduler->maybe_start_learner_calculation();

		$this->assertEventScheduledCount( 0, Sensei_Enrolment_Learner_Calculation_Job::NAME, 'No job should have been scheduled.' );
	}

	/**
	 * Tests that once the scheduler has started, subsequent starts have no effect.
	 */
	public function testLearnerCalculationDoesntRescheduleWhenStartedManyTimes() {
		$job       = new Sensei_Enrolment_Learner_Calculation_Job();
		$scheduler = Sensei_Enrolment_Job_Scheduler::instance();

		$scheduler->maybe_start_learner_calculation();
		$scheduler->maybe_start_learner_calculation();
		$scheduler->maybe_start_learner_calculation();

		$job->end();

		$this->assertEventScheduledCount( 1, Sensei_Enrolment_Learner_Calculation_Job::NAME, 'The job should have been scheduled once.' );
	}

	/**
	 * Tests that once the scheduler run is completed, subsequent starts have no effect.
	 */
	public function testLearnerCalculationDoesntStartAfterCompletion() {
		$job               = new Sensei_Enrolment_Learner_Calculation_Job();
		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();
		$scheduler         = Sensei_Enrolment_Job_Scheduler::instance();

		$scheduler->maybe_start_learner_calculation();
		Sensei_Scheduler::instance()->cancel_all_jobs();
		$scheduler->maybe_start_learner_calculation();

		$this->assertEventScheduledCount( 2, Sensei_Enrolment_Learner_Calculation_Job::NAME, 'The job should have been scheduled again if it prematurely stopped' );
		Sensei_Scheduler::instance()->cancel_all_jobs();

		update_option(
			Sensei_Enrolment_Job_Scheduler::CALCULATION_VERSION_OPTION_NAME,
			$enrolment_manager->get_enrolment_calculation_version()
		);

		$scheduler->maybe_start_learner_calculation();
		$job->end();

		$this->assertEventScheduledCount( 2, Sensei_Enrolment_Learner_Calculation_Job::NAME, 'The job should not have started again after it was completed' );
	}


	/**
	 * Tests that when there are no users to calculate, the schedule run is completed.
	 */
	public function testLearnerCalculationCompletesWhenUsersAreCalculated() {
		$job               = new Sensei_Enrolment_Learner_Calculation_Job();
		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();
		$scheduler         = Sensei_Enrolment_Job_Scheduler::instance();

		update_user_meta(
			1,
			Sensei_Course_Enrolment_Manager::LEARNER_CALCULATION_META_NAME,
			$enrolment_manager->get_enrolment_calculation_version()
		);

		for ( $i = 0; $i < 3; $i ++ ) {
			$user = $this->factory->user->create();
			update_user_meta(
				$user,
				Sensei_Course_Enrolment_Manager::LEARNER_CALCULATION_META_NAME,
				$enrolment_manager->get_enrolment_calculation_version()
			);
		}

		$scheduler->run_learner_calculation();
		$job->end();

		$option = get_option( Sensei_Enrolment_Job_Scheduler::CALCULATION_VERSION_OPTION_NAME );
		$this->assertEquals( $enrolment_manager->get_enrolment_calculation_version(), $option );
	}

	/**
	 * Assert that an event was scheduled a certain number of times.
	 *
	 * @param int    $expected Number of times an event should have been scheduled.
	 * @param string $action   Action name.
	 * @param string $message  Message to show for failure.
	 */
	public function assertEventScheduledCount( $expected, $action, $message = '' ) {
		$this->assertEquals( $expected, Sensei_Scheduler_Shim::get_scheduled_action_count( $action ), $message );
	}
}

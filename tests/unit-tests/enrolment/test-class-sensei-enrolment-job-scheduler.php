<?php

/**
 * Tests for Sensei_Enrolment_Calculation_Scheduler class.
 *
 * @covers Sensei_Enrolment_Learner_Calculation_Scheduler
 * @group course-enrolment
 */
class Sensei_Enrolment_Calculation_Scheduler_Test extends WP_UnitTestCase {
	private $scheduled_events = [];

	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
		Sensei()->deactivation();

		tests_add_filter(
			'schedule_event',
			function ( $event ) {
				$event_name = $event->hook;

				if ( ! isset( $this->scheduled_events[ $event_name ] ) ) {
					$this->scheduled_events[ $event_name ] = 0;
				}
				$this->scheduled_events[ $event_name ]++;

				return $event;
			}
		);
	}

	public function tearDown() {
		parent::tearDown();

		$this->scheduled_events = [];
	}

	/**
	 * Tests that the scheduler starts when the calculation version does not exist.
	 */
	public function testLearnerCalculationStartsWhenVersionIsNotSet() {

		$scheduler = Sensei_Enrolment_Job_Scheduler::instance();

		$scheduler->maybe_start_learner_calculation();

		$this->assertEventScheduledCount( 1, Sensei_Enrolment_Learner_Calculation_Job::get_name(), 'The job should have been scheduled once.' );
	}

	/**
	 * Tests that the scheduler doesn't starts when the calculation version is the current one.
	 */
	public function testLearnerCalculationDoesntStartWhenVersionIsSet() {
		$scheduler = Sensei_Enrolment_Job_Scheduler::instance();

		update_option(
			Sensei_Enrolment_Job_Scheduler::CALCULATION_VERSION_OPTION_NAME,
			Sensei_Course_Enrolment_Manager::get_enrolment_calculation_version()
		);

		$scheduler->maybe_start_learner_calculation();

		$this->assertEventScheduledCount( 0, Sensei_Enrolment_Learner_Calculation_Job::get_name(), 'No job should have been scheduled.' );
	}

	/**
	 * Tests that once the scheduler has started, subsequent starts have no effect.
	 */
	public function testLearnerCalculationDoesntRescheduleWhenStartedManyTimes() {
		$scheduler = Sensei_Enrolment_Job_Scheduler::instance();

		$scheduler->maybe_start_learner_calculation();
		$scheduler->maybe_start_learner_calculation();
		$scheduler->maybe_start_learner_calculation();

		$this->assertEventScheduledCount( 1, Sensei_Enrolment_Learner_Calculation_Job::get_name(), 'The job should have been scheduled once.' );
	}

	/**
	 * Tests that once the scheduler run is completed, subsequent starts have no effect.
	 */
	public function testLearnerCalculationDoesntStartAfterCompletion() {
		$scheduler = Sensei_Enrolment_Job_Scheduler::instance();

		$scheduler->maybe_start_learner_calculation();
		$scheduler->stop_all_jobs();
		$scheduler->maybe_start_learner_calculation();

		$this->assertEventScheduledCount( 2, Sensei_Enrolment_Learner_Calculation_Job::get_name(), 'The job should have been scheduled again if it prematurely stopped' );
		$scheduler->stop_all_jobs();

		update_option(
			Sensei_Enrolment_Job_Scheduler::CALCULATION_VERSION_OPTION_NAME,
			Sensei_Course_Enrolment_Manager::get_enrolment_calculation_version()
		);

		$scheduler->maybe_start_learner_calculation();

		$this->assertEventScheduledCount( 2, Sensei_Enrolment_Learner_Calculation_Job::get_name(), 'The job should not have started again after it was completed' );
	}


	/**
	 * Tests that when there are no users to calculate, the schedule run is completed.
	 */
	public function testLearnerCalculationCompletesWhenUsersAreCalculated() {
		$scheduler = Sensei_Enrolment_Job_Scheduler::instance();

		update_user_meta(
			1,
			Sensei_Course_Enrolment_Manager::LEARNER_CALCULATION_META_NAME,
			Sensei_Course_Enrolment_Manager::get_enrolment_calculation_version()
		);

		for ( $i = 0; $i < 3; $i ++ ) {
			$user = $this->factory->user->create();
			update_user_meta(
				$user,
				Sensei_Course_Enrolment_Manager::LEARNER_CALCULATION_META_NAME,
				Sensei_Course_Enrolment_Manager::get_enrolment_calculation_version()
			);
		}

		$scheduler->run_learner_calculation();

		$option = get_option( Sensei_Enrolment_Job_Scheduler::CALCULATION_VERSION_OPTION_NAME );
		$this->assertEquals( Sensei_Course_Enrolment_Manager::get_enrolment_calculation_version(), $option );
	}

	/**
	 * Assert that an event was scheduled a certain number of times.
	 *
	 * @param int    $expected Number of times an event should have been scheduled.
	 * @param string $event    Event name.
	 * @param string $message  Message to show for failure.
	 */
	public function assertEventScheduledCount( $expected, $event, $message = '' ) {
		if ( ! isset( $this->scheduled_events[ $event ] ) ) {
			$this->scheduled_events[ $event ] = 0;
		}
		$this->assertEquals( $expected, $this->scheduled_events[ $event ], $message );
	}
}

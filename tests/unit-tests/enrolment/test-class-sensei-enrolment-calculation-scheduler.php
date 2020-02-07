<?php

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';

/**
 * Tests for Sensei_Enrolment_Calculation_Scheduler class.
 *
 * @covers Sensei_Enrolment_Calculation_Scheduler
 * @group course-enrolment
 */
class Sensei_Enrolment_Calculation_Scheduler_Test extends WP_UnitTestCase {
	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
		Sensei()->deactivation();
	}

	/**
	 * Tests that the scheduler starts when the calculation version does not exist.
	 */
	public function testSchedulerStartsWhenVersionIsNotSet() {
		$scheduler = Sensei_Enrolment_Calculation_Scheduler::instance();

		$schedule_called = false;

		tests_add_filter(
			'schedule_event',
			function ( $event ) use ( &$schedule_called ) {
				$schedule_called = true;
				return $event;
			}
		);

		$scheduler->start();

		$this->assertTrue( $schedule_called );
	}

	/**
	 * Tests that the scheduler doesn't starts when the calculation version is the current one.
	 */
	public function testSchedulerDoesntStartWhenVersionIsSet() {
		$scheduler = Sensei_Enrolment_Calculation_Scheduler::instance();

		update_option(
			Sensei_Enrolment_Calculation_Scheduler::CALCULATION_VERSION_OPTION_NAME,
			Sensei_Course_Enrolment_Manager::get_enrolment_calculation_version()
		);

		$schedule_called = false;

		tests_add_filter(
			'schedule_event',
			function ( $event ) use ( &$schedule_called ) {
				$schedule_called = true;
				return $event;
			}
		);

		$scheduler->start();

		$this->assertFalse( $schedule_called );
	}

	/**
	 * Tests that once the scheduler has started, subsequent starts have no effect.
	 */
	public function testSchedulerDoesntRescheduleWhenStartedManyTimes() {
		$scheduler = Sensei_Enrolment_Calculation_Scheduler::instance();

		$schedule_count = 0;

		tests_add_filter(
			'schedule_event',
			function ( $event ) use ( &$schedule_count ) {
				$schedule_count ++;
				return $event;
			}
		);

		$scheduler->start();
		$scheduler->start();
		$scheduler->start();

		$this->assertEquals( 1, $schedule_count );
	}

	/**
	 * Tests that once the scheduler run is completed, subsequent starts have no effect.
	 */
	public function testSchedulerDoesntStartAfterCompletion() {
		$scheduler = Sensei_Enrolment_Calculation_Scheduler::instance();

		$schedule_count = 0;

		tests_add_filter(
			'schedule_event',
			function ( $event ) use ( &$schedule_count ) {
				$schedule_count ++;
				return $event;
			}
		);

		$scheduler->start();
		$scheduler->stop();
		$scheduler->start();

		$this->assertEquals( 2, $schedule_count );

		$scheduler->complete();
		$scheduler->start();

		$this->assertEquals( 2, $schedule_count );
	}

	/**
	 * Tests that when there are no users to calculate, the schedule run is completed.
	 */
	public function testSchedulerCompletesWhenUsersAreCalculated() {
		$scheduler = Sensei_Enrolment_Calculation_Scheduler::instance();

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

		$scheduler->calculate_enrolments();

		$option = get_option( Sensei_Enrolment_Calculation_Scheduler::CALCULATION_VERSION_OPTION_NAME );
		$this->assertEquals( Sensei_Course_Enrolment_Manager::get_enrolment_calculation_version(), $option );
	}

	/**
	 * Tests that a call to Sensei_Enrolment_Calculation_Scheduler::calculate_enrolments, calculates the enrolments for
	 * a number of users equal to the batch size.
	 */
	public function testSchedulerCalculatesEnrolmentsForOneBatch() {
		$scheduler = Sensei_Enrolment_Calculation_Scheduler::instance();

		$property = new ReflectionProperty( 'Sensei_Enrolment_Calculation_Scheduler', 'batch_size' );
		$property->setAccessible( true );
		$property->setValue( $scheduler, 2 );

		$mock = $this->getMockBuilder( Sensei_Course_Enrolment_Manager::class )
			->disableOriginalConstructor()
			->setMethods( [ 'recalculate_enrolments' ] )
			->getMock();

		$property = new ReflectionProperty( 'Sensei_Course_Enrolment_Manager', 'instance' );
		$property->setAccessible( true );
		$property->setValue( $mock );

		$user1 = $this->factory->user->create( [ 'user_login' => 'user1' ] );
		update_user_meta(
			$user1,
			Sensei_Course_Enrolment_Manager::LEARNER_CALCULATION_META_NAME,
			'random-version-string'
		);
		$user2 = $this->factory->user->create( [ 'user_login' => 'user2' ] );

		$mock->expects( $this->exactly( 2 ) )
			->method( 'recalculate_enrolments' )
			->withConsecutive(
				[ $this->equalTo( 1 ) ],
				[ $this->equalTo( $user1 ) ]
			);

		$scheduler->calculate_enrolments();
	}

	/**
	 * Tests that calling Sensei_Enrolment_Calculation_Scheduler::calculate_enrolments multiple times, eventually
	 * calculates the enrolments for all users.
	 */
	public function testSchedulerCalculatesEnrolmentsForAllBatches() {
		$scheduler = Sensei_Enrolment_Calculation_Scheduler::instance();

		$property = new ReflectionProperty( 'Sensei_Enrolment_Calculation_Scheduler', 'batch_size' );
		$property->setAccessible( true );
		$property->setValue( $scheduler, 2 );

		$mock = $this->getMockBuilder( Sensei_Course_Enrolment_Manager::class )
			->disableOriginalConstructor()
			->setMethods( [ 'recalculate_enrolments' ] )
			->getMock();

		$property = new ReflectionProperty( 'Sensei_Course_Enrolment_Manager', 'instance' );
		$property->setAccessible( true );
		$property->setValue( $mock );

		$user1 = $this->factory->user->create( [ 'user_login' => 'user1' ] );
		$user2 = $this->factory->user->create( [ 'user_login' => 'user2' ] );

		$mock->expects( $this->exactly( 3 ) )
			->method( 'recalculate_enrolments' )
			->withConsecutive(
				[ $this->equalTo( 1 ) ],
				[ $this->equalTo( $user1 ) ],
				[ $this->equalTo( $user2 ) ]
			);

		$scheduler->calculate_enrolments();

		// Since we mock Sensei_Course_Enrolment_Manager::recalculate_enrolments we need to manually update users.
		update_user_meta(
			1,
			Sensei_Course_Enrolment_Manager::LEARNER_CALCULATION_META_NAME,
			Sensei_Course_Enrolment_Manager::get_enrolment_calculation_version()
		);
		update_user_meta(
			$user1,
			Sensei_Course_Enrolment_Manager::LEARNER_CALCULATION_META_NAME,
			Sensei_Course_Enrolment_Manager::get_enrolment_calculation_version()
		);

		$scheduler->calculate_enrolments();
	}
}

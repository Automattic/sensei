<?php

/**
 * Tests for Sensei_Enrolment_Calculation_Scheduler class.
 *
 * @covers Sensei_Enrolment_Learner_Calculation_Scheduler
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
	 * Tests that a call to Sensei_Enrolment_Calculation_Scheduler::calculate_enrolments, calculates the enrolments for
	 * a number of users equal to the batch size.
	 */
	public function testSchedulerCalculatesEnrolmentsForOneBatch() {
		$job = new Sensei_Enrolment_Learner_Calculation_Job( 2 );

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

		$job->run();
	}

	/**
	 * Tests that calling Sensei_Enrolment_Calculation_Scheduler::calculate_enrolments multiple times, eventually
	 * calculates the enrolments for all users.
	 */
	public function testSchedulerCalculatesEnrolmentsForAllBatches() {
		$job = new Sensei_Enrolment_Learner_Calculation_Job( 2 );

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

		$job->run();

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

		$job->run();
	}
}

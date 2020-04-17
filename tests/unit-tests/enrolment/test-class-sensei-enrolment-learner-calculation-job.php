<?php

/**
 * Tests for Sensei_Enrolment_Learner_Calculation_Job class.
 *
 * @covers Sensei_Enrolment_Learner_Calculation_Job
 * @group course-enrolment
 */
class Sensei_Enrolment_Learner_Calculation_Job_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Test_Helpers;

	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
		Sensei()->deactivation();
	}

	/**
	 * Clean up after test.
	 */
	public function tearDown() {
		parent::tearDown();

		remove_all_filters( 'sensei_enrolment_learner_calculation_job_batch_size' );
		( new Sensei_Enrolment_Learner_Calculation_Job() )->end();
	}

	/**
	 * Tests that a call to Sensei_Enrolment_Calculation_Scheduler::calculate_enrolments, calculates the enrolments for
	 * a number of users equal to the batch size.
	 */
	public function testSchedulerCalculatesEnrolmentsForOneBatch() {
		add_filter(
			'sensei_enrolment_learner_calculation_job_batch_size',
			function() {
				return 2;
			}
		);

		$job = new Sensei_Enrolment_Learner_Calculation_Job();
		$job->setup( 'version' );

		$mock = $this->getMockBuilder( Sensei_Course_Enrolment_Manager::class )
			->disableOriginalConstructor()
			->setMethods( [ 'recalculate_enrolments' ] )
			->getMock();

		$property = new ReflectionProperty( 'Sensei_Course_Enrolment_Manager', 'instance' );
		$property->setAccessible( true );
		$property->setValue( $mock );

		$this->prepareEnrolmentManager();

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
		add_filter(
			'sensei_enrolment_learner_calculation_job_batch_size',
			function() {
				return 2;
			}
		);

		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();
		$job               = new Sensei_Enrolment_Learner_Calculation_Job();
		$job->setup( 'version' );

		$mock = $this->getMockBuilder( Sensei_Course_Enrolment_Manager::class )
			->disableOriginalConstructor()
			->setMethods( [ 'recalculate_enrolments' ] )
			->getMock();

		$property = new ReflectionProperty( 'Sensei_Course_Enrolment_Manager', 'instance' );
		$property->setAccessible( true );
		$property->setValue( $mock );

		$this->prepareEnrolmentManager();

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
			$enrolment_manager->get_enrolment_calculation_version()
		);
		update_user_meta(
			$user1,
			Sensei_Course_Enrolment_Manager::LEARNER_CALCULATION_META_NAME,
			$enrolment_manager->get_enrolment_calculation_version()
		);

		$job->run();
	}
}

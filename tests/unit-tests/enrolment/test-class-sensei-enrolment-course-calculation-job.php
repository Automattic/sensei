<?php

/**
 * Tests for Sensei_Enrolment_Learner_Calculation_Job class.
 *
 * @covers Sensei_Enrolment_Learner_Calculation_Job
 * @group course-enrolment
 */
class Sensei_Enrolment_Course_Calculation_Job_Test extends WP_UnitTestCase {
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

		remove_all_filters( 'sensei_enrolment_course_calculation_job_batch_size' );
	}

	/**
	 * Tests arguments are carried over from constructor.
	 */
	public function testGetArgs() {
		$test_args = [
			'job_id'           => null,
			'course_id'        => 10,
			'invalidated_only' => true,
		];

		$job = new Sensei_Enrolment_Course_Calculation_Job( $test_args );

		$this->assertEquals( $test_args, $job->get_args(), 'Arguments should match' );
	}

	/**
	 * Tests to make sure the batches continue to run until completed.
	 */
	public function testContinueToRunBatch() {
		add_filter(
			'sensei_enrolment_course_calculation_job_batch_size',
			function() {
				return 2;
			}
		);

		$course_id        = $this->factory->course->create();
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$job              = new Sensei_Enrolment_Course_Calculation_Job(
			[
				'course_id'        => $course_id,
				'invalidated_only' => false,
			]
		);
		$job->setup();

		$this->createAndEnrolUsers( $course_id, 5 );
		$this->invalidateAllCourseResults( $course_enrolment );

		$job->run();
		$this->assertFalse( $job->is_complete(), 'Job should not be marked complete after the first run.' );

		$job->run();
		$this->assertFalse( $job->is_complete(), 'Job should not be marked as complete after the second run.' );

		$job->run();
		$this->assertTrue( $job->is_complete(), 'Job should be marked as complete after third run.' );
	}

	/**
	 * Tests checking course enrolment happens when job runs.
	 */
	public function testCheckingCourseEnrolment() {
		add_filter(
			'sensei_enrolment_course_calculation_job_batch_size',
			function() {
				return 5;
			}
		);

		$course_id        = $this->factory->course->create();
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$job              = new Sensei_Enrolment_Course_Calculation_Job(
			[
				'course_id'        => $course_id,
				'invalidated_only' => false,
			]
		);
		$job->setup();

		$enrolled_user_ids = $this->createAndEnrolUsers( $course_id, 3 );
		$other_user_ids    = $this->factory()->user->create_many( 1 );

		$this->invalidateAllCourseResults( $course_enrolment );

		$job->run();
		$this->assertTrue( $job->is_complete(), 'Job should be complete after first run.' );

		foreach ( $enrolled_user_ids as $user_id ) {
			$results = $course_enrolment->get_enrolment_check_results( $user_id );
			$this->assertNotFalse( $results, 'Results should have been calculated and stored' );
		}

		foreach ( $other_user_ids as $user_id ) {
			$results = $course_enrolment->get_enrolment_check_results( $user_id );
			$this->assertFalse( $results, 'Results should have not been stored' );
		}
	}

	/**
	 * Tests to make sure only enrolled progress is invalidated and recalculated.
	 */
	public function testCheckingCourseEnrolmentInvalidatedOnly() {
		add_filter(
			'sensei_enrolment_course_calculation_job_batch_size',
			function() {
				return 5;
			}
		);

		$course_id        = $this->factory->course->create();
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$job              = new Sensei_Enrolment_Course_Calculation_Job(
			[
				'course_id'        => $course_id,
				'invalidated_only' => true,
			]
		);
		$job->setup();

		$enrolled_user_ids = $this->createAndEnrolUsers( $course_id, 2 );
		$other_user_ids    = $this->factory()->user->create_many( 2 );
		$user_ids          = array_merge( $enrolled_user_ids, $other_user_ids );

		foreach ( $user_ids as $user_id ) {
			$course_enrolment->is_enrolled( $user_id );
		}

		$new_user_ids = $this->factory()->user->create_many( 2 );

		$this->invalidateEnrolledCourseResults( $course_enrolment );

		foreach ( $enrolled_user_ids as $user_id ) {
			$results = $course_enrolment->get_enrolment_check_results( $user_id );
			$this->assertFalse( $results, 'Results should have been cleared for enrolled users' );
		}

		foreach ( $new_user_ids as $user_id ) {
			$results = $course_enrolment->get_enrolment_check_results( $user_id );
			$this->assertFalse( $results, 'Results should have never been set for new users' );
		}

		$other_results = [];
		foreach ( $other_user_ids as $user_id ) {
			$results                   = $course_enrolment->get_enrolment_check_results( $user_id );
			$other_results[ $user_id ] = wp_json_encode( $results );
			$this->assertNotFalse( $results, 'Results should NOT have been cleared for users who are not enrolled' );
		}

		$job->run();
		$this->assertTrue( $job->is_complete(), 'Job should be complete after first run.' );

		foreach ( $enrolled_user_ids as $user_id ) {
			$results = $course_enrolment->get_enrolment_check_results( $user_id );
			$this->assertNotFalse( $results, 'Results should have been calculated for enrolled users' );
		}

		foreach ( $other_user_ids as $user_id ) {
			$results = $course_enrolment->get_enrolment_check_results( $user_id );
			$this->assertEquals( $other_results[ $user_id ], wp_json_encode( $results ), 'Results should NOT have been calculated or changed for users who are not enrolled' );
		}

		foreach ( $new_user_ids as $user_id ) {
			$results = $course_enrolment->get_enrolment_check_results( $user_id );
			$this->assertFalse( $results, 'Results should still have never been set for new users' );
		}
	}

	/**
	 * Create and enrol users in a course.
	 *
	 * @param int $course_id Course ID.
	 * @param int $n         Number of students to create.
	 *
	 * @return int[]
	 */
	private function createAndEnrolUsers( $course_id, $n ) {
		$user_ids = $this->factory()->user->create_many( $n );

		$manual_provider = Sensei_Course_Enrolment_Manager::instance()->get_manual_enrolment_provider();
		foreach ( $user_ids as $user_id ) {
			$manual_provider->enrol_student( $user_id, $course_id );
		}

		return $user_ids;
	}

	/**
	 * Invalidate all course enrolment results.
	 *
	 * @param Sensei_Course_Enrolment $course_enrolment
	 */
	private function invalidateAllCourseResults( $course_enrolment ) {
		$method = new ReflectionMethod( Sensei_Course_Enrolment::class, 'invalidate_all_learner_results' );
		$method->setAccessible( true );
		$method->invoke( $course_enrolment );
		wp_cache_flush();
	}

	/**
	 * Invalidate enrolled user course enrolment results.
	 *
	 * @param Sensei_Course_Enrolment $course_enrolment
	 */
	private function invalidateEnrolledCourseResults( $course_enrolment ) {
		$method = new ReflectionMethod( Sensei_Course_Enrolment::class, 'invalidate_enrolled_learner_results' );
		$method->setAccessible( true );
		$method->invoke( $course_enrolment );
	}
}

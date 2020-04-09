<?php

/**
 * Tests for Sensei_Course_Enrolment_Manager class.
 *
 * @group course-enrolment
 */
class Sensei_Course_Enrolment_Manager_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Test_Helpers;
	use Sensei_Scheduler_Test_Helpers;

	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();

		self::resetEnrolmentProviders();
		self::restoreShimScheduler();
		Sensei_Scheduler_Shim::reset();
	}

	/**
	 * Tear down.
	 */
	public function tearDown() {
		parent::tearDown();

		$this->clearEnrolmentCheckDeferred();
		Sensei_Scheduler_Shim::reset();
	}

	/**
	 * Clean up after all tests.
	 */
	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		self::resetEnrolmentProviders();
	}

	/**
	 * Tests getting an enrolment provider by ID.
	 */
	public function testGetEnrolmentProviderById() {
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Always_Provides::class );
		$this->prepareEnrolmentManager();

		$enrolment_manager        = Sensei_Course_Enrolment_Manager::instance();
		$provider_always_provides = $enrolment_manager->get_enrolment_provider_by_id( Sensei_Test_Enrolment_Provider_Always_Provides::ID );
		$provider_never_provides  = $enrolment_manager->get_enrolment_provider_by_id( Sensei_Test_Enrolment_Provider_Never_Provides::ID );

		$this->assertFalse( $provider_never_provides, 'This provider was never registered and should not be returned.' );
		$this->assertNotFalse( $provider_always_provides, 'This provider was registered and its singleton instance should be returned' );

		$this->assertTrue( $provider_always_provides instanceof Sensei_Test_Enrolment_Provider_Always_Provides, 'Singleton instance of the provider should be returned.' );
	}

	/**
	 * Tests re-checking enrolment when something might have changed.
	 */
	public function testTriggerCourseEnrolmentCheckSimple() {
		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Denies_Crooks::class );
		$this->prepareEnrolmentManager();

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$pre_crook_enrolment = $course_enrolment->is_enrolled( $student_id );
		$this->assertTrue( $pre_crook_enrolment, 'As a non-crook, this student should be enrolled' );

		$this->turnStudentIntoCrook( $student_id );
		$pre_notify_enrolment = $course_enrolment->is_enrolled( $student_id );
		$this->assertTrue( $pre_notify_enrolment, 'Even as a crook, the cached value should be used until we notify the course enrolment handler' );

		Sensei_Course_Enrolment_Manager::trigger_course_enrolment_check( $student_id, $course_id );
		$post_notify_enrolment = $course_enrolment->is_enrolled( $student_id );
		$this->assertFalse( $post_notify_enrolment, 'Now that the crook status is known, they should no longer be enrolled in the course' );
	}

	/**
	 * Test o make sure the recalculation happens only once when triggering course enrolment checks.
	 */
	public function testDeferredEnrolmentCheckCalled() {
		$course_id               = $this->getSimpleCourse();
		$student_id              = $this->createStandardStudent();
		$course_results_meta_key = Sensei_Course_Enrolment::META_PREFIX_ENROLMENT_RESULTS . $course_id;

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Always_Provides::class );
		$this->prepareEnrolmentManager();

		remove_filter( 'sensei_should_defer_enrolment_check', '__return_false' );
		Sensei_Course_Enrolment_Manager::trigger_course_enrolment_check( $student_id, $course_id );
		add_filter( 'sensei_should_defer_enrolment_check', '__return_false' );

		$this->assertTrue( '' === get_user_meta( $student_id, $course_results_meta_key, true ), 'The results meta should not be set yet after lazily triggering a course enrolment check' );
		$this->assertEnrolmentCheckDeferred( $student_id, $course_id, 'There should be a deferred enrolment check for the student/course' );

		Sensei_Course_Enrolment_Manager::instance()->run_deferred_course_enrolment_checks();

		$this->assertTrue( ! empty( get_user_meta( $student_id, $course_results_meta_key, true ) ), 'The results meta should be set after running the deferred course enrolment checks' );
		$this->assertEnrolmentCheckNotDeferred( $student_id, $course_id, 'There should no longer be a deferred enrolment check for the student/course' );
	}

	/**
	 * Test o make sure the recalculation happens immediately once we're inside/after shutdown action.
	 */
	public function testEnrolmentCheckCalledImmediatelyDuringShutdown() {
		$course_id               = $this->getSimpleCourse();
		$student_id              = $this->createStandardStudent();
		$course_results_meta_key = Sensei_Course_Enrolment::META_PREFIX_ENROLMENT_RESULTS . $course_id;

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Always_Provides::class );
		$this->prepareEnrolmentManager();

		remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );
		do_action( 'shutdown' );

		Sensei_Course_Enrolment_Manager::trigger_course_enrolment_check( $student_id, $course_id );
		$this->assertTrue( false !== get_user_meta( $student_id, $course_results_meta_key, true ), 'The results meta should be set yet after immediately running enrolment check' );
		$this->assertEnrolmentCheckNotDeferred( $student_id, $course_id, 'There should not be a deferred enrolment check for the student/course' );
	}

	/**
	 * Tests to make sure deferred calculation of results are removed when calculated.
	 */
	public function testDoubleTriggerProperlyDeletesResults() {
		$course_id        = $this->getSimpleCourse();
		$student_id       = $this->createStandardStudent();
		$course_enrolment = \Sensei_Course_Enrolment::get_course_instance( $course_id );

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Denies_Crooks::class );
		$this->prepareEnrolmentManager();

		$this->assertTrue( $course_enrolment->is_enrolled( $student_id ), 'Initially the user should be enrolled' );
		$this->turnStudentIntoCrook( $student_id );
		$this->assertTrue( $course_enrolment->is_enrolled( $student_id ), 'The student should still be enrolled after changing the status without actions' );

		Sensei_Course_Enrolment_Manager::trigger_course_enrolment_check( $student_id, $course_id );

		$this->assertFalse( $course_enrolment->is_enrolled( $student_id ), 'The user should now no longer be enrolled' );

		$this->turnStudentIntoNormal( $student_id );
		Sensei_Course_Enrolment_Manager::trigger_course_enrolment_check( $student_id, $course_id );
		$this->assertTrue( $course_enrolment->is_enrolled( $student_id ), 'The user should now be enrolled' );

	}

	/**
	 * Tests to make sure manual enrolment is blocked on the frontend if someone filtered out `manual` provider.
	 */
	public function testMaybePreventFrontendManualEnrolNoManual() {
		$course_id         = $this->getSimpleCourse();
		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();

		$this->removeEnrolmentProvider( Sensei_Course_Manual_Enrolment_Provider::class );
		$this->prepareEnrolmentManager();

		$this->assertFalse( $enrolment_manager->maybe_prevent_frontend_manual_enrol( null, $course_id ) );
	}

	/**
	 * Tests to make sure manual enrolment is blocked on the frontend if there is provider that handles the course.
	 */
	public function testMaybePreventFrontendManualEnrolHasHandlingProvider() {
		$course_id         = $this->getSimpleCourse();
		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Always_Provides::class );
		$this->prepareEnrolmentManager();

		$this->assertFalse( $enrolment_manager->maybe_prevent_frontend_manual_enrol( null, $course_id ) );
	}

	/**
	 * Tests to make sure manual enrolment is blocked on the frontend if there is provider that handles the course.
	 */
	public function testMaybeAllowFrontendManualEnrolNoHandlingProvider() {
		$course_id         = $this->getSimpleCourse();
		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Never_Handles::class );
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Handles_Dog_Courses::class );
		$this->prepareEnrolmentManager();

		$this->assertEquals( null, $enrolment_manager->maybe_prevent_frontend_manual_enrol( null, $course_id ) );
	}

	/**
	 * Simple tests for getting a site's enrolment salt.
	 *
	 * @covers Sensei_Course_Enrolment_Manager::get_site_salt
	 */
	public function testGetSiteEnrolmentSalt() {
		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();
		$enrolment_salt    = $enrolment_manager->get_site_salt();

		$this->assertTrue( ! empty( $enrolment_salt ), 'Enrolment salt should not be empty' );
		$this->assertEquals( $enrolment_manager->get_site_salt(), $enrolment_salt, 'Getting enrolment salt twice without resetting should product the same result' );
	}

	/**
	 * Tests to make sure resetting the site enrolment salt produces a new salt.
	 *
	 * @covers Sensei_Course_Enrolment_Manager::get_site_salt
	 */
	public function testResetSiteEnrolmentSalt() {
		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();
		$enrolment_salt    = $enrolment_manager->get_site_salt();

		$this->assertTrue( ! empty( $enrolment_salt ), 'Enrolment salt should not be empty' );
		$new_enrolment_salt = $enrolment_manager->reset_site_salt();
		$this->assertNotEquals( $enrolment_manager->get_site_salt(), $enrolment_salt, 'Getting enrolment salt after resetting it should produce a different result.' );
		$this->assertEquals( $enrolment_manager->get_site_salt(), $new_enrolment_salt, 'Getting enrolment salt after resetting return the same salt as the reset method returns.' );
	}

	/**
 * Tests that Sensei_Course_Enrolment_Manager::recalculate_enrolments calls Sensei_Course_Enrolment::is_enroled for
 * all courses.
 */
	public function testEnrolmentsForAllCoursesAreCalculated() {
		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();
		$simple_course     = $this->getSimpleCourse();
		$dog_course        = $this->getDogCourse();
		$student_id        = $this->createStandardStudent();

		$this->prepareEnrolmentManager();

		$simple_mock = $this->create_course_enrolment_mock( $simple_course );
		$dog_mock    = $this->create_course_enrolment_mock( $dog_course );

		$simple_mock->expects( $this->once() )->method( 'is_enrolled' )->with( $this->equalTo( $student_id ) );
		$dog_mock->expects( $this->once() )->method( 'is_enrolled' )->with( $this->equalTo( $student_id ) );

		$user_meta = get_user_meta( $student_id, Sensei_Course_Enrolment_Manager::LEARNER_CALCULATION_META_NAME, true );
		$this->assertEmpty( $user_meta );

		Sensei_Course_Enrolment_Manager::instance()->recalculate_enrolments( $student_id );

		$user_meta = get_user_meta( $student_id, Sensei_Course_Enrolment_Manager::LEARNER_CALCULATION_META_NAME, true );
		$this->assertEquals( $enrolment_manager->get_enrolment_calculation_version(), $user_meta );
	}

	/**
	 * Tests that Sensei_Course_Enrolment_Manager::recalculate_enrolments does not do any calculation when the enrloments
	 * have been already calculated.
	 */
	public function testNoCalculationIsPerformedWhenAlreadyCalculated() {
		$this->prepareEnrolmentManager();

		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();
		$simple_course     = $this->getSimpleCourse();
		$student_id        = $this->createStandardStudent();
		update_user_meta(
			$student_id,
			Sensei_Course_Enrolment_Manager::LEARNER_CALCULATION_META_NAME,
			$enrolment_manager->get_enrolment_calculation_version()
		);

		$simple_mock = $this->create_course_enrolment_mock( $simple_course );

		$simple_mock->expects( $this->never() )->method( 'is_enrolled' );

		Sensei_Course_Enrolment_Manager::instance()->recalculate_enrolments( $student_id );
	}

	/**
	 * Tests to make sure enrolment is recalculated when moving from draft to publish.
	 */
	public function testRecalculateOnCoursePostScheduleChangeTrueDraftToPublish() {
		$course   = $this->factory->course->create_and_get( [ 'post_status' => 'draft' ] );
		$job_args = [
			'course_id'        => $course->ID,
			'invalidated_only' => false,
		];
		$job      = new Sensei_Enrolment_Course_Calculation_Job( $job_args );

		$course->post_status = 'publish';
		wp_update_post( $course );

		$job->resume();

		$this->assertNotFalse( Sensei_Scheduler_Shim::get_next_scheduled( $job ), 'Job should have been scheduled' );
	}

	/**
	 * Tests to make sure enrolment is recalculated when moving from publish to draft.
	 */
	public function testRecalculateOnCoursePostScheduleChangeTruePublishToDraft() {
		$course   = $this->factory->course->create_and_get( [ 'post_status' => 'publish' ] );
		$job_args = [
			'course_id'        => $course->ID,
			'invalidated_only' => false,
		];
		$job      = new Sensei_Enrolment_Course_Calculation_Job( $job_args );

		$course->post_status = 'draft';
		wp_update_post( $course );

		$job->resume();

		$this->assertNotFalse( Sensei_Scheduler_Shim::get_next_scheduled( $job ), 'Job should have been scheduled' );
	}

	/**
	 * Tests to make sure enrolment is not recalculated when moving from publish to publish.
	 */
	public function testRecalculateOnCoursePostScheduleChangeFalsePublishToPublish() {
		$course   = $this->factory->course->create_and_get( [ 'post_status' => 'publish' ] );
		$job_args = [
			'course_id'        => $course->ID,
			'invalidated_only' => false,
		];
		$job      = new Sensei_Enrolment_Course_Calculation_Job( $job_args );

		$course->post_status = 'publish';
		$course->post_title  = $course->post_title . ' Updated';
		wp_update_post( $course );

		$this->assertFalse( Sensei_Scheduler_Shim::get_next_scheduled( $job ), 'Job should not have been scheduled' );
	}

	/**
	 * Tests to make sure enrolment is not recalculated when moving from scheduled to draft.
	 */
	public function testRecalculateOnCoursePostScheduleChangeFalseScheduledToDraft() {
		$course   = $this->factory->course->create_and_get( [ 'post_status' => 'scheduled' ] );
		$job_args = [
			'course_id'        => $course->ID,
			'invalidated_only' => false,
		];
		$job      = new Sensei_Enrolment_Course_Calculation_Job( $job_args );

		$course->post_status = 'draft';
		wp_update_post( $course );

		$this->assertFalse( Sensei_Scheduler_Shim::get_next_scheduled( $job ), 'Job should not have been scheduled' );
	}

	/**
	 * Helper method to create a Sensei_Course_Enrolment mock object.
	 */
	private function create_course_enrolment_mock( $course ) {
		$mock = $this->getMockBuilder( Sensei_Course_Enrolment::class )
			->disableOriginalConstructor()
			->setMethods( [ 'is_enrolled' ] )
			->getMock();

		$property = new ReflectionProperty( 'Sensei_Course_Enrolment', 'instances' );
		$property->setAccessible( true );
		$instances            = $property->getValue();
		$instances[ $course ] = $mock;
		$property->setValue( $instances );

		return $mock;
	}

	/**
	 * Assert that an enrolment check was deferred.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 */
	private function assertEnrolmentCheckDeferred( $user_id, $course_id, $message = null ) {
		$property = new ReflectionProperty( Sensei_Course_Enrolment_Manager::class, 'deferred_enrolment_checks' );
		$property->setAccessible( true );
		$deferred = $property->getValue( Sensei_Course_Enrolment_Manager::instance() );

		$this->assertTrue( isset( $deferred[ $user_id ][ $course_id ] ), $message );
	}

	/**
	 * Assert that an enrolment check was NOT deferred.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 */
	private function assertEnrolmentCheckNotDeferred( $user_id, $course_id, $message = null ) {
		$property = new ReflectionProperty( Sensei_Course_Enrolment_Manager::class, 'deferred_enrolment_checks' );
		$property->setAccessible( true );
		$deferred = $property->getValue( Sensei_Course_Enrolment_Manager::instance() );

		$this->assertFalse( isset( $deferred[ $user_id ][ $course_id ] ), $message );
	}

	/**
	 * Reset enrolment check deferment status.
	 */
	private function clearEnrolmentCheckDeferred() {
		$property = new ReflectionProperty( Sensei_Course_Enrolment_Manager::class, 'deferred_enrolment_checks' );
		$property->setAccessible( true );
		$property->setValue( Sensei_Course_Enrolment_Manager::instance(), [] );
	}
}

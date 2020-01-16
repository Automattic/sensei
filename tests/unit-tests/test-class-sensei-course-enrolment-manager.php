<?php

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-course-enrolment-test-helpers.php';

class Sensei_Class_Course_Enrolment_Test extends WP_UnitTestCase {

	use Course_Enrolment_Test_Helpers;

	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Clean up after test.
	 */
	public function tearDown() {
		parent::tearDown();

		$this->resetEnrolmentProviders();
	}

	/**
	 * Tests getting an enrolment provider by ID.
	 */
	public function testGetEnrolmentProviderById() {
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Always_Provides::class );

		$provider_always_provides = Sensei_Course_Enrolment_Manager::get_enrolment_provider_by_id( Sensei_Test_Enrolment_Provider_Always_Provides::get_id() );
		$provider_never_provides  = Sensei_Course_Enrolment_Manager::get_enrolment_provider_by_id( Sensei_Test_Enrolment_Provider_Never_Provides::get_id() );

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

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$pre_crook_enrolment = $course_enrolment->is_enrolled( $student_id );
		$this->assertTrue( $pre_crook_enrolment, 'As a non-crook, this student should be enrolled' );

		$this->turnStudentIntoCrook( $student_id );
		$pre_notify_enrolment = $course_enrolment->is_enrolled( $student_id );
		$this->assertTrue( $pre_notify_enrolment, 'Even as a crook, the cached value should be used until we notify the course enrolment handler' );

		Sensei_Course_Enrolment_Manager::trigger_course_enrolment_check( $course_id, $student_id );
		$post_notify_enrolment = $course_enrolment->is_enrolled( $student_id );
		$this->assertFalse( $post_notify_enrolment, 'Now that the crook status is known, they should no longer be enrolled in the course' );
	}
}

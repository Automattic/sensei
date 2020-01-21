<?php

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';

/**
 * Tests for Sensei_Course_Enrolment_Manager class.
 *
 * @group course-enrolment
 */
class Sensei_Course_Enrolment_Manager_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Test_Helpers;

	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();

		self::resetEnrolmentProviders();
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
		$provider_always_provides = $enrolment_manager->get_enrolment_provider_by_id( Sensei_Test_Enrolment_Provider_Always_Provides::get_id() );
		$provider_never_provides  = $enrolment_manager->get_enrolment_provider_by_id( Sensei_Test_Enrolment_Provider_Never_Provides::get_id() );

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
	 * Simple tests for getting a site's enrolment salt.
	 *
	 * @covers Sensei_Course_Enrolment_Manager::get_site_salt
	 */
	public function testGetSiteEnrolmentSalt() {
		$enrolment_salt = Sensei_Course_Enrolment_Manager::get_site_salt();
		$this->assertTrue( ! empty( $enrolment_salt ), 'Enrolment salt should not be empty' );
		$this->assertEquals( Sensei_Course_Enrolment_Manager::get_site_salt(), $enrolment_salt, 'Getting enrolment salt twice without resetting should product the same result' );
	}

	/**
	 * Tests to make sure resetting the site enrolment salt produces a new salt.
	 *
	 * @covers Sensei_Course_Enrolment_Manager::get_site_salt
	 */
	public function testResetSiteEnrolmentSalt() {
		$enrolment_salt = Sensei_Course_Enrolment_Manager::get_site_salt();
		$this->assertTrue( ! empty( $enrolment_salt ), 'Enrolment salt should not be empty' );
		$new_enrolment_salt = Sensei_Course_Enrolment_Manager::reset_site_salt();
		$this->assertNotEquals( Sensei_Course_Enrolment_Manager::get_site_salt(), $enrolment_salt, 'Getting enrolment salt after resetting it should produce a different result.' );
		$this->assertEquals( Sensei_Course_Enrolment_Manager::get_site_salt(), $new_enrolment_salt, 'Getting enrolment salt after resetting return the same salt as the reset method returns.' );
	}
}

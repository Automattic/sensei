<?php
/**
 * Tests for Sensei_Course_Manual_Enrolment_Provider class.
 *
 * @group course-enrolment
 */
class Sensei_Course_Manual_Enrolment_Provider_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Manual_Test_Helpers;

	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();

		self::resetSiteWideLegacyEnrolmentFlag();
		self::resetCourseEnrolmentProviders();
		self::resetLegacyFilters();
		self::resetCourseEnrolmentManager();
	}

	/**
	 * Clean up after all tests.
	 */
	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		self::resetSiteWideLegacyEnrolmentFlag();
		self::resetCourseEnrolmentProviders();
		self::resetLegacyFilters();
		self::resetCourseEnrolmentManager();
	}

	/**
	 * Ensures the manual provider is registered.
	 */
	public function testProviderIsRegistered() {
		$providers = Sensei_Course_Enrolment_Manager::instance()->get_all_enrolment_providers();

		$this->assertTrue( isset( $providers[ Sensei_Course_Manual_Enrolment_Provider::instance()->get_id() ] ), '`manual` provider key should be set' );
		$this->assertTrue( $providers[ Sensei_Course_Manual_Enrolment_Provider::instance()->get_id() ] instanceof Sensei_Course_Manual_Enrolment_Provider, '`manual` provider should be of class Sensei_Course_Manual_Enrolment_Provider' );
	}

	/**
	 * This provider should handle every course.
	 */
	public function testHandlesEnrolmentAlways() {
		$course_id = $this->getSimpleCourseId();
		$provider  = $this->getManualEnrolmentProvider();

		$this->assertTrue( $provider->handles_enrolment( $course_id ), 'Manual provider should handle enrolment for all courses' );
	}

	/**
	 * Tests to make sure legacy isn't migrated because they might have installed on Sensei 3.0.0 and then are now upgrading to 3.1.0.
	 */
	public function testIsEnrolledLegacyNoMigrateVersion() {
		$provider   = $this->getManualEnrolmentProvider();
		$course_id  = $this->getSimpleCourseId();
		$student_id = $this->getStandardStudentUserId();

		// Start course progress for the student and simulate a legacy enrolment.
		$this->legacyEnrolStudentStartCourseProgress( $student_id, $course_id );

		$this->assertFalse( get_option( 'sensei_enrolment_legacy' ), 'The site-wide legacy enrolment flag should not have been set.' );

		$is_enrolled = $provider->is_enrolled( $student_id, $course_id );
		$this->assertFalse( $this->wasLegacyEnrolmentChecked( $student_id, $course_id ), 'Legacy enrolment status for user should not have been checked.' );
		$this->assertFalse( $is_enrolled, 'The user should have not been enrolled because they were not upgrading from a pre-3.0 version.' );
	}

	/**
	 * Tests to make sure enrolment isn't given when the `sensei_is_legacy_enrolled` filters to a false value.
	 */
	public function testIsEnrolledLegacyNoMigrateFilter() {
		$provider   = $this->getManualEnrolmentProvider();
		$course_id  = $this->getSimpleCourseId();
		$student_id = $this->getStandardStudentUserId();

		// Start course progress for the student and simulate a legacy enrolment.
		$this->legacyEnrolStudentStartCourseProgress( $student_id, $course_id );
		$this->simulateUpgradingFromSensei2ToSensei3();
		add_filter( 'sensei_is_legacy_enrolled', '__return_false' );

		$this->assertNotEmpty( get_option( 'sensei_enrolment_legacy' ), 'The site-wide legacy enrolment flag should have been set.' );

		$is_enrolled = $provider->is_enrolled( $student_id, $course_id );
		$this->assertTrue( $this->wasLegacyEnrolmentChecked( $student_id, $course_id ), 'Legacy enrolment status for user should have been checked.' );
		$this->assertFalse( $is_enrolled, 'The user should not have been enrolled due to legacy enrolment being blocked from the filter.' );
	}

	/**
	 * Tests to make sure enrolment isn't provided on migration when the user doesn't have course progress.
	 */
	public function testIsEnrolledLegacyNoMigrateNoProgress() {
		$provider   = $this->getManualEnrolmentProvider();
		$course_id  = $this->getSimpleCourseId();
		$student_id = $this->getStandardStudentUserId();

		$this->simulateUpgradingFromSensei2ToSensei3();

		$this->assertNotEmpty( get_option( 'sensei_enrolment_legacy' ), 'The site-wide legacy enrolment flag should have been set.' );

		$is_enrolled = $provider->is_enrolled( $student_id, $course_id );
		$this->assertFalse( $this->wasLegacyEnrolmentChecked( $student_id, $course_id ), 'Legacy enrolment status for user should not have been checked.' );
		$this->assertFalse( $is_enrolled, 'The user should not have been enrolled because they were not enrolled previously.' );
	}

	/**
	 * Tests to make sure enrolment is migrated when user is coming from a pre-3.0 version of Sensei and has course progress.
	 */
	public function testIsEnrolledLegacyMigrateWithCourseProgress() {
		$provider   = $this->getManualEnrolmentProvider();
		$course_id  = $this->getSimpleCourseId();
		$student_id = $this->getStandardStudentUserId();

		// Start course progress for the student and simulate a legacy enrolment.
		$this->legacyEnrolStudentStartCourseProgress( $student_id, $course_id );
		$this->simulateUpgradingFromSensei2ToSensei3();

		$this->assertNotEmpty( get_option( 'sensei_enrolment_legacy' ), 'The site-wide legacy enrolment flag should have been set.' );

		$is_enrolled = $provider->is_enrolled( $student_id, $course_id );
		$this->assertTrue( $this->wasLegacyEnrolmentChecked( $student_id, $course_id ), 'Legacy enrolment status for user should have been checked.' );
		$this->assertTrue( $is_enrolled, 'The user should have been enrolled due to legacy enrolment from course progress.' );
	}

	/**
	 * Tests to make sure student is enrolled only when the user meta is set directly in provider state.
	 */
	public function testIsEnrolledWithDirectEnrolment() {
		$provider   = $this->getManualEnrolmentProvider();
		$course_id  = $this->getSimpleCourseId();
		$student_id = $this->getStandardStudentUserId();

		$this->directlyEnrolStudent( $student_id, $course_id );

		$is_enrolled = $provider->is_enrolled( $student_id, $course_id );
		$this->assertFalse( $this->wasLegacyEnrolmentChecked( $student_id, $course_id ), 'Legacy enrolment status for user should not have been checked.' );
		$this->assertTrue( $is_enrolled, 'The user should have been enrolled when directly enrolled in provider state.' );

		$this->directlyWithdrawStudent( $student_id, $course_id );
		$is_enrolled_post_withdrawal = $provider->is_enrolled( $student_id, $course_id );
		$this->assertFalse( $is_enrolled_post_withdrawal, 'The user should have been withdrawn when the user was directly withdrawn from provider state storage.' );
	}

	/**
	 * Tests the simple functionality of enrolling a student manually.
	 */
	public function testEnrolStudent() {
		$provider     = $this->getManualEnrolmentProvider();
		$course_id    = $this->getSimpleCourseId();
		$student_id   = $this->getStandardStudentUserId();
		$student_term = Sensei_Learner::get_learner_term( $student_id );

		$this->assertFalse( $provider->is_enrolled( $student_id, $course_id ), 'Student should not be enrolled before we enrol them.' );

		$provider->enrol_learner( $student_id, $course_id );

		$this->assertTrue( $provider->is_enrolled( $student_id, $course_id ), 'Student should now be enrolled.' );
	}

	/**
	 * Tests the simple functionality of withdrawing a student's manual enrolment.
	 */
	public function testWithdrawStudent() {
		$provider     = $this->getManualEnrolmentProvider();
		$course_id    = $this->getSimpleCourseId();
		$student_id   = $this->getStandardStudentUserId();
		$student_term = Sensei_Learner::get_learner_term( $student_id );

		$this->directlyEnrolStudent( $student_id, $course_id );
		$this->assertTrue( $provider->is_enrolled( $student_id, $course_id ), 'Student should be enrolled after directly enrolling them.' );

		$provider->withdraw_learner( $student_id, $course_id );

		$this->assertFalse( $provider->is_enrolled( $student_id, $course_id ), 'Student should not be enrolled after withdrawing them from the course.' );
	}

	/**
	 * Test debug messages are generated correctly for learner without legacy migration.
	 */
	public function testDebugEnrolledNoLegacy() {
		$provider   = $this->getManualEnrolmentProvider();
		$course_id  = $this->getSimpleCourseId();
		$student_id = $this->getStandardStudentUserId();

		$debug          = $provider->debug( $student_id, $course_id );
		$expected_debug = [
			__( 'Learner manual enrollment <strong>was not migrated</strong> from a legacy version of Sensei LMS.', 'sensei-lms' ),
		];

		$this->assertEquals( $expected_debug, $debug );
	}

	/**
	 * Test debug messages are generated correctly for a learner with legacy migration who wasn't enrolled from legacy.
	 */
	public function testDebugEnrolledLegacyNotEnrolled() {
		$provider   = $this->getManualEnrolmentProvider();
		$course_id  = $this->getSimpleCourseId();
		$student_id = $this->getStandardStudentUserId();

		// Set the legacy enrolment status.
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$provider_state   = $course_enrolment->get_provider_state( $provider, $student_id );
		$provider_state->set_stored_value( Sensei_Course_Manual_Enrolment_Provider::DATA_KEY_LEGACY_MIGRATION, false );
		$provider_state->save();

		$debug          = $provider->debug( $student_id, $course_id );
		$expected_debug = [
			__( 'Learner <strong>did have</strong> course progress at the time of manual enrollment migration.', 'sensei-lms' ),
			__( 'Manual enrollment <strong>was not provided</strong> to the learner on legacy migration.', 'sensei-lms' ),
		];

		$this->assertEquals( $expected_debug, $debug );
	}

	/**
	 * Test debug messages are generated correctly for a learner with legacy migration who was enrolled from legacy.
	 */
	public function testDebugEnrolledLegacyEnrolled() {
		$provider   = $this->getManualEnrolmentProvider();
		$course_id  = $this->getSimpleCourseId();
		$student_id = $this->getStandardStudentUserId();

		// Set the legacy migration status.
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$provider_state   = $course_enrolment->get_provider_state( $provider, $student_id );
		$provider_state->set_stored_value( Sensei_Course_Manual_Enrolment_Provider::DATA_KEY_LEGACY_MIGRATION, true );
		$provider_state->save();

		$debug          = $provider->debug( $student_id, $course_id );
		$expected_debug = [
			__( 'Learner <strong>did have</strong> course progress at the time of manual enrollment migration.', 'sensei-lms' ),
			__( 'Manual enrollment <strong>was provided</strong> to the learner on legacy migration.', 'sensei-lms' ),
		];

		$this->assertEquals( $expected_debug, $debug );
	}

	/**
	 * Creates a standard student user account.
	 *
	 * @return int
	 */
	private function getStandardStudentUserId() {
		return $this->factory->user->create();
	}

	/**
	 * Gets a simple course ID.
	 *
	 * @return int
	 */
	private function getSimpleCourseId() {
		return $this->factory->course->create();
	}

}

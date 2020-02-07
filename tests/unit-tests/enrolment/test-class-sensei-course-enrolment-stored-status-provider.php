<?php
require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';

/**
 * Tests for Sensei_Course_Enrolment_Stored_Status_Provider abstract class.
 *
 * @group course-enrolment
 */
class Sensei_Course_Enrolment_Stored_Status_Provider_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Manual_Test_Helpers;

	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Tests to make sure the initial state is used when checking enrolment and no state currently exists.
	 */
	public function testIsEnrolledUsingInitialState() {
		$student_id = $this->getStandardStudentUserId();
		$course_id  = $this->getSimpleCourseId();

		$initially_false_provider = new Sensei_Test_Enrolment_Provider_Stateful_Initially_False();
		$initially_true_provider  = new Sensei_Test_Enrolment_Provider_Stateful_Initially_True();

		$this->assertFalse( $initially_false_provider->is_enrolled( $student_id, $course_id ), 'Initial value of false should be returned when no state has been set' );
		$this->assertTrue( $initially_true_provider->is_enrolled( $student_id, $course_id ), 'Initial value of true should be returned when no state has been set' );
	}

	/**
	 * Tests to make sure the enrolled status can be cleared.
	 *
	 * @covers \Sensei_Course_Enrolment_Stored_Status_Provider::clear_enrolment_status
	 * @covers \Sensei_Course_Enrolment_Stored_Status_Provider::has_enrolment_status
	 */
	public function testClearEnrolmentStatus() {
		$student_id = $this->getStandardStudentUserId();
		$course_id  = $this->getSimpleCourseId();

		$initially_false_provider = new Sensei_Test_Enrolment_Provider_Stateful_Initially_False();

		$initially_false_provider->proxy_set_enrolment_status( $student_id, $course_id, true );
		$this->assertTrue( $initially_false_provider->is_enrolled( $student_id, $course_id ), 'Student should initially be enrolled' );
		$this->assertTrue( $initially_false_provider->proxy_has_enrolment_status( $student_id, $course_id ), 'Enrolment state should be set' );

		$initially_false_provider->proxy_clear_enrolment_status( $student_id, $course_id );
		$this->assertFalse( $initially_false_provider->proxy_has_enrolment_status( $student_id, $course_id ), 'Enrolment state should not be set' );
		$this->assertFalse( $initially_false_provider->is_enrolled( $student_id, $course_id ), 'Student should not be enrolled' );
	}

	/**
	 * Tests to make sure the enrolled status can be set.
	 */
	public function testSetEnrolmentStatus() {
		$student_id = $this->getStandardStudentUserId();
		$course_id  = $this->getSimpleCourseId();

		$initially_false_provider = new Sensei_Test_Enrolment_Provider_Stateful_Initially_False();
		$this->assertFalse( $initially_false_provider->is_enrolled( $student_id, $course_id ), 'Student should not initially be enrolled' );

		$initially_false_provider->proxy_set_enrolment_status( $student_id, $course_id, true );

		$this->assertTrue( $initially_false_provider->is_enrolled( $student_id, $course_id ), 'Student should be enrolled' );
	}

	/**
	 * Tests to make sure the enrolled status can be retrieved.
	 */
	public function testGetEnrolmentStatus() {
		$student_id = $this->getStandardStudentUserId();
		$course_id  = $this->getSimpleCourseId();

		$initially_false_provider = new Sensei_Test_Enrolment_Provider_Stateful_Initially_False();
		$this->assertFalse( $initially_false_provider->is_enrolled( $student_id, $course_id ), 'Student should not initially be enrolled' );

		$initially_false_provider->proxy_set_enrolment_status( $student_id, $course_id, true );

		$this->assertTrue( $initially_false_provider->is_enrolled( $student_id, $course_id ), 'Student should be enrolled' );
		$this->assertTrue( $initially_false_provider->proxy_get_enrolment_status( $student_id, $course_id ), 'This method should also show the user is enrolled' );
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

<?php

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';

class Sensei_Class_Student_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Test_Helpers;

	/**
	 * Constructor function
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * setup function
	 *
	 * This function sets up the lessons, quizes and their questions. This function runs before
	 * every single test in this class
	 */
	public function setUp() {
		parent::setup();

		$this->factory = new Sensei_Factory();

		self::resetEnrolmentProviders();
	}//end setUp()

	public function tearDown() {
		parent::tearDown();
		$this->factory->tearDown();
		self::resetEnrolmentProviders();
	}

	/**
	 * Testing the quiz class to make sure it is loaded
	 */
	public function testClassInstance() {

		// test if the global sensei quiz class is loaded
		$this->assertTrue( class_exists( 'Sensei_Learner' ), 'the Sensei student class is not loaded' );

	} // end testClassInstance

	/**
	 * Tests that user enrolments terms are deleted when user is deleted.
	 *
	 * @covers Sensei_Learner::delete_all_user_activity
	 */
	public function testDeleteUserEnrolments() {
		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Always_Provides::class );
		$this->prepareEnrolmentManager();

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$this->assertTrue( $course_enrolment->is_enrolled( $student_id ) );

		wp_delete_user( $student_id );

		$student_term = Sensei_Learner::get_learner_term( $student_id );
		$this->assertFalse( has_term( $student_term->term_id, Sensei_PostTypes::LEARNER_TAXONOMY_NAME, $course_id ), 'User terms should be removed when user is deleted' );
	}

	/**
	 * Testing the get_learner_full_name function. This function tests the basic assumptions.
	 */
	public function testGetLearnerFullNameBasicAssumptions() {

		// does the function exist?
		$this->assertTrue(
			method_exists( 'Sensei_Learner', 'get_full_name' ),
			'The learner class function `get_full_name` does not exist '
		);

		// make sure it blocks invalid parameters and returns false
		$this->assertFalse( Sensei_Learner::get_full_name( '' ), 'Invalid user_id should return false' );
		$this->assertFalse( Sensei_Learner::get_full_name( -200 ), 'Invalid user_id should return false' );
		$this->assertFalse( Sensei_Learner::get_full_name( 'abc' ), 'Invalid user_id should return false' );
		$this->assertFalse( Sensei_Learner::get_full_name( 4000000 ), 'Invalid user_id should return false' );

	}//end testGetLearnerFullNameBasicAssumptions()

	/**
	 * Testing the get_learner_full_name function to see if it returns what is expected.
	 */
	public function testGetLearnerFullName() {

		// setup assertion
		$test_user_id = wp_create_user( 'getlearnertestuser', 'password', 'getlearnertestuser@sensei-test.com' );

		$this->assertEquals(
			'getlearnertestuser',
			Sensei_Learner::get_full_name( $test_user_id ),
			'The user name should be equal to the display name when no first name and last name is specified'
		);

		// setup the next assertion
		$first_name        = 'Test';
		$last_name         = 'User';
		$updated_user_data = array(
			'ID'         => $test_user_id,
			'first_name' => $first_name,
			'last_name'  => $last_name,
		);
		wp_update_user( $updated_user_data );

		// does the function return 'first-name last-name' string?
		$this->assertEquals(
			'Test User',
			Sensei_Learner::get_full_name( $test_user_id ),
			'This function should return the users first name and last name.'
		);

	}//end testGetLearnerFullName()

}//end class

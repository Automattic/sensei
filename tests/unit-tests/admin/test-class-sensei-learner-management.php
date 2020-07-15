<?php
/**
 * Sensei learner management tests.
 *
 * @package sensei-lms
 * @since   3.4.0
 */

/**
 * Class for Sensei_Learner_Management tests.
 */
class Sensei_Learner_Management_Test extends WP_Test_REST_TestCase {
	use Sensei_Course_Enrolment_Test_Helpers;
	use Sensei_Course_Enrolment_Manual_Test_Helpers;

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
	 * Tests that withdraw manually enrolled user from course.
	 */
	public function testLearnerManagementWithdrawManuallyEnrolled() {
		$user_id   = $this->createStandardStudent();
		$course_id = $this->getSimpleCourse();

		$this->prepareEnrolmentManager();

		// Enrol user through manual provider.
		$enrolment_manager         = Sensei_Course_Enrolment_Manager::instance();
		$manual_enrolment_provider = $enrolment_manager->get_manual_enrolment_provider();
		$manual_enrolment_provider->enrol_learner( $user_id, $course_id );

		$result = $this->invokeActionMethod( 'withdraw', $user_id, $course_id );

		$this->assertTrue( $result );
	}

	/**
	 * Tests that withdraw provider enrolled user from course.
	 */
	public function testLearnerManagementWithdrawProviderEnrolled() {
		$user_id   = $this->createStandardStudent();
		$course_id = $this->getSimpleCourse();

		// Add provider with access.
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Always_Provides::class );
		$this->prepareEnrolmentManager();

		$result = $this->invokeActionMethod( 'withdraw', $user_id, $course_id );

		$this->assertTrue( $result );
	}

	/**
	 * Tests that withdraw manually and provider enrolled user from course.
	 */
	public function testLearnerManagementWithdrawManuallyAndProviderEnrolled() {
		$user_id   = $this->createStandardStudent();
		$course_id = $this->getSimpleCourse();

		// Add provider with access.
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Always_Provides::class );
		$this->prepareEnrolmentManager();

		// Enrol user through manual provider.
		$enrolment_manager         = Sensei_Course_Enrolment_Manager::instance();
		$manual_enrolment_provider = $enrolment_manager->get_manual_enrolment_provider();
		$manual_enrolment_provider->enrol_learner( $user_id, $course_id );

		$result = $this->invokeActionMethod( 'withdraw', $user_id, $course_id );

		$this->assertTrue( $result );
	}

	/**
	 * Tests that restore enrollment to current provider.
	 */
	public function testLearnerManagementRestoreEnrolment() {
		$user_id   = $this->createStandardStudent();
		$course_id = $this->getSimpleCourse();

		// Add provider with access.
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Always_Provides::class );
		$this->prepareEnrolmentManager();

		// Remove learner.
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$course_enrolment->remove_learner( $user_id );

		$result = $this->invokeActionMethod( 'enrol', $user_id, $course_id );

		$this->assertTrue( $result );
	}

	/**
	 * Tests that enroll user through manual provider when they don't have access through any provider.
	 */
	public function testLearnerManagementEnrolManually() {
		$user_id   = $this->createStandardStudent();
		$course_id = $this->getSimpleCourse();

		$this->prepareEnrolmentManager();

		$result = $this->invokeActionMethod( 'enrol', $user_id, $course_id );

		$this->assertTrue( $result );
	}

	/**
	 * Tests that manually enroll removed user in case that the provider doesn't give access anymore.
	 */
	public function testLearnerManagementEnrolRemovedManually() {
		$user_id   = $this->createStandardStudent();
		$course_id = $this->getSimpleCourse();

		$this->prepareEnrolmentManager();

		// Remove learner even without a provider enrollment.
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$course_enrolment->remove_learner( $user_id );

		$result = $this->invokeActionMethod( 'enrol', $user_id, $course_id );

		$this->assertTrue( $result );
	}

	/**
	 * Method to mock and invoke the action methods.
	 *
	 * @param string $action    Action to be invoked.
	 * @param int    $user_id   User ID.
	 * @param int    $course_id Course ID.
	 *
	 * @return boolean Success result.
	 */
	private function invokeActionMethod( $action, $user_id, $course_id ) {
		$method = new ReflectionMethod( Sensei_Learner_Management::class, $action );

		$method->setAccessible( true );

		return $method->invoke( Sensei()->learners, $user_id, $course_id );
	}
}

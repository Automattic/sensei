<?php
require_once SENSEI_TEST_FRAMEWORK_DIR . '/enrolment-providers/class-sensei-test-enrolment-provider-always-provides.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/enrolment-providers/class-sensei-test-enrolment-provider-denies-crooks.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/enrolment-providers/class-sensei-test-enrolment-provider-handles-dog-courses.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/enrolment-providers/class-sensei-test-enrolment-provider-never-handles.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/enrolment-providers/class-sensei-test-enrolment-provider-never-provides.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/enrolment-providers/class-sensei-test-enrolment-provider-provides-for-dinosaurs.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/enrolment-providers/class-sensei-test-enrolment-provider-version-morph.php';

class Sensei_Class_Course_Enrolment_Test extends WP_UnitTestCase {

	/**
	 * Setup function.
	 */
	public function setup() {
		parent::setup();

		$this->factory = new Sensei_Factory();
	}

	public function testGetCourseInstanceMultiple() {
		$instance_a = Sensei_Course_Enrolment::get_course_instance( 1 );
		$instance_b = Sensei_Course_Enrolment::get_course_instance( 1 );

		$this->assertTrue( $instance_a instanceof Sensei_Course_Enrolment );
		$this->assertTrue( $instance_a === $instance_b, 'Instances should be the same for the same course ID' );
	}

	public function testGetCourseId() {
		Sensei_Course_Enrolment::get_course_instance( 999 );
		$instance = Sensei_Course_Enrolment::get_course_instance( 1000 );
		Sensei_Course_Enrolment::get_course_instance( 1001 );

		$this->assertTrue( $instance instanceof Sensei_Course_Enrolment );
		$this->assertEquals( 1000, $instance->get_course_id(), 'Course ID for provided instance did not match what was expected' );
	}

	public function testSimpleAccessCheck() {

	}

	/**
	 * Creates a standard student user account.
	 *
	 * @return int
	 */
	private function createStandardStudent() {
		return $this->factory->user->create();
	}

	/**
	 * Create a user with "Dinosaur" in its display name.
	 *
	 * @return int
	 */
	private function createDinosaurStudent() {
		$user = get_user_by( 'ID', $this->createStandardStudent() );

		$dinosaur_names = [
			'Pat',
			'Tony',
			'Jan',
			'Meg',
		];

		shuffle( $dinosaur_names );
		$user->display_name = 'Dinosaur ' . $dinosaur_names[0];
		wp_update_user( $user );

		return $user->ID;
	}

	/**
	 * Turns a user into a crook by adding "I am a crook" to their description.
	 *
	 * @param int $user_id
	 * @return int
	 */
	private function turnStudentIntoCrook( $user_id ) {
		$user = get_user_by( 'ID', $user_id );

		$user->description = 'I am a crook';
		wp_update_user( $user );

		return $user_id;
	}

	/**
	 * Adds an enrolment provider.
	 */
	private function addEnrolmentProvider( $class_name ) {
		add_filter(
			'sensei_course_enrolment_providers',
			function( $providers ) use ( $class_name ) {
				if ( in_array( $class_name, $providers, true ) ) {
					return $providers;
				}

				$providers[] = $class_name;

				return $class_name;
			}
		);
	}


	/**
	 * Resets the enrolment providers.
	 */
	private function resetEnrolmentProviders() {
		remove_all_filters( 'sensei_course_enrolment_providers' );

		$enrolment_providers = new ReflectionProperty( Sensei_Course_Enrolment::class, 'enrolment_providers' );
		$enrolment_providers->setAccessible( true );
		$enrolment_providers->setValue( null );

		$course_enrolment_instances = new ReflectionProperty( Sensei_Course_Enrolment::class, 'instances' );
		$course_enrolment_instances->setAccessible( true );
		$course_enrolment_instances->setValue( [] );
	}
}

<?php
/**
 * File with trait Course_Enrolment_Test_Helpers.
 *
 * @package sensei-tests
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Using PHPUnit conventions.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once SENSEI_TEST_FRAMEWORK_DIR . '/enrolment-providers/class-sensei-test-enrolment-provider-always-provides.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/enrolment-providers/class-sensei-test-enrolment-provider-denies-crooks.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/enrolment-providers/class-sensei-test-enrolment-provider-handles-dog-courses.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/enrolment-providers/class-sensei-test-enrolment-provider-never-handles.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/enrolment-providers/class-sensei-test-enrolment-provider-never-provides.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/enrolment-providers/class-sensei-test-enrolment-provider-provides-for-dinosaurs.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/enrolment-providers/class-sensei-test-enrolment-provider-version-morph.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/enrolment-providers/class-sensei-test-enrolment-provider-stateful-initially-false.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/enrolment-providers/class-sensei-test-enrolment-provider-stateful-initially-true.php';

/**
 * Helpers for course enrolment related tests.
 *
 * @since 3.0.0
 */
trait Sensei_Course_Enrolment_Test_Helpers {

	/**
	 * Resets the enrolment providers. Do not do this in production. This is just to reset state in tests.
	 */
	private static function resetEnrolmentProviders() {
		remove_all_filters( 'sensei_course_enrolment_providers' );

		$enrolment_providers = new ReflectionProperty( Sensei_Course_Enrolment_Manager::class, 'enrolment_providers' );
		$enrolment_providers->setAccessible( true );
		$enrolment_providers->setValue( Sensei_Course_Enrolment_Manager::instance(), null );

		$course_enrolment_instances = new ReflectionProperty( Sensei_Course_Enrolment::class, 'instances' );
		$course_enrolment_instances->setAccessible( true );
		$course_enrolment_instances->setValue( [] );
	}

	/**
	 * Resets the state stores.
	 */
	private static function resetEnrolmentStateStores() {
		$state_store_instances = new ReflectionProperty( Sensei_Enrolment_Provider_State_Store::class, 'instances' );
		$state_store_instances->setAccessible( true );
		$state_store_instances->setValue( [] );
	}

	/**
	 * Gets a simple course ID.
	 *
	 * @return int
	 */
	private function getSimpleCourse() {
		return $this->factory->course->create();
	}

	/**
	 * Gets a course ID for a course with "dog" in the title.
	 *
	 * @return int
	 */
	private function getDogCourse() {
		return $this->factory->course->create(
			[
				'post_title' => 'Dog Whispering',
			]
		);
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
		$user_id = $this->createStandardStudent();

		$dinosaur_names = [
			'Pat',
			'Tony',
			'Jan',
			'Meg',
		];

		shuffle( $dinosaur_names );

		wp_update_user(
			[
				'ID'           => $user_id,
				'display_name' => 'Dinosaur ' . $dinosaur_names[0],
			]
		);

		return $user_id;
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
	 * Turns a user into a crook by adding "I am a crook" to their description.
	 *
	 * @param int $user_id
	 * @return int
	 */
	private function turnStudentIntoNormal( $user_id ) {
		$user = get_user_by( 'ID', $user_id );

		$user->description = 'I am a normal';
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
				$providers[] = new $class_name();

				return $providers;
			}
		);
	}

	/**
	 * Removes an enrolment provider.
	 */
	private function removeEnrolmentProvider( $class_name ) {
		add_filter(
			'sensei_course_enrolment_providers',
			function( $providers ) use ( $class_name ) {
				foreach ( $providers as $index => $provider ) {
					if ( get_class( $provider ) === $class_name ) {
						unset( $providers[ $index ] );
					}
				}

				return $providers;
			}
		);
	}

	/**
	 * Prepare the enrolment manager. Do not do this in production. This is just to simulate what is done on `init`.
	 */
	private function prepareEnrolmentManager() {
		Sensei_Course_Enrolment_Manager::instance()->collect_enrolment_providers();
	}
}

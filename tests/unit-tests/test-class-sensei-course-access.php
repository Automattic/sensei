<?php
require_once SENSEI_TEST_FRAMEWORK_DIR . '/access-providers/class-sensei-test-access-provider-always-provides.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/access-providers/class-sensei-test-access-provider-denies-crooks.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/access-providers/class-sensei-test-access-provider-handles-dog-courses.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/access-providers/class-sensei-test-access-provider-never-handles.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/access-providers/class-sensei-test-access-provider-never-provides.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/access-providers/class-sensei-test-access-provider-provides-for-dinosaurs.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/access-providers/class-sensei-test-access-provider-version-morph.php';

class Sensei_Class_Course_Access_Test extends WP_UnitTestCase {

	/**
	 * Setup function.
	 */
	public function setup() {
		parent::setup();

		$this->factory = new Sensei_Factory();
	}

	public function testGetCourseInstanceMultiple() {
		$instanceA = Sensei_Course_Access::get_course_instance(1);
		$instanceB = Sensei_Course_Access::get_course_instance(1);

		$this->assertTrue( $instanceA instanceof Sensei_Course_Access );
		$this->assertTrue( $instanceA === $instanceB, 'Instances should be the same for the same course ID' );
	}

	public function testGetCourseId() {
		Sensei_Course_Access::get_course_instance( 999 );
		$instance = Sensei_Course_Access::get_course_instance( 1000 );
		Sensei_Course_Access::get_course_instance( 1001 );

		$this->assertTrue( $instance instanceof Sensei_Course_Access );
		$this->assertEquals( 1000, $instance->get_course_id(), 'Course ID for provided instance did not match what was expected' );
	}


	/**
	 * Resets the access providers.
	 */
	private function resetAccessProviders() {
		remove_all_filters( 'sensei_course_access_providers' );

		$access_providers = new ReflectionProperty( Sensei_Course_Access::class, 'access_providers' );
		$access_providers->setAccessible( true );
		$access_providers->setValue( null );

		$course_access_instances = new ReflectionProperty( Sensei_Course_Access::class, 'instances' );
		$course_access_instances->setAccessible( true );
		$course_access_instances->setValue( [] );
	}
}

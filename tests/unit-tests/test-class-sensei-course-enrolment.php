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
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
		$this->resetEnrolmentProviders();
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
		$this->assertEquals( 1000, $instance->get_course_id(), 'Course ID for provided instance did not match the created instance' );
	}

	/**
	 * Simple check for positive enrolment with simple provider.
	 */
	public function testEnrolmentCheckAlwaysProvides() {
		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Always_Provides::class );

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$this->assertTrue( $course_enrolment->is_enroled( $student_id ) );
	}

	/**
	 * Simple check for negative enrolment with simple provider.
	 */
	public function testEnrolmentCheckNeverProvides() {
		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Never_Provides::class );

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$this->assertFalse( $course_enrolment->is_enroled( $student_id ) );
	}

	/**
	 * Check for negative result if no providers are registered.
	 */
	public function testEnrolmentCheckNoProviders() {
		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$this->assertFalse( $course_enrolment->is_enroled( $student_id ) );
	}

	/**
	 * Check for negative result for providers that shouldn't handle a course.
	 */
	public function testEnrolmentCheckOpinionatedProviders() {
		$course_id_simple = $this->getSimpleCourse();
		$course_id_dog    = $this->getDogCourse();
		$student_id       = $this->createStandardStudent();

		// This provider provides enrolment for any student when a course with "dog" in the title is checked.
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Handles_Dog_Courses::class );
		$course_enrolment_simple = Sensei_Course_Enrolment::get_course_instance( $course_id_simple );
		$course_enrolment_dog    = Sensei_Course_Enrolment::get_course_instance( $course_id_dog );

		$this->assertFalse( $course_enrolment_simple->is_enroled( $student_id ), 'Non-dog courses should be NOT handled and therefore enrolment should not be provided' );
		$this->assertTrue( $course_enrolment_dog->is_enroled( $student_id ), 'Dog courses should be handled and enrol all students' );
	}

	/**
	 * Check for positive result when positive and negative provider exist. Checks for case when positive is registered first.
	 */
	public function testEnrolmentCheckPositivePrevailsFirst() {
		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Always_Provides::class );
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Never_Provides::class );

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$this->assertTrue( $course_enrolment->is_enroled( $student_id ), 'One positive provider should always enrol student in course' );
	}

	/**
	 * Check for positive result when positive and negative provider exist. Checks for case when positive is registered second.
	 */
	public function testEnrolmentCheckPositivePrevailsSecond() {
		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Never_Provides::class );
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Always_Provides::class );

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$this->assertTrue( $course_enrolment->is_enroled( $student_id ), 'One positive provider should always enrol student in course' );
	}

	/**
	 * Check for negative result if no providers that handle this course's registration are handled.
	 */
	public function testEnrolmentCheckNoHandlingProviders() {
		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Never_Handles::class );

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$this->assertFalse( $course_enrolment->is_enroled( $student_id ), 'Student should not be enroled if there are no providers handling enrolment for a course' );
	}

	/**
	 * Tests to make sure non-handling providers aren't used. Never_Handles has a true response, but it should never be asked.
	 */
	public function testEnrolmentCheckNonHandlingProvidersIgnored() {
		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Never_Handles::class );
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Never_Provides::class );

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$this->assertFalse( $course_enrolment->is_enroled( $student_id ), 'Non-handling providers should never be used to determine enrolment' );
	}

	/**
	 * Tests to make sure the cached response is updated on a change to the providers.
	 */
	public function testEnrolmentCheckVersionCachingWorks() {
		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();

		// Initial request.
		$this->resetAndSetUpVersionedProvider( false ); // Version : 1; Odd numbers do not provide enrolment.
		$course_enrolment_a = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$is_enroled_a       = $course_enrolment_a->is_enroled( $student_id );
		$is_enroled_a_2     = $course_enrolment_a->is_enroled( $student_id );
		$this->assertFalse( $is_enroled_a, 'This version of the provider should not provide enrolment' );
		$this->assertEquals( $is_enroled_a_2, $is_enroled_a, 'Duplicate calls should use caching and return same result' );

		// Bump version. Second request.
		$this->resetAndSetUpVersionedProvider( true ); // Version : 2; Even numbers provide enrolment.
		$course_enrolment_b = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$is_enroled_b       = $course_enrolment_b->is_enroled( $student_id );
		$this->assertTrue( $is_enroled_b, 'Cache should refresh on version chnage and this version of the provider should provide enrolment.' );
	}

	/**
	 * Tests a provider that actually has an opinion about which students are enroled. Only enrols users with "Dinosaur" in their name.
	 */
	public function testEnrolmentCheckConditionalUserPositive() {
		$course_id           = $this->getSimpleCourse();
		$student_id_standard = $this->createStandardStudent();
		$student_id_dino     = $this->createDinosaurStudent();

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Provides_For_Dinosaurs::class );

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$this->assertFalse( $course_enrolment->is_enroled( $student_id_standard ), 'This provider should only allow dinosaurs' );
		$this->assertTrue( $course_enrolment->is_enroled( $student_id_dino ), 'This provider should allow dinosaurs' );
	}

	/**
	 * Tests a provider that actually has an opinion about which students are enroled. Denies crooks.
	 */
	public function testEnrolmentCheckConditionalUserNegative() {
		$course_id        = $this->getSimpleCourse();
		$student_id_crook = $this->turnStudentIntoCrook( $this->createStandardStudent() );
		$student_id_okay  = $this->createStandardStudent();

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Denies_Crooks::class );

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$this->assertFalse( $course_enrolment->is_enroled( $student_id_crook ), 'This provider should deny crooks' );
		$this->assertTrue( $course_enrolment->is_enroled( $student_id_okay ), 'This provider should allow okay students' );
	}

	/**
	 * Tests re-checking enrolment when something might have changed.
	 */
	public function testTriggerCourseEnrolmentCheckSimple() {
		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Denies_Crooks::class );

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$pre_crook_enrolment = $course_enrolment->is_enroled( $student_id );
		$this->assertTrue( $pre_crook_enrolment, 'As a non-crook, this student should be enroled' );

		$this->turnStudentIntoCrook( $student_id );
		$pre_notify_enrolment = $course_enrolment->is_enroled( $student_id );
		$this->assertTrue( $pre_notify_enrolment, 'Even as a crook, the cached value should be used until we notify the course enrolment handler' );

		$course_enrolment->trigger_course_enrolment_check( $student_id );
		$post_notify_enrolment = $course_enrolment->is_enroled( $student_id );
		$this->assertFalse( $post_notify_enrolment, 'Now that the crook status is known, they should no longer be enroled in the course' );
	}

	/**
	 * Tests storage of the term association for positive results.
	 *
	 * @covers \Sensei_Course_Enrolment::save_enrolment
	 */
	public function testSaveEnrolmentStore() {
		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Always_Provides::class );

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$this->assertTrue( $course_enrolment->is_enroled( $student_id ) );

		$student_term = Sensei_Learner::get_learner_term( $student_id );
		$this->assertTrue( has_term( $student_term->term_id, Sensei_PostTypes::LEARNER_TAXONOMY_NAME, $course_id ), 'Student term was not associated with the course but should have been' );
	}

	/**
	 * Tests remove of the stored term association for when enrolment is removed.
	 *
	 * @covers \Sensei_Course_Enrolment::save_enrolment
	 */
	public function testSaveEnrolmentRemoved() {
		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Denies_Crooks::class );

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$this->assertTrue( $course_enrolment->is_enroled( $student_id ) );

		$student_term = Sensei_Learner::get_learner_term( $student_id );
		$this->assertTrue( has_term( $student_term->term_id, Sensei_PostTypes::LEARNER_TAXONOMY_NAME, $course_id ), 'Student term was not associated with the course but should have been' );

		// Turns the student into someone who won't be enroled by provider.
		$this->turnStudentIntoCrook( $student_id );
		$course_enrolment->trigger_course_enrolment_check( $student_id );

		$this->assertFalse( has_term( $student_term->term_id, Sensei_PostTypes::LEARNER_TAXONOMY_NAME, $course_id ), 'Student term associated should be removed when enrolment was removed' );
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

				return $providers;
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

	/**
	 * Helper for `\Sensei_Class_Course_Enrolment_Test::testEnrolmentCheckVersionCachingWorks`.
	 */
	private function resetAndSetUpVersionedProvider( $bump_version ) {
		$this->resetEnrolmentProviders();
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Version_Morph::class );

		if ( $bump_version ) {
			Sensei_Test_Enrolment_Provider_Version_Morph::$version++;
		}
	}
}

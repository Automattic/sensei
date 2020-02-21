<?php

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';

/**
 * Tests for Sensei_Course_Enrolment class.
 *
 * @group course-enrolment
 */
class Sensei_Course_Enrolment_Test extends WP_UnitTestCase {
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
		$this->prepareEnrolmentManager();

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$this->assertTrue( $course_enrolment->is_enrolled( $student_id ) );
	}

	/**
	 * Simple check for negative enrolment with simple provider.
	 */
	public function testEnrolmentCheckNeverProvides() {
		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Never_Provides::class );
		$this->prepareEnrolmentManager();

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$this->assertFalse( $course_enrolment->is_enrolled( $student_id ) );
	}

	/**
	 * Check for negative result if no providers are registered.
	 */
	public function testEnrolmentCheckNoProviders() {
		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$this->assertFalse( $course_enrolment->is_enrolled( $student_id ) );
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
		$this->prepareEnrolmentManager();

		$course_enrolment_simple = Sensei_Course_Enrolment::get_course_instance( $course_id_simple );
		$course_enrolment_dog    = Sensei_Course_Enrolment::get_course_instance( $course_id_dog );

		$this->assertFalse( $course_enrolment_simple->is_enrolled( $student_id ), 'Non-dog courses should be NOT handled and therefore enrolment should not be provided' );
		$this->assertTrue( $course_enrolment_dog->is_enrolled( $student_id ), 'Dog courses should be handled and enrol all students' );
	}

	/**
	 * Check for positive result when positive and negative provider exist. Checks for case when positive is registered first.
	 */
	public function testEnrolmentCheckPositivePrevailsFirst() {
		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Always_Provides::class );
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Never_Provides::class );
		$this->prepareEnrolmentManager();

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$this->assertTrue( $course_enrolment->is_enrolled( $student_id ), 'One positive provider should always enrol student in course' );
	}

	/**
	 * Check that unpublished courses return `false` for `is_enrolled`.
	 */
	public function testEnrolmentCheckUnpublishedCourse() {
		$course_id  = $this->factory->course->create(
			[
				'post_status' => 'draft',
			]
		);
		$student_id = $this->createStandardStudent();
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Always_Provides::class );
		$this->prepareEnrolmentManager();

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$this->assertFalse( $course_enrolment->is_enrolled( $student_id ), 'Students cannot be enrolled in unpublished courses.' );
	}

	/**
	 * Check for positive result when positive and negative provider exist. Checks for case when positive is registered second.
	 */
	public function testEnrolmentCheckPositivePrevailsSecond() {
		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Never_Provides::class );
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Always_Provides::class );
		$this->prepareEnrolmentManager();

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$this->assertTrue( $course_enrolment->is_enrolled( $student_id ), 'One positive provider should always enrol student in course' );
	}

	/**
	 * Check for negative result if no providers that handle this course's registration are handled.
	 */
	public function testEnrolmentCheckNoHandlingProviders() {
		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Never_Handles::class );
		$this->prepareEnrolmentManager();

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$this->assertFalse( $course_enrolment->is_enrolled( $student_id ), 'Student should not be enrolled if there are no providers handling enrolment for a course' );
	}

	/**
	 * Tests to make sure non-handling providers aren't used. Never_Handles has a true response, but it should never be asked.
	 */
	public function testEnrolmentCheckNonHandlingProvidersIgnored() {
		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Never_Handles::class );
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Never_Provides::class );
		$this->prepareEnrolmentManager();

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$this->assertFalse( $course_enrolment->is_enrolled( $student_id ), 'Non-handling providers should never be used to determine enrolment' );
	}

	/**
	 * Tests to make sure the cached response is updated on a change to the providers.
	 */
	public function testEnrolmentCheckVersionCachingWorks() {
		Sensei_Test_Enrolment_Provider_Version_Morph::$version = 1;

		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();

		// Initial request.
		$this->resetAndSetUpVersionedProvider( false ); // Version : 1; Odd numbers do not provide enrolment.
		$course_enrolment_a = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$is_enrolled_a      = $course_enrolment_a->is_enrolled( $student_id );
		$is_enrolled_a_2    = $course_enrolment_a->is_enrolled( $student_id );
		$this->assertFalse( $is_enrolled_a, 'This version of the provider should not provide enrolment' );
		$this->assertEquals( $is_enrolled_a_2, $is_enrolled_a, 'Duplicate calls should use caching and return same result' );

		// Bump version. Second request.
		$this->resetAndSetUpVersionedProvider( true ); // Version : 2; Even numbers provide enrolment.
		$course_enrolment_b = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$is_enrolled_b      = $course_enrolment_b->is_enrolled( $student_id );
		$this->assertTrue( $is_enrolled_b, 'Cache should refresh on version chnage and this version of the provider should provide enrolment.' );
	}

	/**
	 * Tests a provider that actually has an opinion about which students are enrolled. Only enrols users with "Dinosaur" in their name.
	 */
	public function testEnrolmentCheckConditionalUserPositive() {
		$course_id           = $this->getSimpleCourse();
		$student_id_standard = $this->createStandardStudent();
		$student_id_dino     = $this->createDinosaurStudent();

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Provides_For_Dinosaurs::class );
		$this->prepareEnrolmentManager();

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$this->assertFalse( $course_enrolment->is_enrolled( $student_id_standard ), 'This provider should only allow dinosaurs' );
		$this->assertTrue( $course_enrolment->is_enrolled( $student_id_dino ), 'This provider should allow dinosaurs' );
	}

	/**
	 * Tests a provider that actually has an opinion about which students are enrolled. Denies crooks.
	 */
	public function testEnrolmentCheckConditionalUserNegative() {
		$course_id        = $this->getSimpleCourse();
		$student_id_crook = $this->turnStudentIntoCrook( $this->createStandardStudent() );
		$student_id_okay  = $this->createStandardStudent();

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Denies_Crooks::class );
		$this->prepareEnrolmentManager();

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$this->assertFalse( $course_enrolment->is_enrolled( $student_id_crook ), 'This provider should deny crooks' );
		$this->assertTrue( $course_enrolment->is_enrolled( $student_id_okay ), 'This provider should allow okay students' );
	}

	/**
	 * Tests re-checking enrolment when we have it ignore cached result.
	 */
	public function testCourseEnrolmentCheckDoNotUseCache() {
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

		// Check enrolment but don't let it use the cached result.
		$post_notify_enrolment = $course_enrolment->is_enrolled( $student_id, false );
		$this->assertFalse( $post_notify_enrolment, 'Now that the crook status is known, they should no longer be enrolled in the course' );
	}

	/**
	 * Tests storage of the term association for positive results.
	 *
	 * @covers Sensei_Course_Enrolment::save_enrolment
	 */
	public function testSaveEnrolmentStore() {
		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Always_Provides::class );
		$this->prepareEnrolmentManager();

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$this->assertTrue( $course_enrolment->is_enrolled( $student_id ) );

		$student_term = Sensei_Learner::get_learner_term( $student_id );
		$this->assertTrue( has_term( $student_term->term_id, Sensei_PostTypes::LEARNER_TAXONOMY_NAME, $course_id ), 'Student term was not associated with the course but should have been' );
	}

	/**
	 * Tests remove of the stored term association for when enrolment is removed.
	 *
	 * @covers Sensei_Course_Enrolment::save_enrolment
	 */
	public function testSaveEnrolmentRemoved() {
		$course_id  = $this->getSimpleCourse();
		$student_id = $this->createStandardStudent();

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Denies_Crooks::class );
		$this->prepareEnrolmentManager();

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$this->assertTrue( $course_enrolment->is_enrolled( $student_id ) );

		$student_term = Sensei_Learner::get_learner_term( $student_id );
		$this->assertTrue( has_term( $student_term->term_id, Sensei_PostTypes::LEARNER_TAXONOMY_NAME, $course_id ), 'Student term was not associated with the course but should have been' );

		// Turns the student into someone who won't be enrolled by provider.
		$this->turnStudentIntoCrook( $student_id );
		$course_enrolment->is_enrolled( $student_id, false );

		$this->assertFalse( has_term( $student_term->term_id, Sensei_PostTypes::LEARNER_TAXONOMY_NAME, $course_id ), 'Student term associated should be removed when enrolment was removed' );
	}

	/**
	 * Tests getting a fresh provider state result.
	 */
	public function testGetProviderStateFresh() {
		$provider_class = Sensei_Test_Enrolment_Provider_Always_Provides::class;
		$this->addEnrolmentProvider( $provider_class );
		$this->prepareEnrolmentManager();

		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();
		$provider          = $enrolment_manager->get_enrolment_provider_by_id( $provider_class::ID );
		$course_id         = $this->getSimpleCourse();
		$student_id        = $this->createStandardStudent();

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$provider_state   = $course_enrolment->get_provider_state( $provider, $student_id );

		$this->assertTrue( $provider_state instanceof Sensei_Enrolment_Provider_State );
	}

	/**
	 * Tests getting a provider state result that has persisted.
	 */
	public function testGetProviderStateSaved() {
		$course_id     = $this->getSimpleCourse();
		$student_id    = $this->createStandardStudent();
		$persisted_set = '{"always-provides":{"d":{"test":1234},"l":[[1581098440,"This is a log message"]]}}';
		update_user_meta( $student_id, Sensei_Enrolment_Provider_State_Store::META_PREFIX_ENROLMENT_PROVIDERS_STATE . $course_id, $persisted_set );

		$provider_class = Sensei_Test_Enrolment_Provider_Always_Provides::class;
		$this->addEnrolmentProvider( $provider_class );
		$this->prepareEnrolmentManager();

		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();
		$provider          = $enrolment_manager->get_enrolment_provider_by_id( $provider_class::ID );
		$course_enrolment  = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$provider_state    = $course_enrolment->get_provider_state( $provider, $student_id );

		$this->assertTrue( $provider_state instanceof Sensei_Enrolment_Provider_State );
		$this->assertEquals( 1234, $provider_state->get_stored_value( 'test' ), 'Persisted stored value should be retrieved' );

		$logs = $provider_state->get_logs();
		$this->assertEquals( 'This is a log message', $logs[0][1], 'Persisted log entry should be retrieved' );
	}

	/**
	 * Tests invalidating results for current learners only and enqueuing async job.
	 */
	public function testRecalculateEnrolmentCurrentLearnersOnly() {
		$course_id         = $this->getSimpleCourse();
		$other_user_ids    = $this->factory()->user->create_many( 2 );
		$enrolled_user_ids = $this->factory()->user->create_many( 5 );
		$all_user_ids      = array_merge( $enrolled_user_ids, $other_user_ids );

		foreach ( $other_user_ids as $user_id ) {
			$this->turnStudentIntoCrook( $user_id );
		}

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Denies_Crooks::class );
		$this->prepareEnrolmentManager();
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$course_salt      = $course_enrolment->get_course_enrolment_salt();

		// First, ensure we've calculated all users.
		foreach ( $all_user_ids as $user_id ) {
			$course_enrolment->is_enrolled( $user_id );
		}

		$job = $course_enrolment->recalculate_enrolment( true );

		foreach ( $enrolled_user_ids as $user_id ) {
			$this->assertEquals( '', get_user_meta( $user_id, $course_enrolment->get_enrolment_results_meta_key(), true ), 'Enrolled users should have an invalidated enrolment result set' );
		}

		foreach ( $other_user_ids as $user_id ) {
			$this->assertNotEquals( '', get_user_meta( $user_id, $course_enrolment->get_enrolment_results_meta_key(), true ), 'Unenrolled users should NOT have an invalidated enrolment result set' );
		}
		$this->assertEquals( $course_enrolment->get_course_enrolment_salt(), $course_salt, 'The course salt should not have been reset' );

		$this->assertTrue( $job instanceof Sensei_Enrolment_Course_Calculation_Job, 'Returned job should be an instance of Sensei_Enrolment_Course_Calculation_Job' );
		$this->assertNotEmpty( wp_next_scheduled( Sensei_Enrolment_Course_Calculation_Job::get_name(), [ $job->get_args() ] ), 'Job should have been scheduled' );
	}

	/**
	 * Tests recalculating enrolment for all users.
	 */
	public function testRecalculateEnrolmentAllUsers() {
		$course_id         = $this->getSimpleCourse();
		$other_user_ids    = $this->factory()->user->create_many( 2 );
		$enrolled_user_ids = $this->factory()->user->create_many( 3 );
		$all_user_ids      = array_merge( $enrolled_user_ids, $other_user_ids );

		foreach ( $other_user_ids as $user_id ) {
			$this->turnStudentIntoCrook( $user_id );
		}

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Denies_Crooks::class );
		$this->prepareEnrolmentManager();
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$course_salt = $course_enrolment->get_course_enrolment_salt();

		// First, ensure we've calculated all users.
		foreach ( $all_user_ids as $user_id ) {
			$course_enrolment->is_enrolled( $user_id );
		}

		$job = $course_enrolment->recalculate_enrolment( false );
		wp_cache_flush();

		foreach ( $all_user_ids as $user_id ) {
			$this->assertEquals( '', get_user_meta( $user_id, $course_enrolment->get_enrolment_results_meta_key(), true ), 'All users should have an enrolment result invalidated' );
		}
		$this->assertNotEquals( $course_enrolment->get_course_enrolment_salt(), $course_salt, 'The course salt should have been reset' );

		$this->assertTrue( $job instanceof Sensei_Enrolment_Course_Calculation_Job, 'Returned job should be an instance of Sensei_Enrolment_Course_Calculation_Job' );
		$this->assertNotEmpty( wp_next_scheduled( Sensei_Enrolment_Course_Calculation_Job::get_name(), [ $job->get_args() ] ), 'Job should have been scheduled' );
	}

	/**
	 * Tests retrieval of enrolled users IDs.
	 */
	public function testGetEnrolledUserIds() {
		$course_id         = $this->getSimpleCourse();
		$other_user_ids    = $this->factory()->user->create_many( 2 );
		$enrolled_user_ids = $this->factory()->user->create_many( 3 );
		$all_user_ids      = array_merge( $enrolled_user_ids, $other_user_ids );

		foreach ( $other_user_ids as $user_id ) {
			$this->turnStudentIntoCrook( $user_id );
		}

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Denies_Crooks::class );
		$this->prepareEnrolmentManager();

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$this->assertEquals( [], $course_enrolment->get_enrolled_user_ids(), 'Before we calculate enrolment, no one should be marked as enrolled in course' );

		// First, ensure we've calculated all users.
		foreach ( $all_user_ids as $user_id ) {
			$course_enrolment->is_enrolled( $user_id );
		}

		$this->assertEquals( $enrolled_user_ids, $course_enrolment->get_enrolled_user_ids(), 'We should only get the IDs of students who should be enrolled' );
	}

	/**
	 * Helper for `\Sensei_Class_Course_Enrolment_Test::testEnrolmentCheckVersionCachingWorks`.
	 */
	private function resetAndSetUpVersionedProvider( $bump_version ) {
		self::resetEnrolmentProviders();
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Version_Morph::class );
		$this->prepareEnrolmentManager();

		if ( $bump_version ) {
			Sensei_Test_Enrolment_Provider_Version_Morph::$version++;
		}
	}
}

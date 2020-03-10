<?php

class Sensei_Class_Student_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Test_Helpers;
	use Sensei_Course_Enrolment_Manual_Test_Helpers;

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
	 * Tests to make sure menu order is used when course order has been set.
	 */
	public function testQueryArgsUseMenuOrderWhenCourseOrderSet() {
		$learner_manager = Sensei_Learner::instance();

		$args_without_order = $learner_manager->get_enrolled_courses_query_args( 0 );
		$this->assertEquals( 'date', $args_without_order['orderby'], 'Initially we should order by date' );

		// Simulate us setting the course order. Normally this would also set the menu_order for all courses.
		update_option( 'sensei_course_order', '1,2,3' );

		$args_with_order = $learner_manager->get_enrolled_courses_query_args( 0 );
		$this->assertEquals( 'menu_order', $args_with_order['orderby'], 'Initially we should order by date' );
	}

	/**
	 * Tests to make sure only enrolled courses show up with query.
	 */
	public function testGetEnrolledCoursesQuerySimple() {
		$enrolled_course_ids = $this->factory->course->create_many( 2 );
		$other_course_ids    = $this->factory->course->create_many( 1 );
		$student_id          = $this->createStandardStudent();
		$learner_manager     = Sensei_Learner::instance();

		$this->prepareEnrolmentManager();

		$manual_provider = Sensei_Course_Enrolment_Manager::instance()->get_manual_enrolment_provider();

		foreach ( $enrolled_course_ids as $course_id ) {
			$manual_provider->enrol_student( $student_id, $course_id );
		}

		$base_query_args        = [
			'posts_per_page' => -1,
		];
		$query_enrolled_courses = $learner_manager->get_enrolled_courses_query( $student_id, $base_query_args );

		$this->assertTrue( $query_enrolled_courses instanceof WP_Query, 'Returned value should be instance of WP_Query' );
		$this->assertEquals( count( $enrolled_course_ids ), $query_enrolled_courses->found_posts, 'Number of found posts should match number of enrolled courses' );

		foreach ( $query_enrolled_courses->posts as $check_post ) {
			$this->assertTrue( in_array( $check_post->ID, $enrolled_course_ids, true ), 'Enrolled course should be included in results' );
		}
	}

	/**
	 * Tests to make sure only active enrolled courses show up with query.
	 */
	public function testGetEnrolledActiveCoursesQuerySimple() {
		$enrolled_course_ids     = $this->factory->course->create_many( 2 );
		$other_course_ids        = $this->factory->course->create_many( 1 );
		$completed_course_ids    = [ $enrolled_course_ids[0] ];
		$enrolled_and_active_ids = [ $enrolled_course_ids[1] ];
		$not_included_ids        = [ $enrolled_course_ids[0], $other_course_ids[0] ];
		$student_id              = $this->createStandardStudent();
		$learner_manager         = Sensei_Learner::instance();

		$this->prepareEnrolmentManager();

		$manual_provider = Sensei_Course_Enrolment_Manager::instance()->get_manual_enrolment_provider();

		foreach ( $enrolled_course_ids as $course_id ) {
			$manual_provider->enrol_student( $student_id, $course_id );
		}

		foreach ( $completed_course_ids as $course_id ) {
			Sensei_Utils::user_complete_course( $course_id, $student_id );
		}

		$base_query_args        = [
			'posts_per_page' => -1,
		];
		$query_enrolled_courses = $learner_manager->get_enrolled_active_courses_query( $student_id, $base_query_args );

		$this->assertTrue( $query_enrolled_courses instanceof WP_Query, 'Returned value should be instance of WP_Query' );
		$this->assertEquals( count( $enrolled_and_active_ids ), $query_enrolled_courses->found_posts, 'Number of found posts should match number of enrolled and active courses' );

		foreach ( $query_enrolled_courses->posts as $check_post ) {
			$this->assertTrue( in_array( $check_post->ID, $enrolled_and_active_ids, true ), 'Enrolled and incomplete courses should be included in results' );
		}

		foreach ( $query_enrolled_courses->posts as $check_post ) {
			$this->assertFalse( in_array( $check_post->ID, $not_included_ids, true ), 'Courses either not enrolled in or already completed should not be included in results' );
		}
	}

	/**
	 * Tests to make sure only completed enrolled courses show up with query.
	 */
	public function testGetEnrolledCompletedCoursesQuerySimple() {
		$enrolled_course_ids        = $this->factory->course->create_many( 2 );
		$other_course_ids           = $this->factory->course->create_many( 1 );
		$completed_course_ids       = [ $enrolled_course_ids[0], $other_course_ids[0] ];
		$enrolled_and_completed_ids = [ $enrolled_course_ids[0] ];
		$not_included_ids           = [ $enrolled_course_ids[1], $other_course_ids[0] ];
		$student_id                 = $this->createStandardStudent();
		$learner_manager            = Sensei_Learner::instance();

		$this->prepareEnrolmentManager();

		$manual_provider = Sensei_Course_Enrolment_Manager::instance()->get_manual_enrolment_provider();

		foreach ( $enrolled_course_ids as $course_id ) {
			$manual_provider->enrol_student( $student_id, $course_id );
		}

		foreach ( $completed_course_ids as $course_id ) {
			Sensei_Utils::user_complete_course( $course_id, $student_id );
		}

		$base_query_args        = [
			'posts_per_page' => -1,
		];
		$query_enrolled_courses = $learner_manager->get_enrolled_completed_courses_query( $student_id, $base_query_args );

		$this->assertTrue( $query_enrolled_courses instanceof WP_Query, 'Returned value should be instance of WP_Query' );
		$this->assertEquals( count( $enrolled_and_completed_ids ), $query_enrolled_courses->found_posts, 'Number of found posts should match number of enrolled and completed courses' );

		foreach ( $query_enrolled_courses->posts as $check_post ) {
			$this->assertTrue( in_array( $check_post->ID, $enrolled_and_completed_ids, true ), 'Enrolled and completed courses should be included in results' );
		}

		foreach ( $query_enrolled_courses->posts as $check_post ) {
			$this->assertFalse( in_array( $check_post->ID, $not_included_ids, true ), 'Courses either not enrolled in or still active should not be included in results' );
		}
	}

	/**
	 * Tests to make sure users that have stale enrolment results are calculated before querying.
	 */
	public function testGetEnrolledCoursesQueryStaleIncluded() {
		$enrolled_course_ids = $this->factory->course->create_many( 2 );
		$other_course_ids    = $this->factory->course->create_many( 1 );
		$student_id          = $this->createStandardStudent();
		$learner_manager     = Sensei_Learner::instance();

		$this->prepareEnrolmentManager();

		foreach ( $enrolled_course_ids as $course_id ) {
			$this->directlyEnrolStudent( $student_id, $course_id );
		}

		$user_meta = get_user_meta( $student_id, Sensei_Course_Enrolment_Manager::LEARNER_CALCULATION_META_NAME, true );
		$this->assertEmpty( $user_meta, 'Learner should not be calculated prior to querying courses' );

		// This should run
		$base_query_args        = [
			'posts_per_page' => -1,
		];
		$query_enrolled_courses = $learner_manager->get_enrolled_courses_query( $student_id, $base_query_args );

		$user_meta = get_user_meta( $student_id, Sensei_Course_Enrolment_Manager::LEARNER_CALCULATION_META_NAME, true );
		$this->assertEquals( Sensei_Course_Enrolment_Manager::instance()->get_enrolment_calculation_version(), $user_meta, 'Learner should have been calculated prior to querying courses' );

		$this->assertTrue( $query_enrolled_courses instanceof WP_Query, 'Returned value should be instance of WP_Query' );
		$this->assertEquals( count( $enrolled_course_ids ), $query_enrolled_courses->found_posts, 'Number of found posts should match number of enrolled courses' );

		foreach ( $query_enrolled_courses->posts as $check_post ) {
			$this->assertTrue( in_array( $check_post->ID, $enrolled_course_ids, true ), 'Enrolled course should be included in results' );
		}
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

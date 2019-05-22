<?php

class Sensei_Class_Course_Test extends WP_UnitTestCase {

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
	public function setup() {
		parent::setup();

		$this->factory = new Sensei_Factory();
		Sensei_Test_Events::reset();
	}//end setup()

	public function tearDown() {
		parent::tearDown();
		$this->factory->tearDown();
	}

	/**
	 * Testing the quiz class to make sure it is loaded
	 *
	 * @since 1.8.0
	 */
	public function testClassInstance() {

		// test if the class exists
		$this->assertTrue( class_exists( 'WooThemes_Sensei_Course' ), 'Sensei course class does not exist' );

		// test if the global sensei quiz class is loaded
		$this->assertTrue( isset( Sensei()->course ), 'Sensei Course class is not loaded' );

	} // end testClassInstance

	/**
	 * This tests Sensei_Courses::get_all_course
	 *
	 * @since 1.8.0
	 */
	public function testGetAllCourses() {
		// check if the function is there
		$this->assertTrue( method_exists( 'WooThemes_Sensei_Course', 'get_all_courses' ), 'The course class get_all_courses function does not exist.' );

		// setup the assertion
		$retrieved_courses = get_posts(
			array(
				'post_type'      => 'course',
				'posts_per_page' => 10000,
			)
		);

		// make sure the same course were retrieved as what we just created
		$this->assertEquals(
			count( $retrieved_courses ),
			count( WooThemes_Sensei_Course::get_all_courses() ),
			'The number of course returned is not equal to what is actually available'
		);

	}//end testGetAllCourses()

	/**
	 *
	 * This tests Sensei_Courses::get_completed_lesson_ids
	 *
	 * @since 1.8.0
	 */
	public function testGetCompletedLessonIds() {

		// does the function exist?
		$this->assertTrue( method_exists( 'WooThemes_Sensei_Course', 'get_completed_lesson_ids' ), 'The course class get_completed_lesson_ids function does not exist.' );

		// setup the test
		$test_user_id   = wp_create_user( 'getCompletedLessonIds', 'getCompletedLessonIds', 'getCompletedLessonIds@tes.co' );
		$test_lessons   = $this->factory->get_lessons();
		$test_course_id = $this->factory->get_random_course_id();
		remove_all_actions( 'sensei_user_course_start' );
		WooThemes_Sensei_Utils::user_start_course( $test_user_id, $test_course_id );

		// add lessons to the course
		foreach ( $test_lessons as $lesson_id ) {
			add_post_meta( $lesson_id, '_lesson_course', intval( $test_course_id ) );
		}

		// complete 3 lessons
		$i = 0;
		for ( $i = 0; $i < 3; $i++ ) {
			WooThemes_Sensei_Utils::update_lesson_status( $test_user_id, $test_lessons[ $i ], 'complete' );
		}

		$this->assertEquals( 3, count( Sensei()->course->get_completed_lesson_ids( $test_course_id, $test_user_id ) ), 'Course completed lesson count not accurate' );

		// complete all lessons
		foreach ( $test_lessons as $lesson_id ) {
			WooThemes_Sensei_Utils::update_lesson_status( $test_user_id, $lesson_id, 'complete' );
		}

		// does it return all lessons
		$this->assertEquals( count( $test_lessons ), count( Sensei()->course->get_completed_lesson_ids( $test_course_id, $test_user_id ) ), 'Course completed lesson count not accurate' );

	}//end testGetCompletedLessonIds()

	/**
	 * This tests Sensei_Courses::get_completion_percentage
	 *
	 * @since 1.8.0
	 */
	public function testGetCompletionPercentage() {
		// does the function exist?
		$this->assertTrue( method_exists( 'WooThemes_Sensei_Course', 'get_completion_percentage' ), 'The course class get_completion_percentage function does not exist.' );

		// setup the test
		$test_user_id   = wp_create_user( 'testGetCompletionPercentage', 'testGetCompletionPercentage', 'testGetCompletionPercentage@tes.co' );
		$test_lessons   = $this->factory->get_lessons();
		$test_course_id = $this->factory->get_random_course_id();
		remove_all_actions( 'sensei_user_course_start' );
		WooThemes_Sensei_Utils::user_start_course( $test_user_id, $test_course_id );

		// add lessons to the course
		foreach ( $test_lessons as $lesson_id ) {
			add_post_meta( $lesson_id, '_lesson_course', intval( $test_course_id ) );
		}

		// complete 3 lessons and check if the correct percentage returns
		$i = 0;
		for ( $i = 0; $i < 3; $i++ ) {
			WooThemes_Sensei_Utils::update_lesson_status( $test_user_id, $test_lessons[ $i ], 'complete' );
		}
		$expected_percentage = round( 3 / count( $test_lessons ) * 100, 2 );
		$this->assertEquals( $expected_percentage, Sensei()->course->get_completion_percentage( $test_course_id, $test_user_id ), 'Course completed percentage is not accurate' );

		// complete all lessons
		foreach ( $test_lessons as $lesson_id ) {
			WooThemes_Sensei_Utils::update_lesson_status( $test_user_id, $lesson_id, 'complete' );
		}
		// all lessons should no be completed
		$this->assertEquals( 100, Sensei()->course->get_completion_percentage( $test_course_id, $test_user_id ), 'Course completed percentage is not accurate' );

	}

	/**
	 * @covers Sensei_Course::log_initial_publish_event
	 */
	public function testLogEventOnFirstPublish() {
		$this->factory->course->create();

		$events = Sensei_Test_Events::get_logged_events( 'sensei_course_publish' );
		$this->assertCount( 1, $events );
	}

	/**
	 * @covers Sensei_Course::log_initial_publish_event
	 */
	public function testLogNoEventOnSecondPublish() {
		$course_id = $this->factory->course->create();

		// Unpublish course.
		wp_update_post( [
			'ID'          => $course_id,
			'post_status' => 'draft',
		] );

		// Reset test logger and republish course.
		Sensei_Test_Events::reset();
		wp_update_post( [
			'ID'          => $course_id,
			'post_status' => 'publish',
		] );

		// Ensure that the second publish did not log an event.
		$events = Sensei_Test_Events::get_logged_events();
		$this->assertCount( 0, $events );
	}

	/**
	 * @covers Sensei_Course::log_initial_publish_event
	 */
	public function testLogNoEventOnExistingCourseSecondPublish() {
		$course_id = $this->factory->course->create();

		// Remove the meta to simulate an existing course.
		delete_post_meta( $course_id, 'course_already_published' );

		// Unpublish course.
		wp_update_post( [
			'ID'          => $course_id,
			'post_status' => 'draft',
		] );

		// Reset test logger and republish course.
		Sensei_Test_Events::reset();
		wp_update_post( [
			'ID'          => $course_id,
			'post_status' => 'publish',
		] );

		// Ensure that the second publish did not log an event.
		$events = Sensei_Test_Events::get_logged_events();
		$this->assertCount( 0, $events );
	}

	/**
	 * @covers Sensei_Course::log_initial_publish_event
	 */
	public function testLogNoEventOnExistingCourseUpdate() {
		$course_id = $this->factory->course->create();

		// Remove the meta to simulate an existing published course.
		delete_post_meta( $course_id, 'course_already_published' );

		// Reset test logger and update course without changing the status.
		Sensei_Test_Events::reset();
		wp_update_post( [
			'ID'           => $course_id,
			'post_content' => 'New content',
			'post_status'  => 'publish',
		] );

		// Ensure that the second publish did not log an event.
		$events = Sensei_Test_Events::get_logged_events();
		$this->assertCount( 0, $events );
	}

	/**
	 * @covers Sensei_Course::log_initial_publish_event
	 */
	public function testLogEventModuleCount() {
		$course_id = $this->factory->course->create( [
			'post_status' => 'draft',
		] );

		// Add some modules.
		wp_set_object_terms( $course_id, [ 'module-a', 'module-b' ], 'module' );

		// Publish course.
		wp_update_post( [
			'ID'          => $course_id,
			'post_status' => 'publish',
		] );

		$events = Sensei_Test_Events::get_logged_events( 'sensei_course_publish' );
		$this->assertCount( 1, $events );

		// Ensure module count is correct.
		$event = $events[0];
		$this->assertEquals( 2, $event['url_args']['module_count'] );
	}

	/**
	 * @covers Sensei_Course::log_initial_publish_event
	 */
	public function testLogEventLessonCount() {
		$course_id = $this->factory->course->create( [
			'post_status' => 'draft',
		] );

		// Add some lessons to the course.
		$lesson_ids = $this->factory->lesson->create_many( 2 );
		foreach ( $lesson_ids as $lesson_id ) {
			add_post_meta( $lesson_id, '_lesson_course', $course_id );
		}

		// Publish course.
		wp_update_post( [
			'ID'          => $course_id,
			'post_status' => 'publish',
		] );

		$events = Sensei_Test_Events::get_logged_events( 'sensei_course_publish' );
		$this->assertCount( 1, $events );

		// Ensure lesson count is correct.
		$event = $events[0];
		$this->assertEquals( 2, $event['url_args']['lesson_count'] );
	}

	/**
	 * @covers Sensei_Course::log_initial_publish_event
	 */
	public function testLogEventProductId() {
		$course_id = $this->factory->course->create( [
			'post_status' => 'draft',
		] );

		// Add product ID.
		add_post_meta( $course_id, '_course_woocommerce_product', 5 );

		// Publish without product ID.
		wp_update_post( [
			'ID'          => $course_id,
			'post_status' => 'publish',
		] );

		$events = Sensei_Test_Events::get_logged_events( 'sensei_course_publish' );
		$this->assertCount( 1, $events );

		// Ensure product ID is correct.
		$event = $events[0];
		$this->assertEquals( 5, $event['url_args']['product_id'] );
	}

	/**
	 * @covers Sensei_Course::log_initial_publish_event
	 */
	public function testLogNoEventProductId() {
		$course_id = $this->factory->course->create( [
			'post_status' => 'draft',
		] );

		// Publish without product ID.
		wp_update_post( [
			'ID'          => $course_id,
			'post_status' => 'publish',
		] );

		$events = Sensei_Test_Events::get_logged_events( 'sensei_course_publish' );
		$this->assertCount( 1, $events );

		// Ensure product ID is correct.
		$event = $events[0];
		$this->assertEquals( -1, $event['url_args']['product_id'] );
	}

}//end class

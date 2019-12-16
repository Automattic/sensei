<?php

class Sensei_Class_Admin_Test extends WP_UnitTestCase {

	/**
	 * Constructor function
	 */
	public function __construct() { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		parent::__construct();
	}

	/**
	 * Setup function
	 *
	 * This function sets up the lessons, quizes and their questions. This function runs before
	 * every single test in this class
	 */
	public function setup() {
		parent::setup();

		$this->factory = new Sensei_Factory();
	}//end setup()

	public function tearDown() {
		parent::tearDown();
		$this->factory->tearDown();
	}

	/**
	 * Testing the admin class to make sure it is loaded
	 */
	public function testClassInstance() {
		// test if the class exists
		$this->assertTrue( class_exists( 'WooThemes_Sensei_Admin' ), 'Sensei Admin class does not exist' );
	} // end testClassInstance

	/**
	 * Test duplicate courses with lessons.
	 *
	 * @covers WooThemes_Sensei_Admin::duplicate_course_with_lessons_action
	 */
	public function testDuplicateCourseWithLessons() {
		$qty_lessons = 2;

		$this->assertTrue(
			method_exists( 'WooThemes_Sensei_Admin', 'duplicate_course_with_lessons_action' ),
			'The admin class function `duplicate_course_with_lessons_action` does not exist '
		);

		// Mock the safe_redirect method
		Sensei()->admin = $this->getMockBuilder( 'WooThemes_Sensei_Admin' )
			->setMethods( [ 'safe_redirect' ] )
			->getMock();

		Sensei()->admin->expects( $this->once() )
			->method( 'safe_redirect' );

		// Create and set current user as teacher
		$admin_user = $this->factory->user->create_and_get(
			array(
				'user_login' => 'admin_user',
				'user_pass'  => null,
				'role'       => 'teacher',
			)
		);
		wp_set_current_user( $admin_user->ID );

		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many( $qty_lessons );
		foreach ( $lesson_ids as $lesson_id ) {
			add_post_meta( $lesson_id, '_lesson_course', $course_id );
		}

		// Add the first lesson as prerequisite of the second one
		add_post_meta( $lesson_ids[1], '_lesson_prerequisite', $lesson_ids[0] );

		// Create nonce to pass in the method
		$duplicate_nonce = wp_create_nonce( 'duplicate_course_with_lessons_' . $course_id );

		// Set request and get params
		$_REQUEST['_wpnonce'] = $duplicate_nonce;
		$_GET['post']         = $course_id;

		// Runs the duplication
		Sensei()->admin->duplicate_course_with_lessons_action();

		// Get duplicated course (all in draft)
		$course_args   = array(
			'post_type'   => 'course',
			'post_status' => [ 'draft' ],
		);
		$courses       = get_posts( $course_args );
		$new_course_id = $courses[0]->ID;

		// Get the lessons of the duplicated post
		$lesson_args = array(
			'post_type'   => 'lesson',
			'meta_key'    => '_lesson_course', // phpcs:ignore Slow query ok.
			'meta_value'  => $new_course_id, // phpcs:ignore Slow query ok.
			'post_status' => [ 'draft' ],
		);
		$lessons     = get_posts( $lesson_args );

		// Lessons assertation
		$this->assertCount( $qty_lessons, $lessons, 'The number of lessons duplicated should be the same that the original.' );

		// Prerequisite assertation
		$first_lesson_prerequisite  = get_post_meta( $lessons[0]->ID, '_lesson_prerequisite', true );
		$second_lesson_prerequisite = get_post_meta( $lessons[1]->ID, '_lesson_prerequisite', true );
		$this->assertEquals( $first_lesson_prerequisite, '', 'The first lesson prerequisite should be empty.' );
		$this->assertEquals( $second_lesson_prerequisite, $lessons[0]->ID, 'The second lesson prerequisite should be the first duplicated lesson.' );
	}

}//end class

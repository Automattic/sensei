<?php

class Sensei_Class_Admin_Test extends WP_UnitTestCase {

	/**
	 * Constructor function
	 */
	public function __construct() {
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
		$admin_user = $this->factory->user->create_and_get( array( 'user_login' => 'admin_user', 'user_pass' => null, 'role' => 'teacher' ) );
		wp_set_current_user( $admin_user->ID );

		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many( $qty_lessons );
		foreach ( $lesson_ids as $lesson_id ) {
			add_post_meta( $lesson_id, '_lesson_course', $course_id );
		}

		// Add prerequisite
		add_post_meta( $lesson_ids[1], '_lesson_prerequisite', $lesson_ids[0] );

		// Create nonce to pass in the method
		$duplicate_nonce = wp_create_nonce( 'duplicate_course_with_lessons_' . $course_id );

		// Set request and get params
		$_REQUEST['_wpnonce'] = $duplicate_nonce;
		$_GET['post']         = $course_id;

		Sensei()->admin->duplicate_course_with_lessons_action();

		$course_args   = array(
			'post_type'   => 'course',
			'post_status' => [ 'draft' ],
		);
		$courses       = get_posts( $course_args );
		$new_course_id = $courses[0]->ID;

		$lesson_args = array(
			'post_type'   => 'lesson',
			'meta_key'    => '_lesson_course',
			'meta_value'  => $new_course_id,
			'post_status' => [ 'draft' ],
		);
		$lessons     = get_posts( $lesson_args );

		$this->assertCount( $qty_lessons, $lessons, 'The number of lessons duplicated should be the same that the original.' );

		$new_prerequisite = get_post_meta( $lessons[1]->ID, '_lesson_prerequisite', true );
		$this->assertEquals( $lessons[0]->ID, $new_prerequisite, 'The prerequisite post meta should update after the duplication.' );
	}

}//end class

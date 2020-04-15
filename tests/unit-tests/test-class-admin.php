<?php

class Sensei_Class_Admin_Test extends WP_UnitTestCase {

	/**
	 * Setup function.
	 *
	 * This function sets up the lessons, quizes and their questions.
	 * This function runs before every single test in this class.
	 *
	 * @return void
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
	 * Testing the admin class to make sure it is loaded.
	 *
	 * @return void
	 */
	public function testClassInstance() {
		// test if the class exists
		$this->assertTrue( class_exists( 'WooThemes_Sensei_Admin' ), 'Sensei Admin class does not exist' );
	} // end testClassInstance

	/**
	 * Test duplicate courses with lessons.
	 *
	 * @uses WooThemes_Sensei_Admin::duplicate_course_with_lessons_action
	 * @uses WooThemes_Sensei_Admin::duplicate_content
	 * @uses WooThemes_Sensei_Admin::duplicate_post
	 * @uses WooThemes_Sensei_Admin::update_lesson_prerequisite_ids
	 * @uses WooThemes_Sensei_Admin::get_prerequisite_update_object
	 * @uses WooThemes_Sensei_Admin::duplicate_lesson_quizzes
	 *
	 * @covers WooThemes_Sensei_Admin::duplicate_course_lessons
	 *
	 * @return void
	 */
	public function testDuplicateCourseWithLessons() {

		$qty_lessons = 2;
		$duplication = $this->duplicate_course_with_lessons_setup( $qty_lessons );
		$course_id   = $duplication['course_id'];
		$lessons_ids = $duplication['lessons_ids'];

		// Runs the duplication
		Sensei()->admin->duplicate_course_with_lessons_action();

		// Get the duplicated course
		$duplicated_course_args = array(
			'post_type'   => 'course',
			'meta_key'    => '_duplicate', // phpcs:ignore Slow query ok.
			'meta_value'  => $course_id, // phpcs:ignore Slow query ok.
			'post_status' => [ 'draft' ],
		);
		$duplicated_courses     = get_posts( $duplicated_course_args );
		$duplicated_course_id   = $duplicated_courses[0]->ID;

		// Get duplicated lessons
		$duplicated_lesson_args = array(
			'post_type'   => 'lesson',
			'meta_key'    => '_lesson_course', // phpcs:ignore Slow query ok.
			'meta_value'  => $duplicated_course_id, // phpcs:ignore Slow query ok.
			'post_status' => [ 'draft' ],
		);
		$duplicated_lessons     = get_posts( $duplicated_lesson_args );

		// Lessons assertion
		$this->assertCount( $qty_lessons, $duplicated_lessons, 'The number of duplicated lessons should be the same as the original.' );
	}

	/**
	 * Test duplicate courses with lessons with prerequisite.
	 *
	 * @uses WooThemes_Sensei_Admin::duplicate_course_with_lessons_action
	 * @uses WooThemes_Sensei_Admin::duplicate_content
	 * @uses WooThemes_Sensei_Admin::duplicate_course_lessons
	 * @uses WooThemes_Sensei_Admin::duplicate_post
	 * @uses WooThemes_Sensei_Admin::duplicate_lesson_quizzes
	 *
	 * @covers WooThemes_Sensei_Admin::update_lesson_prerequisite_ids
	 * @covers WooThemes_Sensei_Admin::get_prerequisite_update_object
	 *
	 * @return void
	 */
	public function testDuplicateCourseWithLessonsWithPrerequisite() {
		$qty_lessons = 2;
		$duplication = $this->duplicate_course_with_lessons_setup( $qty_lessons );
		$course_id   = $duplication['course_id'];
		$lessons_ids = $duplication['lessons_ids'];

		// Add the first lesson as prerequisite of the second one
		add_post_meta( $lessons_ids[1], '_lesson_prerequisite', $lessons_ids[0] );

		// Runs the duplication
		Sensei()->admin->duplicate_course_with_lessons_action();

		// Get the duplicated prerequisite lesson
		$duplicated_prerequisite_lesson_args = array(
			'post_type'   => 'lesson',
			'meta_key'    => '_duplicate', // phpcs:ignore Slow query ok.
			'meta_value'  => $lessons_ids[0], // phpcs:ignore Slow query ok.
			'post_status' => [ 'draft' ],
		);
		$duplicated_prerequisite_lesson      = get_posts( $duplicated_prerequisite_lesson_args );
		$duplicated_prerequisite_lesson_id   = $duplicated_prerequisite_lesson[0]->ID;

		// Get the duplicated dependent lesson
		$duplicated_dependent_lesson_args = array(
			'post_type'   => 'lesson',
			'meta_key'    => '_duplicate', // phpcs:ignore Slow query ok.
			'meta_value'  => $lessons_ids[1], // phpcs:ignore Slow query ok.
			'post_status' => [ 'draft' ],
		);
		$duplicated_dependent_lesson      = get_posts( $duplicated_dependent_lesson_args );
		$duplicated_dependent_lesson_id   = $duplicated_dependent_lesson[0]->ID;

		// Get the prerequisite id
		$new_prerequisite_id = get_post_meta( $duplicated_dependent_lesson_id, '_lesson_prerequisite', true );

		// Prerequisite assertion
		$this->assertEquals( $duplicated_prerequisite_lesson_id, $new_prerequisite_id, 'The duplicated dependent should have the duplicated prerequisite as its prerequisite.' );
	}

	/**
	 * Test duplicate courses with lessons, ensure lesson order is preserved.
	 *
	 * @uses WooThemes_Sensei_Admin::duplicate_course_with_lessons_action
	 * @uses WooThemes_Sensei_Admin::duplicate_content
	 * @uses WooThemes_Sensei_Admin::duplicate_post
	 *
	 * @covers WooThemes_Sensei_Admin::duplicate_course_lessons
	 *
	 * @return void
	 */
	public function testDuplicateCourseWithLessonsPreservesOrder() {
		$qty_lessons = 2;
		$original    = $this->duplicate_course_with_lessons_setup( $qty_lessons );
		$course_id   = $original['course_id'];
		$lessons_ids = $original['lessons_ids'];
		$lesson1_id  = $lessons_ids[0];
		$lesson2_id  = $lessons_ids[1];

		$lesson_order = join( ',', [ $lesson1_id, $lesson2_id ] );
		Sensei()->admin->save_lesson_order( $lesson_order, $course_id );

		// Runs the duplication
		Sensei()->admin->duplicate_course_with_lessons_action();

		// Get the duplicated course and lessons.
		$duplicated_course  = $this->get_duplicated_course( $course_id );
		$duplicated_lesson1 = $this->get_duplicated_lesson( $lesson1_id );
		$duplicated_lesson2 = $this->get_duplicated_lesson( $lesson2_id );

		// Assert course order is correct.
		$this->assertEquals(
			join( ',', [ $duplicated_lesson1->ID, $duplicated_lesson2->ID ] ),
			get_post_meta( $duplicated_course->ID, '_lesson_order', true ),
			'Duplicated lesson order in course meta differs from the original order'
		);

		// Assert order is correct on each lesson.
		$this->assertEquals(
			'1',
			get_post_meta( $duplicated_lesson1->ID, "_order_$duplicated_course->ID", true ),
			'Duplicated lesson order on first lesson differs from the original order'
		);
		$this->assertEquals(
			'2',
			get_post_meta( $duplicated_lesson2->ID, "_order_$duplicated_course->ID", true ),
			'Duplicated lesson order on second lesson differs from the original order'
		);
	}

	/**
	 * Setup function for duplicate course.
	 *
	 * This function mocks the redirect method, create and set the user, create a course
	 * with some lessons and set the needed params.
	 *
	 * @param $qty_lessons How many lessons to create.
	 * @return array       Created course and list of lessons id.
	 */
	private function duplicate_course_with_lessons_setup( $qty_lessons ) {
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

		// Create one course and add some lessons
		$course_id   = $this->factory->course->create();
		$lessons_ids = $this->factory->lesson->create_many( $qty_lessons );

		foreach ( $lessons_ids as $lesson_id ) {
			add_post_meta( $lesson_id, '_lesson_course', $course_id );
		}

		// Create nonce
		$duplicate_nonce = wp_create_nonce( 'duplicate_course_with_lessons_' . $course_id );

		// Set request and get params
		$_REQUEST['_wpnonce'] = $duplicate_nonce;
		$_GET['post']         = $course_id;

		return array(
			'course_id'   => $course_id,
			'lessons_ids' => $lessons_ids,
		);
	}

	/**
	 * Get duplicated course given the original course ID.
	 *
	 * @param int $course_id The ID of the original course.
	 * @return WP_Post
	 */
	private function get_duplicated_course( $course_id ) {
		$duplicated_course_args = array(
			'post_type'   => 'course',
			'meta_key'    => '_duplicate', // phpcs:ignore Slow query ok.
			'meta_value'  => $course_id, // phpcs:ignore Slow query ok.
			'post_status' => [ 'draft' ],
			'per_page'    => 1,
		);
		return get_posts( $duplicated_course_args )[0];
	}

	/**
	 * Get duplicated lesson given the original lesson ID.
	 *
	 * @param int $lesson_id The ID of the original lesson.
	 * @return WP_Post
	 */
	private function get_duplicated_lesson( $lesson_id ) {
		$duplicated_lesson_args = array(
			'post_type'   => 'lesson',
			'meta_key'    => '_duplicate', // phpcs:ignore Slow query ok.
			'meta_value'  => $lesson_id, // phpcs:ignore Slow query ok.
			'post_status' => [ 'draft' ],
			'per_page'    => 1,
		);
		return get_posts( $duplicated_lesson_args )[0];
	}
}//end class

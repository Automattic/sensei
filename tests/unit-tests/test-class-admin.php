<?php

/**
 * Tests for Sensei_Admin class.
 *
 * @covers Sensei_Admin
 */
class Sensei_Class_Admin_Test extends WP_UnitTestCase {

	/**
	 * Setup function.
	 *
	 * This function sets up the lessons, quizes and their questions.
	 * This function runs before every single test in this class.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	public function tearDown(): void {
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
	}

	/**
	 * Test duplicate courses with lessons.
	 */
	public function testDuplicateCourseWithLessons() {

		$qty_lessons = 2;
		$duplication = $this->duplicate_course_with_lessons_setup( $qty_lessons );
		$course_id   = $duplication['course_id'];
		$lessons_ids = $duplication['lessons_ids'];

		// Make one of the lessons draft, it should also get duplicated.
		wp_update_post(
			[
				'ID'          => $lessons_ids[0],
				'post_status' => 'draft',
			]
		);

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
	 * Ensure the lessons could be moved and unassigned from modules.
	 */
	public function testShouldMoveLessonsBetweenModulesAndUnassignModules() {
		/* Arrange. */
		$setup             = $this->setupSyncLessonOrderEnv();
		$sync_lesson_order = $setup['sync_lesson_order'];

		$course_id = $setup['course_id'];
		$lesson_1  = $setup['lessons'][0];
		$lesson_2  = $setup['lessons'][1];
		$module_1  = $setup['modules'][0];
		$module_2  = $setup['modules'][1];

		/* Act. */
		$lessons_to_sync = [
			$lesson_1 => [ 'module' => $module_1 ],
			$lesson_2 => [ 'module' => $module_2 ],
		];

		$sync_lesson_order( $lessons_to_sync, $course_id );

		/* Assert. */
		$this->assertTrue( has_term( $module_1, Sensei()->modules->taxonomy, $lesson_1 ) );
		$this->assertTrue( has_term( $module_2, Sensei()->modules->taxonomy, $lesson_2 ) );

		/* Act. */
		// Move lesson 2 to module 1.
		$lessons_to_sync = [
			$lesson_1 => [ 'module' => $module_1 ],
			$lesson_2 => [ 'module' => $module_1 ],
		];

		$sync_lesson_order( $lessons_to_sync, $course_id );

		/* Assert. */
		$this->assertTrue( has_term( $module_1, Sensei()->modules->taxonomy, $lesson_1 ) );
		$this->assertTrue( has_term( $module_1, Sensei()->modules->taxonomy, $lesson_2 ) );
		$this->assertFalse( has_term( $module_2, Sensei()->modules->taxonomy, $lesson_2 ) );

		/* Act. */
		// Unassign the module from lesson 2.
		$lessons_to_sync = [
			$lesson_1 => [ 'module' => $module_1 ],
			$lesson_2 => [ 'module' => null ],
		];

		$sync_lesson_order( $lessons_to_sync, $course_id );

		/* Assert. */
		$this->assertTrue( has_term( $module_1, Sensei()->modules->taxonomy, $lesson_1 ) );
		$this->assertFalse( has_term( $module_1, Sensei()->modules->taxonomy, $lesson_2 ) );
		$this->assertFalse( has_term( $module_2, Sensei()->modules->taxonomy, $lesson_2 ) );
	}

	/**
	 * Ensure the lessons order is updated.
	 */
	public function testShouldUpdateTheOrderOfLessons() {
		/* Arrange. */
		$setup             = $this->setupSyncLessonOrderEnv();
		$sync_lesson_order = $setup['sync_lesson_order'];

		$course_id = $setup['course_id'];
		$lesson_1  = $setup['lessons'][0];
		$lesson_2  = $setup['lessons'][1];
		$lesson_3  = $setup['lessons'][2];
		$module_1  = $setup['modules'][0];
		$module_2  = $setup['modules'][1];

		/* Act. */
		$lessons_to_sync = [
			$lesson_1 => [ 'module' => $module_1 ],
			$lesson_2 => [ 'module' => $module_2 ],
			$lesson_3 => [ 'module' => null ],
		];

		$sync_lesson_order( $lessons_to_sync, $course_id );

		/* Assert. */
		$this->assertEquals( '0', get_post_meta( $lesson_1, '_order_module_' . $module_1, true ) );
		$this->assertEquals( '0', get_post_meta( $lesson_2, '_order_module_' . $module_2, true ) );

		/* Act. */
		// Move the lesson 2 to module 1 and make it first.
		$lessons_to_sync = [
			$lesson_2 => [ 'module' => $module_1 ],
			$lesson_1 => [ 'module' => $module_1 ],
			$lesson_3 => [ 'module' => null ],
		];

		$sync_lesson_order( $lessons_to_sync, $course_id );

		/* Assert. */
		$this->assertEquals( '0', get_post_meta( $lesson_2, '_order_module_' . $module_1, true ) );
		$this->assertEquals( '1', get_post_meta( $lesson_1, '_order_module_' . $module_1, true ) );

		/* Act. */
		// Have 2 unassigned lessons.
		$lessons_to_sync = [
			$lesson_1 => [ 'module' => $module_1 ],
			$lesson_2 => [ 'module' => null ],
			$lesson_3 => [ 'module' => null ],
		];

		$sync_lesson_order( $lessons_to_sync, $course_id );

		/* Assert. */
		$no_module_lessons_order = explode( ',', get_post_meta( $course_id, '_lesson_order', true ) );
		$this->assertEquals( [ $lesson_2, $lesson_3 ], $no_module_lessons_order );

		/* Act. */
		// Reorder the unassigned lessons.
		$lessons_to_sync = [
			$lesson_1 => [ 'module' => $module_1 ],
			$lesson_3 => [ 'module' => null ],
			$lesson_2 => [ 'module' => null ],
		];

		$sync_lesson_order( $lessons_to_sync, $course_id );

		/* Assert. */
		$no_module_lessons_order = explode( ',', get_post_meta( $course_id, '_lesson_order', true ) );
		$this->assertEquals( [ $lesson_3, $lesson_2 ], $no_module_lessons_order );
	}

	/**
	 * Setup method for Sensei_Admin::sync_lesson_order.
	 *
	 * @return array
	 */
	private function setupSyncLessonOrderEnv() {
		$course_id = $this->factory->course->create( [ 'post_author' => 1 ] );
		$modules   = $this->factory->module->create_many( 2 );
		$lessons   = $this->factory->lesson->create_many(
			3,
			[
				'meta_input' => [
					'_lesson_course' => $course_id,
				],
			]
		);

		wp_set_object_terms( $course_id, $modules, Sensei()->modules->taxonomy );

		$admin  = new Sensei_Admin();
		$method = new ReflectionMethod( $admin, 'sync_lesson_order' );
		$method->setAccessible( true );

		return [
			'course_id'         => $course_id,
			'lessons'           => $lessons,
			'modules'           => $modules,
			'sync_lesson_order' => function( $lessons_to_sync, $course_id ) use ( $admin, $method ) {
				return $method->invoke( $admin, $lessons_to_sync, $course_id );
			},
		];
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
}

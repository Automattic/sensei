<?php
/**
 * This file contains the Sensei_Import_Associations_Tests class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Import_Associations class.
 *
 * @group data-port
 */
class Sensei_Import_Associations_Tests extends WP_UnitTestCase {

	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Test to make sure lesson modules are handled correctly.
	 */
	public function testHandleLessonModule() {
		$test_module_name = 'A Very Fancy Module';
		$term             = Sensei_Data_Port_Utilities::get_term( $test_module_name, 'module', 0 );
		$course_id        = $this->factory->course->create();
		$lesson_id        = $this->factory->lesson->create();

		wp_set_object_terms( $course_id, [ $term->term_id ], 'module' );
		add_post_meta( $lesson_id, '_lesson_course', $course_id );

		$job  = Sensei_Import_Job::create( 'test', 0 );
		$task = new Sensei_Import_Associations( $job );

		$method = new ReflectionMethod( $task, 'handle_lesson_module' );
		$method->setAccessible( true );

		$method->invokeArgs(
			$task,
			[
				$lesson_id,
				[ 'A Very Fancy Module', 1, 'Post title' ],
			]
		);

		$terms = wp_get_post_terms( $lesson_id, 'module' );
		$this->assertEquals( $test_module_name, $terms[0]->name );
	}

	/**
	 * Tests lesson association.
	 */
	public function testHandleCourseLessonsNormal() {
		$course_id  = $this->factory->course->create();
		$lessons    = $this->factory->lesson->create_many( 3 );
		$lesson_map = [
			'232'  => $lessons[1],
			'4'    => $lessons[2],
			'4255' => $lessons[0],
		];

		$job = Sensei_Import_Job::create( 'test', 0 );
		foreach ( $lesson_map as $original_id => $post_id ) {
			$job->set_import_id( Sensei_Data_Port_Lesson_Schema::POST_TYPE, $original_id, $post_id );
		}

		$task   = new Sensei_Import_Associations( $job );
		$method = new ReflectionMethod( $task, 'handle_course_lessons' );
		$method->setAccessible( true );

		$method->invokeArgs(
			$task,
			[
				$course_id,
				[ 'id:232,id:4,id:4255', 1, 'Post title' ],
			]
		);

		$this->assertEmpty( $job->get_logs(), 'No warnings should have been reported' );

		$order_index = 0;
		foreach ( $lesson_map as $original_id => $post_id ) {
			$this->assertEquals( $course_id, get_post_meta( $post_id, '_lesson_course', true ) );
			$this->assertEquals( $order_index, get_post_meta( $post_id, '_order_' . $course_id, true ) );

			$order_index++;
		}

		$expected_lesson_order = implode( ',', array_values( $lesson_map ) );
		$this->assertEquals( $expected_lesson_order, get_post_meta( $course_id, '_lesson_order', true ), 'Course lesson order should have been set' );
	}

	/**
	 * Tests lesson association when a missing lesson is included.
	 */
	public function testHandleCourseLessonsMissingLesson() {
		$course_id  = $this->factory->course->create();
		$lessons    = $this->factory->lesson->create_many( 2 );
		$lesson_map = [
			'232'  => $lessons[1],
			'4255' => $lessons[0],
		];

		$job = Sensei_Import_Job::create( 'test', 0 );
		foreach ( $lesson_map as $original_id => $post_id ) {
			$job->set_import_id( Sensei_Data_Port_Lesson_Schema::POST_TYPE, $original_id, $post_id );
		}

		$task   = new Sensei_Import_Associations( $job );
		$method = new ReflectionMethod( $task, 'handle_course_lessons' );
		$method->setAccessible( true );

		$method->invokeArgs(
			$task,
			[
				$course_id,
				[ 'id:232,id:4,id:4255', 1, 'Post title' ],
			]
		);

		$logs = $job->get_logs();
		$this->assertEquals( 1, count( $logs ), 'A warnings should have been reported about the missing lesson' );
		$this->assertEquals( 'Lesson does not exist: id:4.', $logs[0]['message'], 'Warning about missing lesson should have been added' );

		$order_index = 0;
		foreach ( $lesson_map as $original_id => $post_id ) {
			$this->assertEquals( $course_id, get_post_meta( $post_id, '_lesson_course', true ) );
			$this->assertEquals( $order_index, get_post_meta( $post_id, '_order_' . $course_id, true ) );

			$order_index++;
		}

		$expected_lesson_order = implode( ',', array_values( $lesson_map ) );
		$this->assertEquals( $expected_lesson_order, get_post_meta( $course_id, '_lesson_order', true ), 'Course lesson order should have been set' );
	}


	/**
	 * Tests lesson association when multiple courses include the same lesson.
	 */
	public function testHandleCourseLessonsLessonWithMultipleCourses() {
		$course_id_a = $this->factory->course->create();
		$course_id_b = $this->factory->course->create();
		$lessons     = $this->factory->lesson->create_many( 2 );
		$lesson_map  = [
			'232'  => $lessons[1],
			'4255' => $lessons[0],
		];

		$job = Sensei_Import_Job::create( 'test', 0 );
		foreach ( $lesson_map as $original_id => $post_id ) {
			$job->set_import_id( Sensei_Data_Port_Lesson_Schema::POST_TYPE, $original_id, $post_id );
		}

		$task   = new Sensei_Import_Associations( $job );
		$method = new ReflectionMethod( $task, 'handle_course_lessons' );
		$method->setAccessible( true );

		$method->invokeArgs(
			$task,
			[
				$course_id_a,
				[ 'id:232,id:4255', 1, 'Post title' ],
			]
		);

		$job->set_import_id( Sensei_Data_Port_Course_Schema::POST_TYPE, '123', $course_id_a );
		$this->assertEmpty( $job->get_logs(), 'First course should not have produced any warnings.' );

		$method->invokeArgs(
			$task,
			[
				$course_id_b,
				[ 'id:4255', 2, 'Post title' ],
			]
		);

		$logs = $job->get_logs();

		$this->assertEquals( 1, count( $logs ), 'A warnings should have been reported about the lesson associated with multiple courses' );
		$this->assertEquals( 'The lesson "id:4255" can only be associated with one course at a time.', $logs[0]['message'], 'Warning about missing lesson should have been added' );

		$order_index = 0;
		foreach ( $lesson_map as $original_id => $post_id ) {
			$this->assertEquals( $course_id_a, get_post_meta( $post_id, '_lesson_course', true ) );
			$this->assertEquals( $order_index, get_post_meta( $post_id, '_order_' . $course_id_a, true ) );

			$order_index++;
		}

		$expected_lesson_order = implode( ',', array_values( $lesson_map ) );
		$this->assertEquals( $expected_lesson_order, get_post_meta( $course_id_a, '_lesson_order', true ), 'Course A lesson order should have been set' );

		$this->assertEquals( '', get_post_meta( $course_id_b, '_lesson_order', true ), 'Course B lesson order should be empty' );
	}

	/**
	 * Tests course lessons when there are modules to associate as well.
	 */
	public function testHandleCourseLessonsWithModules() {
		$course_id        = $this->factory->course->create();
		$lessons          = $this->factory->lesson->create_many( 6 );
		$module_a_lessons = [
			'232'  => $lessons[1],
			'4255' => $lessons[3],
		];
		$module_b_lessons = [
			'122' => $lessons[2],
			'44'  => $lessons[4],
		];
		$loose_lessons    = [
			'425' => $lessons[5],
			'306' => $lessons[0],
		];

		$all_lessons = $module_a_lessons + $module_b_lessons + $loose_lessons;

		$module_a   = Sensei_Data_Port_Utilities::get_term( 'Module A', 'module', 1 );
		$module_b   = Sensei_Data_Port_Utilities::get_term( 'Module B', 'module', 1 );
		$module_ids = [ $module_a->term_id, $module_b->term_id ];

		wp_set_object_terms( $course_id, $module_ids, 'module' );

		$new_module_order = array_map( 'strval', $module_ids );
		update_post_meta( $course_id, '_module_order', $new_module_order );

		$job  = Sensei_Import_Job::create( 'test', 0 );
		$task = new Sensei_Import_Associations( $job );
		foreach ( $all_lessons as $original_id => $post_id ) {
			$job->set_import_id( Sensei_Data_Port_Lesson_Schema::POST_TYPE, $original_id, $post_id );
		}

		foreach ( $module_a_lessons as $original_id => $post_id ) {
			$task->add_lesson_module( $post_id, 'Module A', 1, 'Lesson ' . $post_id );
		}

		foreach ( $module_b_lessons as $original_id => $post_id ) {
			$task->add_lesson_module( $post_id, 'Module B', 2, 'Lesson ' . $post_id );
		}

		$task->add_course_lessons(
			$course_id,
			'id:232,id:425,id:306,id:122,id:4255,id:44',
			33,
			'Course Title'
		);

		$task->run();

		$order_index = 0;
		foreach ( $loose_lessons as $original_id => $post_id ) {
			$this->assertEquals( $course_id, get_post_meta( $post_id, '_lesson_course', true ) );
			$this->assertEquals( $order_index, get_post_meta( $post_id, '_order_' . $course_id, true ) );

			$order_index++;
		}

		$order_index = 0;
		foreach ( $module_a_lessons as $original_id => $post_id ) {
			$this->assertEquals( $course_id, get_post_meta( $post_id, '_lesson_course', true ) );
			$this->assertEquals( $order_index, get_post_meta( $post_id, '_order_module_' . $module_a->term_id, true ) );

			$order_index++;
		}

		$order_index = 0;
		foreach ( $module_b_lessons as $original_id => $post_id ) {
			$this->assertEquals( $course_id, get_post_meta( $post_id, '_lesson_course', true ) );
			$this->assertEquals( $order_index, get_post_meta( $post_id, '_order_module_' . $module_b->term_id, true ) );

			$order_index++;
		}
	}
}

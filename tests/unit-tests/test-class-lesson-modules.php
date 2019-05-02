<?php

class Sensei_Class_Lesson_Modules_Test extends WP_UnitTestCase {

	private $lesson_id;
	private $course_id;
	private $module_id;
	private $lesson_modules;

	private $module_taxonomy;

	/**
	 * Constructor function
	 */
	public function __construct() {
		parent::__construct();
	}

	public function setup() {
		parent::setup();

		$this->factory = new Sensei_Factory();

		// Use the taxonomy for Modules
		$this->module_taxonomy = Sensei()->modules->taxonomy;

		// Set up a new module to use for some tests
		$new_term        = wp_insert_term( 'My New Module', $this->module_taxonomy );
		$this->module_id = $new_term['term_id'];

		// Fetch a lesson
		$this->lesson_id = $this->factory->get_random_lesson_id();

		// Fetch a course
		$this->course_id = $this->factory->get_random_course_id();

		// Create the Sensei_Core_Lesson_Modules instance
		$this->lesson_modules = new Sensei_Core_Lesson_Modules( $this->lesson_id );
	}

	public function teardown() {
		parent::tearDown();
		$this->factory->tearDown();
		wp_delete_term( $this->module_id, $this->module_taxonomy );
	}


	/**
	 * Testing Sensei_Core_Lesson_Modules::set_module
	 */

	public function testSetModuleWithGivenCourse() {

		/*
		 * When the module belongs to the course, we should be able to set it
		 * on the lesson.
		 */

		// Set the module on the course
		wp_set_object_terms( $this->course_id, $this->module_id, $this->module_taxonomy );

		$this->lesson_modules->set_module( $this->module_id, $this->course_id );
		$this->assertTrue( has_term( $this->module_id, $this->module_taxonomy, $this->lesson_id ) );

		/*
		 * When the module does not belong to the course, we should be unset
		 * the lesson's module.
		 */

		// Remove the module from the course
		wp_delete_object_term_relationships( $this->course_id, $this->module_taxonomy );

		$this->lesson_modules->set_module( $this->module_id, $this->course_id );
		$this->assertEquals( wp_get_object_terms( $this->lesson_id, $this->module_taxonomy ), array() );

	}

	public function testSaveLessonModuleWithoutGivenCourse() {

		// Set the lesson on the course
		update_post_meta( $this->lesson_id, '_lesson_course', $this->course_id );

		/*
		 * When the module belongs to the course, we should be able to set it
		 * on the lesson.
		 */

		// Set the module on the course
		wp_set_object_terms( $this->course_id, $this->module_id, Sensei()->modules->taxonomy );

		$this->lesson_modules->set_module( $this->module_id );
		$this->assertTrue( has_term( $this->module_id, $this->module_taxonomy, $this->lesson_id ) );

		/*
		 * When the module does not belong to the course, we should unset the
		 * lesson's module.
		 */

		wp_delete_object_term_relationships( $this->course_id, $this->module_taxonomy );

		$this->lesson_modules->set_module( $this->module_id );
		$this->assertEquals( wp_get_object_terms( $this->lesson_id, $this->module_taxonomy ), array() );
	}

} // end class

<?php

class Sensei_Class_Modules_Test extends WP_UnitTestCase {

	/**
	 * Constructor function
	 */
	public function __construct() {
		parent::__construct();
	}


	/**
	 * setup function
	 * This function sets up the lessons, quizes and their questions. This function runs before
	 * every single test in this class
	 */
	public function setup() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * tearDown function
	 */
	public function tearDown() {
		parent::tearDown();
		$this->factory->tearDown();
	}

	/**
	 * Testing the quiz class to make sure it is loaded
	 */
	public function testClassInstance() {

		// test if the global sensei quiz class is loaded
		$this->assertTrue( isset( Sensei()->modules ), 'Sensei Modules class is not loaded' );

	} // end testClassInstance

	/**
	 * @covers Sensei_Core_Modules::do_link_to_module
	 */
	public function testDoLinkToModuleEmptyDescription() {
		$course_id   = $this->factory->get_course_with_modules();
		$modules     = wp_get_post_terms( $course_id, 'module' );
		$test_module = $modules[0];

		wp_update_term( $test_module->term_id, $test_module->taxonomy, array( 'description' => '' ) );
		$test_module = get_term( $test_module->term_id, 'module' );

		// Module doesn't have description.
		$this->assertFalse( Sensei()->modules->do_link_to_module( $test_module ) );
	}

	/**
	 * @covers Sensei_Core_Modules::do_link_to_module
	 */
	public function testDoLinkToModuleWithDescription() {
		$course_id   = $this->factory->get_course_with_modules();
		$modules     = wp_get_post_terms( $course_id, 'module' );
		$test_module = $modules[0];

		// Module now has description.
		$this->assertTrue( Sensei()->modules->do_link_to_module( $test_module ) );
	}


	/**
	 * @covers Sensei_Core_Modules::do_link_to_module
	 */
	public function testDoLinkToModuleCurrentTax() {
		global $wp_query;

		$course_id   = $this->factory->get_course_with_modules();
		$modules     = wp_get_post_terms( $course_id, 'module' );
		$test_module = $modules[0];

		wp_update_term( $test_module->term_id, $test_module->taxonomy, array( 'description' => 'A test description' ) );
		$wp_query->is_tax         = true;
		$wp_query->queried_object = $test_module;

		$test_module = get_term( $test_module->term_id, 'module' );

		$this->assertTrue( Sensei()->modules->do_link_to_module( $test_module, true ) );
		$this->assertFalse( Sensei()->modules->do_link_to_module( $test_module, false ) );
	}

	/**
	 * Testing Sensei_Core_Modules::get_term_author
	 */
	public function testGetTermAuthor() {

		// setup assertions
		$test_user_id = wp_create_user( 'teacherGetTermAuthor', 'teacherGetTermAuthor', 'teacherGetTermAuthor@test.com' );

		// insert a general term
		wp_insert_term( 'Get Started', 'module' );
		// insert a term as if from the user
		wp_insert_term(
			'Get Started Today',
			'module',
			array(
				'description' => 'A yummy apple.',
				'slug'        => $test_user_id . '-get-started-today',
			)
		);

		// does the function exist?
		$this->assertTrue( method_exists( 'Sensei_Core_Modules', 'get_term_authors' ), 'The function Sensei_Core_Modules::get_term_author does not exist ' );

		// does the taxonomy exist
		$module_taxonomy = get_taxonomy( 'module' );
		$this->assertTrue( $module_taxonomy->public, 'The module taxonomy is not loaded' );

		// does it return empty array id for bogus term nam?
		$term_authors = Sensei_Core_Modules::get_term_authors( 'bogusnonexistan' );
		$this->assertTrue( empty( $term_authors ), 'The function should return false for an invalid term' );

		// does it return the admin user for a valid term ?
		$admin        = get_user_by( 'email', get_bloginfo( 'admin_email' ) );
		$term_authors = Sensei_Core_Modules::get_term_authors( 'Get Started' );
		$this->assertTrue( $admin == $term_authors[0], 'The function should return admin user for normal module term.' );

		// does it return the expected new user for the given term registered with that id in front of the slug?
		$term_authors = Sensei_Core_Modules::get_term_authors( 'Get Started Today' );
		$this->assertTrue( get_userdata( $test_user_id ) == $term_authors[0], 'The function should admin user for normal module term.' );

		// what about terms with the same name but different slug?
		// It should return 2 authors as we've created 2 with the same name
		// insert a term that is the same as the first one
		wp_insert_term(
			'Get Started',
			'module',
			array(
				'description' => 'A yummy apple.',
				'slug'        => $test_user_id . '-get-started',
			)
		);
		$term_authors = Sensei_Core_Modules::get_term_authors( 'Get Started' );
		$this->assertTrue( 2 == count( $term_authors ), 'The function should admin user for normal module term.' );

	}

} // end class

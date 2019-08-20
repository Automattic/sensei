<?php

require_once 'test-class-sensei-unsupported-theme-handler-page-imitator.php';

class Sensei_Unsupported_Theme_Handler_Module_Test extends WP_UnitTestCase {

	/**
	 * @var Sensei_Unsupported_Theme_Handler_Module_Test The request handler to test.
	 */
	private $handler;

	/**
	 * @var WP_Post A course post with some Modules.
	 */
	private $course;

	/**
	 * @var array Some modules belonging to the Course.
	 */
	private $modules;

	public function setUp() {
		parent::setUp();

		$this->setupModulePage();

		Sensei_Unsupported_Theme_Handler_Page_Imitator_Test::create_page_template();

		$this->handler = new Sensei_Unsupported_Theme_Handler_Module();
	}

	public function tearDown() {
		$this->handler = null;

		Sensei_Unsupported_Theme_Handler_Page_Imitator_Test::delete_page_template();

		parent::tearDown();
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Module handles the Module Page.
	 *
	 * @since 1.12.0
	 */
	public function testShouldHandleModulePage() {
		$this->assertTrue( $this->handler->can_handle_request() );
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Module does not handle a
	 * non-Module page.
	 *
	 * @since 1.12.0
	 */
	public function testShouldNotHandleNonModulePage() {
		// Set up the query to be for the Courses page.
		global $wp_query;
		$wp_query = new WP_Query(
			array(
				'post_type' => 'course',
			)
		);

		$this->assertFalse( $this->handler->can_handle_request() );
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Module disables the header and
	 * footer when rendering the module.
	 *
	 * @since 1.12.0
	 */
	public function testShouldDisableHeaderAndFooter() {
		$this->assertFalse( has_filter( 'sensei_show_main_header', '__return_false' ), 'Header should initially be enabled' );
		$this->assertFalse( has_filter( 'sensei_show_main_footer', '__return_false' ), 'Footer should initially be enabled' );

		$this->handler->handle_request();

		$this->assertNotFalse( has_filter( 'sensei_show_main_header', '__return_false' ), 'Header should be disabled by handler' );
		$this->assertNotFalse( has_filter( 'sensei_show_main_footer', '__return_false' ), 'Footer should be disabled by handler' );
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Module renders the module using
	 * the taxonomy-module.php template.
	 *
	 * @since 1.12.0
	 */
	public function testShouldUseTaxonomyModuleTemplate() {
		// We'll test to ensure it uses the template by checking if the
		// sensei_taxonomy_module_content_inside_before action was run.
		$this->assertEquals(
			0,
			did_action( 'sensei_taxonomy_module_content_inside_before' ),
			'Should not have already done action sensei_taxonomy_module_content_inside_before'
		);

		$this->handler->handle_request();

		$this->assertEquals(
			1,
			did_action( 'sensei_taxonomy_module_content_inside_before' ),
			'Should have done action sensei_taxonomy_module_content_inside_before'
		);
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Module shows pagination when
	 * rendering the module.
	 *
	 * @since 1.12.0
	 */
	public function testShouldShowPagination() {
		$this->assertEquals(
			0,
			did_action( 'sensei_pagination' ),
			'Should not have already shown pagination'
		);

		$this->handler->handle_request();

		$this->assertEquals(
			1,
			did_action( 'sensei_pagination' ),
			'Should have shown pagination'
		);
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Module sets up a dummy post for
	 * the final render of the module content.
	 *
	 * @since 1.12.0
	 */
	public function testShouldSetupDummyPost() {
		global $post;

		$this->handler->handle_request();

		$this->assertNotEquals( 0, $post->ID, 'The dummy post ID should be non-zero' );
		$this->assertNull( get_post( $post->ID ), 'The dummy post ID should be unused' );
		$this->assertEquals( 'page', $post->post_type, 'The dummy post type should be "page"' );

		$copied_from_course = array(
			'post_author',
			'post_date',
			'post_date_gmt',
			'post_modified',
			'post_modified_gmt',
		);
		foreach ( $copied_from_course as $field ) {
			$this->assertEquals(
				$this->course->{$field},
				$post->{$field},
				"Dummy post field $field should be copied from the Course"
			);
		}

		// Our page is set up for Module 2.
		$module = get_term( $this->modules[1]['term_id'], Sensei()->modules->taxonomy );

		$copied_from_module = array(
			'post_title' => 'name',
			'post_name'  => 'slug',
		);
		foreach ( $copied_from_module as $post_field => $module_field ) {
			$this->assertEquals(
				$module->{$module_field},
				$post->{$post_field},
				"Dummy post field $post_field should be copied from the Module's $module_field"
			);
		}
	}

	/**
	 * Helper to set up the current request to be a Module page. This request
	 * will be handled by the unsupported theme handler if the theme is not
	 * supported.
	 *
	 * @since 1.12.0
	 */
	private function setupModulePage() {
		global $post, $wp_query, $wp_the_query;

		$this->course = $this->factory->post->create_and_get(
			array(
				'post_status' => 'publish',
				'post_type'   => 'course',
			)
		);

		// Create a few modules.
		$this->modules = array();

		for ( $i = 1; $i <= 3; $i++ ) {
			$module = wp_insert_term( 'Module ' . $i, Sensei()->modules->taxonomy );

			// Add the module to the course.
			wp_set_object_terms( $this->course->ID, $module['term_id'], Sensei()->modules->taxonomy );

			// Add two lessons to the course within the module.
			$lesson_ids = $this->factory->post->create_many(
				2,
				array(
					'post_status' => 'publish',
					'post_type'   => 'lesson',
				)
			);

			foreach ( $lesson_ids as $lesson_id ) {
				add_post_meta( $lesson_id, '_lesson_course', $this->course->ID );
				wp_set_object_terms( $lesson_id, $module['term_id'], Sensei()->modules->taxonomy );
			}

			$this->modules[] = $module;
		}

		// Setup $wp_query to be for the lessons in Module 2.
		$args         = array(
			'post_type' => 'lesson',
			'tax_query' => array(
				array(
					'taxonomy' => Sensei()->modules->taxonomy,
					'field'    => 'name',
					'terms'    => 'Module 2',
				),
			),
		);
		$wp_query     = new WP_Query( $args );
		$wp_the_query = $wp_query;

		// Setup $post to be the first lesson.
		$posts = get_posts(
			array_merge(
				$args,
				array(
					'posts_per_page' => 1,
				)
			)
		);
		$post  = $posts[0];
	}
}

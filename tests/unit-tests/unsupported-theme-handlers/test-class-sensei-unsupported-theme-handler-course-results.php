<?php

require_once 'test-class-sensei-unsupported-theme-handler-page-imitator.php';

class Sensei_Unsupported_Theme_Handler_Course_Results_Test extends WP_UnitTestCase {

	/**
	 * @var Sensei_Unsupported_Theme_Handler_Course_Results_Test The request handler to test.
	 */
	private $handler;

	/**
	 * @var WP_Post A course post.
	 */
	private $course;

	public function setUp() {
		parent::setUp();
		$this->factory = new Sensei_Factory();

		$this->setupCourseResultsPage();

		Sensei_Unsupported_Theme_Handler_Page_Imitator_Test::create_page_template();

		$this->handler = new Sensei_Unsupported_Theme_Handler_Course_Results();
	}

	public function tearDown() {
		$this->handler = null;

		Sensei_Unsupported_Theme_Handler_Page_Imitator_Test::delete_page_template();

		parent::tearDown();
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Course_Results handles the course results page.
	 *
	 * @since 1.12.0
	 */
	public function testShouldHandleCourseResultsPage() {
		$this->assertTrue( $this->handler->can_handle_request() );
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Course_Results does not handle a
	 * non-course results page.
	 *
	 * @since 1.12.0
	 */
	public function testShouldNotHandleNonCourseResultsPage() {
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
	 * Ensure Sensei_Unsupported_Theme_Handler_Course_Results disables the header and
	 * footer when rendering the course results page.
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
	 * Ensure Sensei_Unsupported_Theme_Handler_Course_Results renders the course results page using
	 * the course-results.php template.
	 *
	 * @since 1.12.0
	 */
	public function testShouldUseCourseResultsTemplate() {
		// We'll test to ensure it uses the template by checking if the
		// sensei_course_results_content_inside_before action was run.
		$this->assertEquals(
			0,
			did_action( 'sensei_course_results_content_inside_before' ),
			'Should not have already done action sensei_course_results_content_inside_before'
		);

		$this->handler->handle_request();

		$this->assertEquals(
			1,
			did_action( 'sensei_course_results_content_inside_before' ),
			'Should have done action sensei_course_results_content_inside_before'
		);
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Course_Results sets up a dummy post for
	 * the final render of the course results content.
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
	}

	/**
	 * Helper to set up the current request to be a course results page. This request
	 * will be handled by the unsupported theme handler if the theme is not
	 * supported.
	 *
	 * @since 1.12.0
	 */
	private function setupCourseResultsPage() {
		global $post, $wp_query, $wp_the_query;

		$this->course = get_post( $this->factory->get_course_with_modules() );

		$posts = $this->factory->post->create_many( 2 );

		// Setup $wp_query to be simple post list with the course_results query arg.
		$args         = array(
			'post_type'      => 'post',
			'course_results' => $this->course->post_name,
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

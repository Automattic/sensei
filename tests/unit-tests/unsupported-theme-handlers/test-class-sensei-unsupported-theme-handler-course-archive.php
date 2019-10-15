<?php

require_once 'test-class-sensei-unsupported-theme-handler-page-imitator.php';

class Sensei_Unsupported_Theme_Handler_Course_Archive_Test extends WP_UnitTestCase {

	/**
	 * @var Sensei_Unsupported_Theme_Handler_Course_Archive_Test The request handler to test.
	 */
	private $handler;

	/**
	 * @var WP_Post A course post.
	 */
	private $course;

	public function setUp() {
		parent::setUp();
		$this->factory = new Sensei_Factory();

		$this->setupCourseArchivePage();

		Sensei_Unsupported_Theme_Handler_Page_Imitator_Test::create_page_template();

		$this->handler = new Sensei_Unsupported_Theme_Handler_Course_Archive();
	}

	public function tearDown() {
		$this->handler = null;

		Sensei_Unsupported_Theme_Handler_Page_Imitator_Test::delete_page_template();

		parent::tearDown();
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Course_Archive handles the course archive page.
	 *
	 * @since 1.12.0
	 */
	public function testShouldHandleCourseArchivePage() {
		$this->assertTrue( $this->handler->can_handle_request() );
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Course_Archive does not handle a
	 * non-course archive page.
	 *
	 * @since 1.12.0
	 */
	public function testShouldNotHandleNonCourseArchivePage() {
		// Set up the query to be for the Lessons page.
		global $wp_query;
		$wp_query = new WP_Query(
			array(
				'post_type' => 'lesson',
			)
		);

		$this->assertFalse( $this->handler->can_handle_request() );
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Course_Archive disables the header and
	 * footer when rendering the course archive page.
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
	 * Ensure Sensei_Unsupported_Theme_Handler_Course_Archive renders the course
	 * archive page using the course-archive.php template.
	 *
	 * @since 1.12.0
	 */
	public function testShouldUseCourseArchiveTemplate() {
		// We'll test to ensure it uses the template by checking if the
		// sensei_archive_before_course_loop action was run.
		$this->assertEquals(
			0,
			did_action( 'sensei_archive_before_course_loop' ),
			'Should not have already done action sensei_archive_before_course_loop'
		);

		$this->handler->handle_request();

		$this->assertEquals(
			1,
			did_action( 'sensei_archive_before_course_loop' ),
			'Should have done action sensei_archive_before_course_loop'
		);
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Course_Archive sets up a dummy post for
	 * the final render of the course archive content.
	 *
	 * @since 1.12.0
	 */
	public function testShouldSetupDummyPost() {
		global $post;

		$this->handler->handle_request();

		$this->assertNotEquals( 0, $post->ID, 'The dummy post ID should be non-zero' );
		$this->assertNull( get_post( $post->ID ), 'The dummy post ID should be unused' );
		$this->assertEquals( 'page', $post->post_type, 'The dummy post type should be "page"' );
	}

	/**
	 * Helper to set up the current request to be a course archive page. This request
	 * will be handled by the unsupported theme handler if the theme is not
	 * supported.
	 *
	 * @since 1.12.0
	 */
	private function setupCourseArchivePage() {
		global $post, $wp_query, $wp_the_query;

		$courses = $this->factory->post->create_many(
			5,
			array(
				'post_type' => 'course',
			)
		);

		// Setup the globals.
		$args         = array(
			'post_type' => 'course',
		);
		$wp_query     = new WP_Query( $args );
		$wp_the_query = $wp_query;
		$post         = get_post( $courses[0] );
	}
}

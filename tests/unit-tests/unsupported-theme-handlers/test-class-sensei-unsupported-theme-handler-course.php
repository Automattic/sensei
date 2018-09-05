<?php

class Sensei_Unsupported_Theme_Handler_Course_Test extends WP_UnitTestCase {

	/**
	 * @var Sensei_Unsupported_Theme_Handler_Course_Test The request handler to test.
	 */
	private $handler;

	/**
	 * @var WP_Post The course being rendered.
	 */
	private $course;

	public function setUp() {
		parent::setUp();

		$this->setupCoursePage();

		$this->handler = new Sensei_Unsupported_Theme_Handler_Course();
	}

	public function tearDown() {
		$this->handler = null;

		parent::tearDown();
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Course handles the Course Page.
	 *
	 * @since 1.12.0
	 */
	public function testShouldHandleCoursePage() {
		$this->assertTrue( $this->handler->can_handle_request() );
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Course does not handle a
	 * non-Course page.
	 *
	 * @since 1.12.0
	 */
	public function testShouldNotHandleNonCoursePage() {
		global $post;
		$post = $this->factory->post->create_and_get();

		$this->assertFalse( $this->handler->can_handle_request() );
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Course sets up its `the_content`
	 * filter.
	 *
	 * @since 1.12.0
	 */
	public function testShouldSetUpContentFilter() {
		$this->handler->handle_request();
		$this->assertNotFalse( has_filter( 'the_content', array( $this->handler, 'course_page_content_filter' ) ) );
	}

	/**
	 * Ensure the content filter only runs if it is in the main query.
	 *
	 * @since 1.12.0
	 */
	public function testShouldRunOnlyInMainQuery() {
		global $wp_query;

		// Move out of main query.
		$wp_query = new WP_Query();

		$content = 'dummy content';

		$this->assertSame( $content, $this->handler->course_page_content_filter( $content ) );
	}

	/**
	 * Ensure the content filter removes itself from `the_content`.
	 *
	 * @since 1.12.0
	 */
	public function testShouldRemoveContentFilter() {
		$this->handler->course_page_content_filter( '' );

		$this->assertFalse( has_filter( 'the_content', array( $this->handler, 'course_page_content_filter' ) ) );
	}

	/**
	 * Ensure the content filter uses the Single Course Renderer.
	 *
	 * @since 1.12.0
	 */
	public function testShouldUseSingleCourseRenderer() {
		$handler_content = $this->handler->course_page_content_filter( '' );
		$renderer        = new Sensei_Renderer_Single_Course( $this->course->ID, array(
			'show_pagination' => true,
		) );
		$renderer_content = $renderer->render();

		$this->assertEquals(
			$renderer_content,
			$handler_content,
			'Output of content filter should match the output of the renderer'
		);
	}

	/**
	 * Ensure the content filter shows pagination when by default.
	 *
	 * @since 1.12.0
	 */
	public function testShouldShowPaginationByDefault() {
		$this->handler->course_page_content_filter( '' );
		$this->assertEquals( 1, did_action( 'sensei_pagination' ) );
	}

	/**
	 * Ensure the content filter hides pagination when filtered.
	 *
	 * @since 1.12.0
	 */
	public function testShouldHidePaginationWhenFiltered() {
		add_filter( 'sensei_course_page_show_pagination', '__return_false' );
		$this->handler->course_page_content_filter( '' );
		$this->assertEquals( 0, did_action( 'sensei_pagination' ) );
	}

	/**
	 * Helper to set up the current request to be a Course page. This request
	 * will be handled by the unsupported theme handler if the theme is not
	 * supported.
	 *
	 * @since 1.12.0
	 */
	private function setupCoursePage() {
		global $post, $wp_query, $wp_the_query, $page, $pages;

		$this->course = $this->factory->post->create_and_get( array(
			'post_status' => 'publish',
			'post_type'   => 'course',
		) );

		// Create a couple more courses so we have pagination links.
		$this->factory->post->create_many( 5, array(
			'post_status' => 'publish',
			'post_type'   => 'course',
		) );

		// Setup globals.
		$post                = $this->course;
		$wp_query->post      = $this->course;
		$wp_query->is_single = true;
		$page                = 1;
		$pages               = array( $post->post_content );

		// Ensure is_main_query is true.
		$wp_the_query = $wp_query;
	}
}

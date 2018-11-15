<?php

class Sensei_Unsupported_Theme_Handler_CPT_Test extends WP_UnitTestCase {

	/**
	 * @var Sensei_Unsupported_Theme_Handler_CPT The request handler to test.
	 */
	private $handler;

	/**
	 * @var WP_Post The course post being rendered. Since we are testing an
	 *              abstract class, we will test using the concrete Course
	 *              subclass.
	 */
	private $course;

	public function setUp() {
		parent::setUp();

		// We'll use a Course post and handler to test this.
		$this->setupCoursePage();
		$this->handler = new Sensei_Unsupported_Theme_Handler_CPT( 'course' );
	}

	public function tearDown() {
		$this->handler = null;

		parent::tearDown();
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_CPT handles the CPT Page.
	 *
	 * @since 1.12.0
	 */
	public function testShouldHandleCPTPage() {
		$this->assertTrue( $this->handler->can_handle_request() );
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_CPT does not handle a
	 * non-CPT page.
	 *
	 * @since 1.12.0
	 */
	public function testShouldNotHandleNonCPTPage() {
		global $post;
		$post = $this->factory->post->create_and_get();

		$this->assertFalse( $this->handler->can_handle_request() );
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_CPT sets up its `the_content`
	 * filter.
	 *
	 * @since 1.12.0
	 */
	public function testShouldSetUpContentFilter() {
		$this->handler->handle_request();
		$this->assertNotFalse( has_filter( 'the_content', array( $this->handler, 'cpt_page_content_filter' ) ) );
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

		$this->assertSame( $content, $this->handler->cpt_page_content_filter( $content ) );
	}

	/**
	 * Ensure the content filter only runs if it is in the loop.
	 *
	 * @since 1.12.0
	 */
	public function testShouldRunOnlyInTheLoop() {
		global $wp_query;

		// Move out of the loop.
		$wp_query->in_the_loop = false;

		$content = 'dummy content';

		$this->assertSame( $content, $this->handler->cpt_page_content_filter( $content ) );
	}

	/**
	 * Ensure the content filter removes itself from `the_content`.
	 *
	 * @since 1.12.0
	 */
	public function testShouldRemoveContentFilter() {
		$this->handler->handle_request();
		$this->handler->cpt_page_content_filter( '' );

		$this->assertFalse( has_filter( 'the_content', array( $this->handler, 'cpt_page_content_filter' ) ) );
	}

	/**
	 * Ensure the content filter uses the Single Post Renderer.
	 *
	 * @since 1.12.0
	 */
	public function testShouldUseSinglePostRenderer() {
		$this->handler->handle_request();

		$renderer         = new Sensei_Renderer_Single_Post(
			$this->course->ID,
			'single-course.php',
			array(
				'show_pagination' => true,
			)
		);
		$renderer_content = $renderer->render();

		// Run the handler last, since it has side-effects.
		$handler_content = $this->handler->cpt_page_content_filter( '' );

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
		$this->handler->handle_request();
		$this->handler->cpt_page_content_filter( '' );
		$this->assertEquals( 1, did_action( 'sensei_pagination' ) );
	}

	/**
	 * Ensure the content filter hides pagination when filtered.
	 *
	 * @since 1.12.0
	 */
	public function testShouldHidePaginationWhenFiltered() {
		add_filter( 'sensei_cpt_page_show_pagination', '__return_false' );
		$this->handler->handle_request();
		$this->handler->cpt_page_content_filter( '' );
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

		$this->course = $this->factory->post->create_and_get(
			array(
				'post_status' => 'publish',
				'post_type'   => 'course',
			)
		);

		// Create a couple more courses so we have pagination links.
		$this->factory->post->create_many(
			5,
			array(
				'post_status' => 'publish',
				'post_type'   => 'course',
			)
		);

		// Setup globals.
		$post                  = $this->course;
		$wp_query->post        = $this->course;
		$wp_query->is_single   = true;
		$wp_query->in_the_loop = true;
		$page                  = 1;
		$pages                 = array( $post->post_content );

		// Ensure is_main_query is true.
		$wp_the_query = $wp_query;
	}
}

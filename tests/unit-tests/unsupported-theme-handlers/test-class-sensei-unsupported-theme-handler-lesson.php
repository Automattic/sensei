<?php

class Sensei_Unsupported_Theme_Handler_Lesson_Test extends WP_UnitTestCase {

	/**
	 * @var Sensei_Unsupported_Theme_Handler_Lesson The request handler to test.
	 */
	private $handler;

	/**
	 * @var WP_Post The lesson being rendered.
	 */
	private $lesson;

	public function setUp() {
		parent::setUp();

		$this->setupLessonPage();

		$this->handler = new Sensei_Unsupported_Theme_Handler_Lesson();
	}

	public function tearDown() {
		$this->handler = null;

		parent::tearDown();
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Lesson handles the Lesson Page.
	 *
	 * @since 1.12.0
	 */
	public function testShouldHandleLessonPage() {
		$this->assertTrue( $this->handler->can_handle_request() );
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Lesson does not handle a
	 * non-Lesson page.
	 *
	 * @since 1.12.0
	 */
	public function testShouldNotHandleNonLessonPage() {
		global $post;
		$post = $this->factory->post->create_and_get();

		$this->assertFalse( $this->handler->can_handle_request() );
	}

	/**
	 * Ensure the content filter uses the Single Lesson Renderer.
	 *
	 * @since 1.12.0
	 */
	public function testShouldUseSinglePostRenderer() {
		$handler_content = $this->handler->cpt_page_content_filter( '' );
		$renderer        = new Sensei_Renderer_Single_Post(
			$this->lesson->ID,
			'single-lesson.php',
			array(
				'show_pagination' => true,
			)
		);
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
		$this->handler->cpt_page_content_filter( '' );
		$this->assertEquals( 1, did_action( 'sensei_pagination' ) );
	}

	/**
	 * Ensure the content filter hides pagination when filtered.
	 *
	 * @since 1.12.0
	 */
	public function testShouldHidePaginationWhenFiltered() {
		add_filter( 'sensei_lesson_page_show_pagination', '__return_false' );
		$this->handler->cpt_page_content_filter( '' );
		$this->assertEquals( 0, did_action( 'sensei_pagination' ) );
	}

	/**
	 * Helper to set up the current request to be a Lesson page. This request
	 * will be handled by the unsupported theme handler if the theme is not
	 * supported.
	 *
	 * @since 1.12.0
	 */
	private function setupLessonPage() {
		global $post, $wp_query, $wp_the_query, $page, $pages;

		$this->lesson = $this->factory->post->create_and_get( array(
			'post_status' => 'publish',
			'post_type'   => 'lesson',
		) );

		// Create a couple more lessons so we have pagination links.
		$this->factory->post->create_many( 5, array(
			'post_status' => 'publish',
			'post_type'   => 'lesson',
		) );

		// Setup globals.
		$post                = $this->lesson;
		$wp_query->post      = $this->lesson;
		$wp_query->is_single = true;
		$page                = 1;
		$pages               = array( $post->post_content );

		// Ensure is_main_query is true.
		$wp_the_query = $wp_query;
	}
}

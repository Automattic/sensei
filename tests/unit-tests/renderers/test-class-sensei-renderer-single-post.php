<?php

class Sensei_Renderer_Single_Post_Test extends WP_UnitTestCase {

	/**
	 * @var int $post_id The ID of the post created for the tests.
	 */
	private $post_id;

	public function setUp() {
		global $post, $page, $wp_query, $wp_the_query;

		parent::setUp();

		// Set up globals.
		$post         = $this->factory->post->create_and_get();
		$page         = 1;
		$wp_query     = new WP_Query( array( 'p' => $post->ID ) );
		$wp_the_query = $wp_query;

		// Set up Post.
		$this->post_id = $this->factory->post->create( array(
			'post_status' => 'publish',
		) );
	}

	/**
	 * Ensure renderer resets the global vars to what they were before.
	 *
	 * @since 1.12.0
	 */
	public function testShouldResetGlobalVars() {
		global $wp_query, $post, $pages;

		$old_query   = $wp_query;
		$old_post    = $post;

		$query_clone = clone $wp_query;
		$post_clone  = clone $post;
		$pages_clone = $pages; // Arrays are assigned by value.

		$renderer = new Sensei_Renderer_Single_Post( $this->post_id, 'single.php' );
		$renderer->render();

		$this->assertSame( $old_query, $wp_query, '$wp_query should be reset' );
		$this->assertSame( $old_post, $post, '$post should be reset' );

		$this->assertEquals( $query_clone, $wp_query, '$wp_query should be unchanged' );
		$this->assertEquals( $post_clone, $post, '$post should be unchanged' );
		$this->assertEquals( $pages_clone, $pages, '$pages should be unchanged' );
	}

	/**
	 * Ensure renderer disables Sensei's header and footer.
	 *
	 * @since 1.12.0
	 */
	public function testShouldDisableHeaderAndFooter() {
		$this->assertFalse( has_filter( 'sensei_show_main_header', '__return_false' ), 'Header should initially be enabled' );
		$this->assertFalse( has_filter( 'sensei_show_main_footer', '__return_false' ), 'Footer should initially be enabled' );

		$renderer = new Sensei_Renderer_Single_Post( $this->post_id, 'single.php' );
		$renderer->render();

		$this->assertNotFalse( has_filter( 'sensei_show_main_header', '__return_false' ), 'Header should be disabled by renderer' );
		$this->assertNotFalse( has_filter( 'sensei_show_main_footer', '__return_false' ), 'Footer should be disabled by renderer' );
	}

	/**
	 * Ensure renderer disables the_title for the post being rendered.
	 *
	 * @since 1.12.0
	 */
	public function testShouldDisableThePostTitle() {
		// TODO
	}

	/**
	 * Ensure renderer loads the given template.
	 *
	 * @since 1.12.0
	 */
	public function testShouldUseGivenTemplate() {
		/*
		 * We'll test to ensure it uses the template by using single-course.php
		 * and checking if the sensei_single_course_content_inside_before
		 * action was run.
		 */
		$this->assertEquals(
			0,
			did_action( 'sensei_single_course_content_inside_before' ),
			'Should not have already done action sensei_single_course_content_inside_before'
		);

		// Set up a course post.
		$this->post_id = $this->factory->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'course',
		) );

		$renderer = new Sensei_Renderer_Single_Post( $this->post_id, 'single-course.php' );
		$output = $renderer->render();

		$this->assertEquals(
			1,
			did_action( 'sensei_single_course_content_inside_before' ),
			'Should have done action sensei_single_course_content_inside_before'
		);
	}

	/**
	 * Ensure renderer shows pagination when required.
	 *
	 * @since 1.12.0
	 */
	public function testShouldShowPaginationWhenRequired() {
		$renderer = new Sensei_Renderer_Single_Post( $this->post_id, array(
			'show_pagination' => false,
		) );
		$renderer->render();

		$this->assertEquals(
			0,
			did_action( 'sensei_pagination' ),
			'Should not show pagination when show_pagination is false'
		);

		$renderer = new Sensei_Renderer_Single_Post( $this->post_id, 'single.php', array(
			'show_pagination' => true,
		) );
		$renderer->render();

		$this->assertEquals(
			1,
			did_action( 'sensei_pagination' ),
			'Should show pagination when show_pagination is true'
		);
	}
}

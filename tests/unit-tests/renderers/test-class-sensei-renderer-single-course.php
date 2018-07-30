<?php

class Sensei_Renderer_Single_Course_Test extends WP_UnitTestCase {

	public function setUp() {
		global $post, $page;

		parent::setUp();

		// Set up globals.
		$post = $this->factory->post->create_and_get();
		$page = 1;

		// Set up Course.
		$this->course_id = $this->factory->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'course',
		) );
	}

	/**
	 * Ensure renderer throws an exception on missing ID.
	 *
	 * @since 1.12.0
	 */
	public function testShouldThrowExceptionOnMissingId() {
		$renderer = new Sensei_Renderer_Single_Course( array() );

		try {
			$renderer->render();
		} catch ( Exception $exception ) {
			$this->assertInstanceOf( 'Sensei_Renderer_Missing_Fields_Exception', $exception );
			return;
		}

		$this->fail( 'No exception thrown' );
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

		$renderer = new Sensei_Renderer_Single_Course( array( 'id' => $this->course_id ) );
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

		$renderer = new Sensei_Renderer_Single_Course( array( 'id' => $this->course_id ) );
		$renderer->render();

		$this->assertNotFalse( has_filter( 'sensei_show_main_header', '__return_false' ), 'Header should be disabled by renderer' );
		$this->assertNotFalse( has_filter( 'sensei_show_main_footer', '__return_false' ), 'Footer should be disabled by renderer' );
	}

	/**
	 * Ensure renderer loads the `single-course.php` template.
	 *
	 * @since 1.12.0
	 */
	public function testShouldUseSingleCourseTemplate() {
		// We'll test to ensure it uses the template by checking if the
		// sensei_single_course_content_inside_before action was run.
		$this->assertEquals(
			0,
			did_action( 'sensei_single_course_content_inside_before' ),
			'Should not have already done action sensei_single_course_content_inside_before'
		);

		$renderer = new Sensei_Renderer_Single_Course( array( 'id' => $this->course_id ) );
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
		$renderer = new Sensei_Renderer_Single_Course( array(
			'id'              => $this->course_id,
			'show_pagination' => false,
		) );
		$renderer->render();

		$this->assertEquals(
			0,
			did_action( 'sensei_pagination' ),
			'Should not show pagination when show_pagination is false'
		);

		$renderer = new Sensei_Renderer_Single_Course( array(
			'id'              => $this->course_id,
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

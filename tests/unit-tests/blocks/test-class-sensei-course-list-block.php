<?php

/**
 * Tests for Sensei_Course_List_Block class.
 *
 * @group course-structure
 */
class Sensei_Course_List_Block_Test extends WP_UnitTestCase {
	/**
	 * Factory for setting up testing data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Skip tests for wp versions older than query block.
	 */
	private $skip_tests = false;

	/**
	 * Reference of the block instance
	 *
	 * @var object
	 */
	private $block_instance = null;

	/**
	 * Content of the block.
	 */
	private $content = '<!-- wp:query {"queryId":13,"query":{"offset":0,"postType":"course","order":"desc","orderBy":"date","author":"","search":"","sticky":"","perPage":4},"displayLayout":{"type":"flex","columns":3},"align":"wide","className":"wp-block-sensei-lms-course-list"} -->
<div class="wp-block-query alignwide wp-block-sensei-lms-course-list">
<!-- wp:post-template {"align":"wide"} -->
<!-- wp:post-title {"level":1,"isLink":true,"fontSize":"large"} /-->
<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:sensei-lms/button-take-course -->
<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link">Take Course</button></div>
<!-- /wp:sensei-lms/button-take-course -->
</div><!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->';

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		global $wp_version;

		$version = str_replace( '-src', '', $wp_version );
		if ( version_compare( $version, '5.8', '<' ) ) {
			$this->skip_tests = true;
			return;
		}

		parent::setUp();
		$this->factory = new Sensei_Factory();
		new Sensei_Course_List_Block();
		$this->factory->course->create_and_get( [ 'post_name' => 'some course' ] );

		add_filter( 'render_block_core/post-template', [ $this, 'get_block_instance' ], 10, 3 );
	}

	public function get_block_instance( $block_content, $block_parent, \WP_Block $instance ) {
		$this->block_instance = $instance;
	}

	public function testCourseListBlock_AddsAttributeToInnerTakeCourseButton_WhenRendered() {
		if ( $this->skip_tests ) {
			$this->markTestSkipped( 'This test requires WordPress 5.8 or higher.' );
		}
		/* ACT */
		do_blocks( $this->content );

		/* ASSERT */
		$this->assertTrue( $this->block_instance->parsed_block['innerBlocks'][1]['innerBlocks'][0]['attrs']['isCourseListChild'] );
	}

	public function testQueryLoopBlock_DoesNotAddCourseListAttributeToInnerTakeCourseButton_WhenRendered() {
		if ( $this->skip_tests ) {
			$this->markTestSkipped( 'This test requires WordPress 5.8 or higher.' );
		}
		/* ARRANGE */
		$modified_content = str_replace( '"postType":"course"', '"postType":"post"', $this->content );

		/* ACT */
		do_blocks( $modified_content );

		/* ASSERT */
		$this->assertArrayNotHasKey( 'isCourseListChild', $this->block_instance->parsed_block['innerBlocks'][1]['innerBlocks'][0]['attrs'] );
	}
}

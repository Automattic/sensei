<?php

/**
 * Tests for Sensei_Course_Progress_Block class.
 *
 * @covers Sensei_Global_Blocks
 */
class Sensei_Global_Blocks_Test extends WP_UnitTestCase {

	/**
	 * Instance of Sensei Global Blocks
	 *
	 * @var Sensei_Global_Blocks
	 */
	private $global_blocks;

	/**
	 * List of blocks
	 *
	 * @var Array
	 */
	private $blocks;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		$this->global_blocks = new Sensei_Global_Blocks();
		$this->blocks        = [
			'sensei-lms/button-take-course',
			'sensei-lms/button-view-results',
			'sensei-lms/course-progress',
			'sensei-lms/button-continue-course',
			'sensei-lms/course-categories',
			'sensei-lms/course-list-filter',
		];

		parent::setUp();
	}

	/**
	 * Register all global blocks
	 */
	public function testInitializeBlocks_WhenCalled_ShowsRegisterAllBlocks() {
		$is_registered = function ( string $block ): void {
			$this->assertTrue( WP_Block_Type_Registry::get_instance()->is_registered( $block ) );
		};

		/* Act */
		$this->global_blocks->initialize_blocks();

		/* Assert */
		array_map( $is_registered, $this->blocks );
	}


	/**
	 * Enqueue the global block assets
	 */
	public function testEnqueueBlockAssets_WhenCalled_EnqueueBlockAssets() {
		/* Act */
		$this->global_blocks->enqueue_block_assets();

		/* Assert */
		$this->assertTrue( wp_script_is( 'sensei-course-list-filter' ) );
		$this->assertTrue( wp_style_is( 'sensei-global-blocks-style' ) );
	}

	/**
	 * Enqueue the global block assets, except when the user is on the admin page.
	 */
	public function testEnqueueBlockAssets_WhenCalledOnAdmin_NotEnqueueCourseListFilter() {
		/* Arrange */

		$this->markTestSkipped( 'This test requires WordPress 5.8 or higher.' );
		set_current_screen( 'edit-post' );

		/* Act */
		$this->global_blocks->enqueue_block_assets();

		/* Assert */
		$this->assertFalse( wp_script_is( 'sensei-course-list-filter' ) );
	}


	/**
	 * Enqueue the global block assets
	 */
	public function testEnqueueBlockEditorAssets_WhenCalledOnAdmin_NotEnqueueCourseListFilter() {
		/* Act */
		$this->global_blocks->enqueue_block_editor_assets();

		/* Assert */
		$this->assertTrue( wp_script_is( 'sensei-global-blocks' ) );
		$this->assertTrue( wp_style_is( 'sensei-global-blocks-editor-style' ) );
	}
}

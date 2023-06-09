<?php
/**
 * Tests for Sensei_Blocks_Initializer class.
 *
 * @covers Sensei_Blocks_Initializer
 */
class Sensei_Blocks_Initializer_Test extends WP_UnitTestCase {
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	public function testConstruct_WhenCalled_RegistersHooks() {
		/* Act */
		$initializer_mock = $this->getMockForAbstractClass( Sensei_Blocks_Initializer::class );

		/* Assert */
		$this->assertEquals( 200, has_action( 'init', [ $initializer_mock, 'maybe_initialize_blocks' ] ) );
	}

	public function testMaybeInitializeBlocks_WhenInFrontend_InitializesBlocks() {
		/* Arrange */
		$initializer_mock = $this->getMockForAbstractClass( Sensei_Blocks_Initializer::class );

		/* Assert */
		$initializer_mock->expects( $this->once() )
			->method( 'initialize_blocks' );

		/* Act */
		$initializer_mock->maybe_initialize_blocks();
	}

	public function testMaybeInitializeBlocks_WhenInFrontend_InitializesAssets() {
		/* Arrange */
		$initializer_mock = $this->getMockForAbstractClass( Sensei_Blocks_Initializer::class );

		/* Act */
		$initializer_mock->maybe_initialize_blocks();

		/* Assert */
		$this->assertEquals( 9, has_action( 'template_redirect', [ $initializer_mock, 'initialize_frontend_assets' ] ) );
	}

	public function testMaybeInitializeBlocks_WhenInAdminOnScreenWithoutEditor_DoesNothing() {
		/* Arrange */
		$initializer_mock = $this->getMockForAbstractClass( Sensei_Blocks_Initializer::class );

		set_current_screen( 'dashboard' );

		/* Assert */
		$initializer_mock->expects( $this->never() )
			->method( 'initialize_blocks' );

		/* Act */
		$initializer_mock->maybe_initialize_blocks();
	}

	public function testMaybeInitializeBlocks_WhenInAdminOnSiteEditorScreen_InitializesBlocks() {
		/* Arrange */
		$initializer_mock = $this->getMockForAbstractClass( Sensei_Blocks_Initializer::class );

		set_current_screen( 'site-editor' );

		global $pagenow;
		$pagenow = 'site-editor.php';

		/* Assert */
		$initializer_mock->expects( $this->once() )
			->method( 'initialize_blocks' );

		/* Act */
		$initializer_mock->maybe_initialize_blocks();
	}

	public function testMaybeInitializeBlocks_WhenInAdminOnWidgetsScreen_InitializesBlocks() {
		/* Arrange */
		$initializer_mock = $this->getMockForAbstractClass( Sensei_Blocks_Initializer::class );

		set_current_screen( 'widgets' );

		global $pagenow;
		$pagenow = 'widgets.php';

		/* Assert */
		$initializer_mock->expects( $this->once() )
			->method( 'initialize_blocks' );

		/* Act */
		$initializer_mock->maybe_initialize_blocks();
	}

	public function testMaybeInitializeBlocks_WhenInAdminOnGutenbergEditSiteScreen_InitializesBlocks() {
		/* Arrange */
		$initializer_mock = $this->getMockForAbstractClass( Sensei_Blocks_Initializer::class );

		set_current_screen( 'site-editor' );
		$_GET['page'] = 'gutenberg-edit-site';

		/* Assert */
		$initializer_mock->expects( $this->once() )
			->method( 'initialize_blocks' );

		/* Act */
		$initializer_mock->maybe_initialize_blocks();
	}

	public function testMaybeInitializeBlocks_WhenInAdminOnNewPostScreenAndHasCorrectPostType_InitializesBlocks() {
		/* Arrange */
		$initializer_mock = $this->getMockForAbstractClass( Sensei_Blocks_Initializer::class, [ [ 'course' ] ] );

		set_current_screen( 'course' );
		$_GET['post_type'] = 'course';

		global $pagenow;
		$pagenow = 'post-new.php';

		/* Assert */
		$initializer_mock->expects( $this->once() )
			->method( 'initialize_blocks' );

		/* Act */
		$initializer_mock->maybe_initialize_blocks();
	}

	public function testMaybeInitializeBlocks_WhenInAdminOnNewPostScreenAndHasIncorrectPostType_DoesNothing() {
		/* Arrange */
		$initializer_mock = $this->getMockForAbstractClass( Sensei_Blocks_Initializer::class, [ [ 'lesson' ] ] );

		set_current_screen( 'course' );
		$_GET['post_type'] = 'course';

		global $pagenow;
		$pagenow = 'post-new.php';

		/* Assert */
		$initializer_mock->expects( $this->never() )
			->method( 'initialize_blocks' );

		/* Act */
		$initializer_mock->maybe_initialize_blocks();
	}

	public function testMaybeInitializeBlocks_WhenInAdminOnEditPostScreenAndHasCorrectPostType_InitializesBlocks() {
		/* Arrange */
		$initializer_mock = $this->getMockForAbstractClass( Sensei_Blocks_Initializer::class, [ [ 'course' ] ] );
		$post_id          = $this->factory->course->create();

		set_current_screen( 'post' );
		$_GET['post'] = $post_id;

		global $pagenow;
		$pagenow = 'post.php';

		/* Assert */
		$initializer_mock->expects( $this->once() )
			->method( 'initialize_blocks' );

		/* Act */
		$initializer_mock->maybe_initialize_blocks();
	}

	public function testMaybeInitializeBlocks_WhenInAdminOnEditPostScreenAndHasIncorrectPostType_DoesNothing() {
		/* Arrange */
		$initializer_mock = $this->getMockForAbstractClass( Sensei_Blocks_Initializer::class, [ [ 'lesson' ] ] );
		$post_id          = $this->factory->course->create();

		set_current_screen( 'post' );
		$_GET['post'] = $post_id;

		global $pagenow;
		$pagenow = 'post.php';

		/* Assert */
		$initializer_mock->expects( $this->never() )
			->method( 'initialize_blocks' );

		/* Act */
		$initializer_mock->maybe_initialize_blocks();
	}

	public function testInitializeFrontendAssets_WhenCorrectPostTypeAndHasSenseiBlocks_EnqueuesAssets() {
		/* Arrange */
		$initializer_mock = $this->getMockForAbstractClass( Sensei_Blocks_Initializer::class, [ [ 'course' ] ] );

		global $post;
		$post = $this->factory->course->create_and_get( [ 'post_content' => '<!-- wp:sensei-lms/lesson-actions --><!-- /wp:sensei-lms/lesson-actions -->' ] );

		/* Assert */
		$initializer_mock->expects( $this->once() )
			->method( 'enqueue_block_assets' );

		/* Act */
		$initializer_mock->initialize_frontend_assets();
		do_action( 'enqueue_block_assets' );
	}

	public function testInitializeFrontendAssets_WhenIncorrectPostTypeAndHasSenseiBlocks_DoesNothing() {
		/* Arrange */
		$initializer_mock = $this->getMockForAbstractClass( Sensei_Blocks_Initializer::class, [ [ 'lesson' ] ] );

		global $post;
		$post = $this->factory->course->create_and_get( [ 'post_content' => '<!-- wp:sensei-lms/lesson-actions --><!-- /wp:sensei-lms/lesson-actions -->' ] );

		/* Assert */
		$initializer_mock->expects( $this->never() )
			->method( 'enqueue_block_assets' );

		/* Act */
		$initializer_mock->initialize_frontend_assets();
		do_action( 'enqueue_block_assets' );
	}
}

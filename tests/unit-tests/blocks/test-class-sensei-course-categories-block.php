<?php
require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';

/**
 * Tests for Sensei_Block_Take_Course class.
 *
 * @group course-structure
 * @covers Sensei_Course_Categories_Block
 */
class Sensei_Course_Categories_Block_Test extends WP_UnitTestCase {
	/**
	 * Factory helper.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Take course block.
	 *
	 * @var Sensei_Course_Categories_Block
	 */
	private $block;

	/**
	 * Current course category.
	 *
	 * @var WP_Term
	 */
	private $category;

	/**
	 * Block content.
	 */
	const CONTENT = '<!-- wp:sensei-lms/course-categories --><div></div>-->';

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->factory  = new Sensei_Factory();
		$this->block    = new Sensei_Course_Categories_Block();
		$this->category = $this->factory->course_category->create_and_get();
		$course         = $this->factory->course->create_and_get( [ 'post_name' => 'some course' ] );
		$this->factory->course_category->add_post_terms( $course->ID, [ $this->category->term_id ], 'course-category' );

		$GLOBALS['post'] = $course;
	}

	public function tearDown(): void {
		parent::tearDown();
		WP_Block_Type_Registry::get_instance()->unregister( 'sensei-lms/course-categories' );
	}

	/**
	 * The course categories is registered
	 */
	public function testBlock_RegisterBlock() {
		/* Arrange */
		WP_Block_Type_Registry::get_instance()->unregister( 'sensei-lms/course-categories' );

		/* Act */
		new Sensei_Course_Categories_Block();

		/* Assert */
		$this->assertTrue(
			WP_Block_Type_Registry::get_instance()->is_registered( 'sensei-lms/course-categories' )
		);
	}

	/**
	 * The course categories block renders content.
	 */
	public function testBlock_RenderTheBlockContent() {
		/* Act */
		$result = do_blocks( self::CONTENT );

		/* Assert */
		$this->assertStringContainsString( $this->category->name, $result );
		$this->assertStringContainsString( $this->category->slug, $result );
	}

	/**
	 * The course categories block is rendering the style and the class attributes from the wrapper.
	 */
	public function testBlockRender_RenderTheAttributesFromTheWrapper() {
		/* Act */
		$content_with_attributes = '<!-- wp:sensei-lms/course-categories --><div class="some-class" style="some-style"></div>-->';
		$result                  = do_blocks( $content_with_attributes );

		/* Assert */
		$this->assertStringContainsString( 'class="some-class"', $result );
		$this->assertStringContainsString( 'style="some-style"', $result );
	}

	/**
	 * Doesn't render the block if it's not running in a course context.
	 */
	public function testRenderBlock_Page_ReturnsEmptyString() {
		$GLOBALS['post'] = $this->factory->post->create_and_get( [ 'post_name' => 'some post' ] );

		/* Act */
		$result = do_blocks( self::CONTENT );

		/* Assert */
		$this->assertEmpty( $result );
	}

	/**
	 * Doesn't render the block if there are no course categories
	 */
	public function testRenderBlockWithNoCourseCategories_Page_ReturnsEmptyString() {
		/* Arrange */
		$GLOBALS['post'] = $this->factory->course->create_and_get(
			[ 'post_name' => 'course without course categories' ]
		);

		/* Act */
		$result = do_blocks( self::CONTENT );

		/* Assert */
		$this->assertEmpty( $result );
	}

}

<?php
require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';

/**
 * Tests for Sensei_Block_Take_Course class.
 *
 * @group course-structure
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
	const CONTENT = '<!-- wp:sensei-lms/course-categories {"textAlign": "left", "align":"center", "options": {"textColor":"#cccccc","backgroundColor":"#dddddd"} } /-->';

	/**
	 * Set up the test.
	 */
	public function setUp() {
		parent::setUp();
		$this->factory = new Sensei_Factory();
		$this->block    = new Sensei_Course_Categories_Block();
		$this->category = $this->factory->course_category->create_and_get();
		$course         = $this->factory->course->create_and_get( [ 'post_name' => 'some course' ] );
		$this->factory->course_category->add_post_terms( $course->ID, [ $this->category->term_id ], 'course-category' );

		$GLOBALS['post'] = $course;
	}

	public function tearDown() {
		parent::tearDown();
		WP_Block_Type_Registry::get_instance()->unregister( 'sensei-lms/course-categories' );
	}

	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
	}


	/**
	 * The course categories is registered
	 *
	 * @covers Sensei_Course_Categories_Block::construct
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
	 *
	 * @covers Sensei_Course_Categories_Block::render_block
	 */
	public function testBlock_RenderTheBlockContent() {
		/* Act */
		$result = do_blocks( self::CONTENT );

		/* Assert */
		$this->assertContains( $this->category->name, $result );
		$this->assertContains( $this->category->slug, $result );
	}

	/**
	 * The course categories block is rendering the style attributes properly.
	 *
	 * @covers Sensei_Course_Categories_Block::render_block
	 */
	public function testBlockRender_RenderTheAttributes() {
		/* Act */
		$result = do_blocks( self::CONTENT );

		/* Assert */
		$this->assertContains( 'has-text-align-left', $result );
		$this->assertContains( 'aligncenter', $result );
		$this->assertContains( '--sensei-lms-course-categories-text-color: #cccccc', $result );
		$this->assertContains( '--sensei-lms-course-categories-background-color: #dddddd;', $result );

	}

	/**
	 * Doesn't render the block if it's not running in a course context.
	 *
	 * @covers Sensei_Course_Categories_Block::render_block
	 */
	public function testRenderBlock_Page_ReturnsEmptyString() {

		/* Arrange */
		$GLOBALS['post'] = (object) [
			'post_type' => 'page',
		];

		/* Act */
		$result = do_blocks( self::CONTENT );

		/* Assert */
		$this->assertEmpty( $result );
	}

	/**
	 * Doesn't render the block if there are no course categories
	 *
	 * @covers Sensei_Course_Categories_Block::render_block
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

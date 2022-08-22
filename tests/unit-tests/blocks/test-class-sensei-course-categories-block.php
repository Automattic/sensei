<?php
require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';

/**
 * Tests for Sensei_Block_Take_Course class.
 *
 * @group course-structure
 */
class Sensei_Course_Categories_Block_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Test_Helpers;
	use Sensei_Course_Enrolment_Manual_Test_Helpers;
	use Sensei_Test_Login_Helpers;

	/**
	 * Take course block.
	 *
	 * @var Sensei_Course_Categories_Block
	 */
	private $block;

	/**
	 * Current course.
	 *
	 * @var WP_Post
	 */
	private $course;

	/**
	 * Current course category.
	 *
	 * @var WP_Term
	 */
	private $category;

	/**
	 * Block content.
	 */
	const CONTENT = '<!-- wp:sensei-lms/course-categories {"align":"center","categoryStyle":{"classes":[],"style":{}},"textColor":"secondary","backgroundColor":"background","style":{"spacing":{"margin":{"top":"10px","right":"0","bottom":"10px","left":"0"}}}} /-->';

	/**
	 * Set up the test.
	 */
	public function setUp() {
		parent::setUp();
		$this->factory = new Sensei_Factory();
		self::resetEnrolmentProviders();
		$this->prepareEnrolmentManager();

		$this->block    = new Sensei_Course_Categories_Block();
		$this->category = $this->factory->course_category->create_and_get();
		$this->course   = $this->factory->course->create_and_get( [ 'post_name' => 'some course' ] );
		$this->factory->course_category->add_post_terms( $this->course->ID, [ $this->category->term_id ], 'course-category' );

		$GLOBALS['post'] = $this->course;
	}

	public function tearDown() {
		parent::tearDown();
		WP_Block_Type_Registry::get_instance()->unregister( 'sensei-lms/course-categories' );
	}

	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		self::resetEnrolmentProviders();
	}

	/**
	 * The course categories block is registered and renders content.
	 *
	 * @covers Sensei_Course_Categories_Block::render_block
	 */
	public function testBlockRegistered() {
		$result = do_blocks( self::CONTENT );

		$this->assertContains( $this->category->name, $result );
		$this->assertContains( $this->category->slug, $result );
	}

	/**
	 * Doesn't render the block if it's not running in a course context.
	 *
	 * @covers Sensei_Course_Categories_Block::render_block
	 */
	public function testRenderBlock_Page_ReturnsEmptyString() {
		$GLOBALS['post'] = (object) [
			'post_type' => 'page',
		];

		$result = do_blocks( self::CONTENT );

		$this->assertEmpty( $result );
	}
}

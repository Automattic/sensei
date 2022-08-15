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
	 * Set up the test.
	 */
	public function setUp() {
		parent::setUp();
		$this->factory = new Sensei_Factory();
		self::resetEnrolmentProviders();
		$this->prepareEnrolmentManager();

		$this->block    = new Sensei_Course_Categories_Block();
		$this->category = $this->factory->course_category->create_and_get();
		$this->course   = $this->factory->course->create_and_get( [ 'post_name' => 'take-block-course' ] );
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
	 */
	public function testBlockRegistered() {
		 $post_content = '<!-- wp:sensei-lms/course-categories {"categoryStyle":{"classes":["has-primary-category-text-color","has-tertiary-category-background-color"],"style":{"color":"#1a4548","backgroundColor":"#F6F6F6"}}} /-->';
		$result        = do_blocks( $post_content );

		$this->assertContains( $this->category->name, $result );
		$this->assertContains( $this->category->slug, $result );
	}
}

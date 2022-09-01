<?php

/**
 * Tests for Sensei_Course_List_Filter_Block class.
 *
 * @group course-structure
 */
class Sensei_Course_List_Filter_Block_Test extends WP_UnitTestCase {

	/**
	 * Instance of Sensei_Course_List_Filter_Block.
	 *
	 * @var Sensei_Course_List_Filter_Block
	 */
	private $block;

	/**
	 * In category course.
	 *
	 * @var WP_Post
	 */
	private $course1;

	/**
	 * No category course.
	 *
	 * @var WP_Post
	 */
	private $course2;

	/**
	 * A course category.
	 *
	 * @var WP_Term
	 */
	private $category;

	/**
	 * Skip tests for wp versions older than query block.
	 */
	private $skip_tests = false;

	/**
	 * Set up the test.
	 */
	public function setUp() {
		global $wp_version;

		$version = str_replace( '-src', '', $wp_version );
		if ( version_compare( $version, '5.8', '<' ) ) {
			$this->skip_tests = true;
			return;
		}

		parent::setUp();
		$this->factory = new Sensei_Factory();

		$this->block    = new Sensei_Course_List_Filter_Block();
		$this->category = $this->factory->course_category->create_and_get();
		$this->course1  = $this->factory->course->create_and_get( [ 'post_name' => 'some course' ] );
		$this->course2  = $this->factory->course->create_and_get( [ 'post_name' => 'another course' ] );
		$this->factory->course_category->add_post_terms( $this->course1->ID, [ $this->category->term_id ], 'course-category' );
	}

	public function tearDown() {
		parent::tearDown();
		WP_Block_Type_Registry::get_instance()->unregister( 'sensei-lms/course-list-filter' );
	}

	public function testCourseFilterBlock_ShowsCoursesAndCategoriesProperly_WhenRendered() {
		if ( $this->skip_tests ) {
			$this->markTestSkipped( 'This test requires WordPress 5.8 or higher.' );
		}
		/* ACT */
		$result = do_blocks( self::get_content_for_type( 'categories' ) );

		/* ASSERT */
		$this->assertContains( $this->category->name, $result );
		$this->assertContains( $this->course1->post_title, $result );
		$this->assertContains( $this->course2->post_title, $result );
	}

	public function testCourseFilterBlock_WhenCalledWithNonCategoryFilterParam_ShowsAllCourses() {
		if ( $this->skip_tests ) {
			$this->markTestSkipped( 'This test requires WordPress 5.8 or higher.' );
		}
		/* ARRANGE */
		$_GET['course-list-category-filter-13'] = 0;

		/* ACT */
		$result = do_blocks( self::get_content_for_type( 'categories' ) );

		/* ASSERT */
		$this->assertContains( $this->course1->post_title, $result );
		$this->assertContains( $this->course2->post_title, $result );
	}

	public function testCourseFilterBlock_WhenCalledWithCategoryFilterParam_ShowsOnlyFilteredCourses() {
		if ( $this->skip_tests ) {
			$this->markTestSkipped( 'This test requires WordPress 5.8 or higher.' );
		}
		/* ARRANGE */
		$_GET['course-list-category-filter-13'] = $this->category->term_id;

		/* ACT */
		$result = do_blocks( self::get_content_for_type( 'categories' ) );

		/* ASSERT */
		$this->assertContains( $this->course1->post_title, $result );
		$this->assertNotContains( $this->course2->post_title, $result );
	}

	/**
	 * Block content.
	 */
	private function get_content_for_type( $type ) {
		return '<!-- wp:query {"queryId":13,"query":{"offset":0,"postType":"course","order":"desc","orderBy":"date","author":"","search":"","sticky":"","perPage":4},"displayLayout":{"type":"flex","columns":3},"align":"wide","className":"wp-block-sensei-lms-course-list"} -->
<div class="wp-block-query alignwide wp-block-sensei-lms-course-list"><!-- wp:sensei-lms/course-list-filter {"type":"' . $type . '"} /-->
<!-- wp:post-template {"align":"wide"} -->
<!-- wp:post-title {"level":1,"isLink":true,"className":"hide-url-underline","fontSize":"large"} /-->
<!-- /wp:post-template --></div>
<!-- /wp:query -->';
	}
}

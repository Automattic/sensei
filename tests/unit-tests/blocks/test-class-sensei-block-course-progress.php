<?php
/**
 * Tests for Sensei_Course_Progress_Block class.
 */
class Sensei_Course_Progress_Block_Test extends WP_UnitTestCase {
	/**
	 * Course Progress block.
	 *
	 * @var Sensei_Course_Progress_Block
	 */
	private $block;

	/**
	 * Block content.
	 */
	const CONTENT = '<!-- wp:sensei-lms/course-progress /-->';

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
		$this->block   = new Sensei_Course_Progress_Block();
		$this->course  = $this->factory->course->create_and_get( [ 'post_name' => 'course-progress-block' ] );

		$GLOBALS['post'] = $this->course;
	}

	public function tearDown(): void {
		parent::tearDown();
		WP_Block_Type_Registry::get_instance()->unregister( 'sensei-lms/course-progress' );
	}

	/**
	 * Doesn't render the block if it's not running in a course context.
	 *
	 * @covers Sensei_Course_Progress_Block::render_course_progress
	 */
	public function testRenderCourseProgress_Page_ReturnsEmptyString() {
		// Update the global post object ID to be the course ID, but change its post type to a page.
		$GLOBALS['post'] = (object) [
			'post_type' => 'page',
		];

		$result = $this->block->render_course_progress( [], self::CONTENT );

		$this->assertEmpty( $result );
	}
}

<?php
/**
 * Tests for Sensei_Course_Overview_Block class.
 */
class Sensei_Course_Overview_Block_Test extends WP_UnitTestCase {
	/**
	 * Course Overview block.
	 *
	 * @var Sensei_Course_Overview_Block
	 */
	private $block;

	/**
	 * Block content.
	 */
	const CONTENT = '<!-- wp:sensei-lms/course-overview /-->';

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
		$this->block   = new Sensei_Course_Overview_Block();
	}

	public function tearDown(): void {
		parent::tearDown();
		WP_Block_Type_Registry::get_instance()->unregister( 'sensei-lms/course-overview' );
	}

	/**
	 * Doesn't render the block if course ID cannot be determined.
	 *
	 * @covers Sensei_Course_Overview_Block::render
	 */
	public function testRender_NoCourseId_ReturnsEmptyString() {
		$result = do_blocks( self::CONTENT );

		$this->assertEmpty( $result );
	}

	/**
	 * Renders the block.
	 *
	 * @covers Sensei_Course_Overview_Block::render
	 */
	public function testRender_ValidCourseId_ReturnsModifiedBlockContent() {
		$GLOBALS['post'] = $this->factory->course->create_and_get( [ 'post_name' => 'course-overview-block' ] );

		$result = do_blocks( self::CONTENT );

		$this->assertEquals( '<div class="wp-block-sensei-lms-course-overview"><a href="http://example.org/?course=course-overview-block">Course Overview</a></div>', $result );
	}
}

<?php
/**
 * This file contains the Exit_Course_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Sensei\Blocks\Course_Theme\Exit_Course;

/**
 * Tests for Exit_Course_Test class.
 *
 * @group course-theme
 */
class Exit_Course_Test extends WP_UnitTestCase {
	/**
	 * Setup function.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();

		WP_Block_Supports::$block_to_render = array(
			'attrs'     => array(),
			'blockName' => 'sensei-lms/exit-course',
		);
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		WP_Block_Supports::$block_to_render = null;
	}

	/**
	 * Tests that the block links to the current course.
	 */
	public function testRender_NoAttributesGiven_RendersTheCourseLink() {
		$course_id       = $this->factory->course->create();
		$GLOBALS['post'] = get_post( $course_id );

		$block = new Exit_Course();
		$html  = $block->render();

		$this->assertStringContainsString( 'href="' . esc_url( get_the_permalink( $course_id ) ) . '"', $html );
	}

	/**
	 * Tests that a label containing markup is escaped rather than rendered.
	 */
	public function testRender_MaliciousLabelGiven_EscapesTheLabel() {
		$course_id       = $this->factory->course->create();
		$GLOBALS['post'] = get_post( $course_id );

		$block = new Exit_Course();
		$html  = $block->render( array( 'label' => '<img src=x onerror=alert(document.cookie)>' ) );

		$this->assertStringContainsString( '&lt;img src=x onerror=alert(document.cookie)&gt;', $html );
	}
}

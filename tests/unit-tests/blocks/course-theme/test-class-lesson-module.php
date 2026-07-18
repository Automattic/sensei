<?php
/**
 * This file contains the Lesson_Module_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Sensei\Blocks\Course_Theme\Lesson_Module;

/**
 * Tests for Lesson_Module class.
 *
 * @group course-theme
 */
class Lesson_Module_Test extends WP_UnitTestCase {
	/**
	 * Setup function.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();

		WP_Block_Supports::$block_to_render = array(
			'attrs'     => array(),
			'blockName' => 'sensei-lms/course-theme-lesson-module',
		);
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		WP_Block_Supports::$block_to_render = null;
	}

	/**
	 * Test that renders empty when there is no current lesson.
	 */
	public function testRender_NoCurrentLessonSet_ReturnsEmpty() {
		$GLOBALS['post'] = null;
		$block           = new Lesson_Module();

		$result = $block->render();

		$this->assertEmpty( $result, 'Should return empty string when there is no current lesson' );
	}

	/**
	 * Test that renders empty when the lesson has no module.
	 */
	public function testRender_NoModuleAssigned_ReturnsEmpty() {
		$lesson          = $this->factory->lesson->create_and_get();
		$GLOBALS['post'] = $lesson;
		$block           = new Lesson_Module();

		$result = $block->render();

		$this->assertEmpty( $result, 'Should return empty string when lesson has no module' );
	}

	/**
	 * Test that renders the module title when a lesson belongs to a module.
	 */
	public function testRender_ModuleAssigned_RendersModuleTitle() {
		$course = $this->factory->course->create_and_get();
		$lesson = $this->factory->lesson->create_and_get(
			array(
				'meta_input' => array(
					'_lesson_course' => $course->ID,
				),
			)
		);
		$module = wp_insert_term( 'Test Module', Sensei()->modules->taxonomy );
		wp_set_object_terms( $course->ID, (int) $module['term_id'], Sensei()->modules->taxonomy );
		wp_set_object_terms( $lesson->ID, (int) $module['term_id'], Sensei()->modules->taxonomy );
		$GLOBALS['post'] = $lesson;
		$block           = new Lesson_Module();

		$html = $block->render();

		$this->assertStringContainsString( 'Test Module', $html, 'Should render the module title' );
	}
}

<?php
/**
 * This file contains the Lesson_Module_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei\Blocks\Course_Theme\Lesson_Module;

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

		WP_Block_Supports::$block_to_render = [
			'attrs'     => [],
			'blockName' => 'sensei-lms/course-theme-lesson-module',
		];
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		WP_Block_Supports::$block_to_render = null;
	}

	/**
	 * Testing the Lesson_Module class to make sure it is loaded.
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( '\Sensei\Blocks\Course_Theme\Lesson_Module' ), 'Lesson_Module class should exist' );
	}

	/**
	 * Test that className attribute is defined as a proper schema object, not a raw string.
	 *
	 * A raw string causes a PHP Fatal TypeError in WordPress REST validation (rest_validate_value_from_schema)
	 * on PHP 8.x when string offsets are accessed as arrays.
	 */
	public function testBlockJson_ClassNameAttributeDefined_IsSchemaArray() {
		$registered = WP_Block_Type_Registry::get_instance()->get_registered( 'sensei-lms/course-theme-lesson-module' );

		$this->assertNotNull( $registered, 'Block should be registered' );

		$this->assertArrayHasKey( 'className', $registered->attributes, 'className attribute should be defined' );

		$this->assertIsArray(
			$registered->attributes['className'],
			'className attribute must be a schema array, not a raw string — a string causes a PHP Fatal TypeError in PHP 8.x REST validation'
		);
	}

	/**
	 * Test that renders empty when there is no current lesson.
	 */
	public function testRender_NoLesson_ReturnsEmpty() {
		$GLOBALS['post'] = null;

		$block = new Lesson_Module();

		$this->assertEmpty( $block->render(), 'Should return empty string when there is no current lesson' );
	}

	/**
	 * Test that renders empty when the lesson has no module.
	 */
	public function testRender_LessonWithNoModule_ReturnsEmpty() {
		$lesson          = $this->factory->lesson->create_and_get();
		$GLOBALS['post'] = $lesson;

		$block = new Lesson_Module();

		$this->assertEmpty( $block->render(), 'Should return empty string when lesson has no module' );
	}

	/**
	 * Test that renders the module title when a lesson belongs to a module.
	 */
	public function testRender_LessonWithModule_RendersModuleTitle() {
		$course  = $this->factory->course->create_and_get();
		$lesson  = $this->factory->lesson->create_and_get(
			[
				'meta_input' => [
					'_lesson_course' => $course->ID,
				],
			]
		);
		$module  = wp_insert_term( 'Test Module', Sensei()->modules->taxonomy );
		wp_set_object_terms( $course->ID, (int) $module['term_id'], Sensei()->modules->taxonomy );
		wp_set_object_terms( $lesson->ID, (int) $module['term_id'], Sensei()->modules->taxonomy );

		$GLOBALS['post'] = $lesson;

		$block = new Lesson_Module();
		$html  = $block->render();

		$this->assertStringContainsString( 'Test Module', $html, 'Should render the module title' );
	}
}

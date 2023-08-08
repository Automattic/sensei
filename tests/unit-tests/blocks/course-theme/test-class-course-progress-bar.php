<?php
/**
 * This file contains the Course_Progress_Bar_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei\Blocks\Course_Theme\Course_Progress_Bar;

/**
 * Tests for Course_Progress_Bar_Test class.
 *
 * @group course-theme
 */
class Course_Progress_Bar_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;
	/**
	 * Setup function.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();

		WP_Block_Supports::$block_to_render = [
			'attrs'     => [],
			'blockName' => 'sensei-lms/course-theme-course-progress-bar',
		];
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		WP_Block_Supports::$block_to_render = null;
	}

	/**
	 * Testing the Course Progress Bar class to make sure it is loaded.
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( '\Sensei\Blocks\Course_Theme\Course_Progress_Bar' ), '\Sensei\Blocks\Course_Theme\Course_Progress_Bar class should exist' );
	}

	/**
	 * Tests that course progress bar width is correct.
	 */
	public function testProgressBarWidth() {
		$course  = $this->factory->course->create_and_get();
		$lesson1 = $this->factory->lesson->create_and_get();
		add_post_meta( $lesson1->ID, '_lesson_course', $course->ID );
		$lesson2 = $this->factory->lesson->create_and_get();
		add_post_meta( $lesson2->ID, '_lesson_course', $course->ID );

		$this->login_as_student();
		$GLOBALS['post'] = $course;
		$block           = new Course_Progress_Bar();

		// check for 0% width
		$this->assertStringContainsString( 'width: 0%', $block->render(), 'The course progress bar width should be 0%.' );

		// check for 50% width
		\Sensei_Utils::sensei_start_lesson( $lesson1->ID, get_current_user_id(), true );
		$this->assertStringContainsString( 'width: 50%', $block->render(), 'The course progress bar width should be 50%.' );

		// check for 100% width
		\Sensei_Utils::sensei_start_lesson( $lesson2->ID, get_current_user_id(), true );
		$this->assertStringContainsString( 'width: 100%', $block->render(), 'The course progress bar width should be 100%.' );
	}

	/**
	 * Tests that course progress bar is empty if the post type is not applicable.
	 */
	public function testProgressBarEmpty() {
		$this->login_as_student();
		$random_post     = $this->factory->post->create_and_get();
		$GLOBALS['post'] = $random_post;
		$block           = new Course_Progress_Bar();

		$this->assertEquals( '', $block->render(), 'The course progress bar should be empty.' );
	}
}

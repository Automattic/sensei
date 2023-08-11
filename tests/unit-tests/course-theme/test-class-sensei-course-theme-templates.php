<?php
/**
 * This file contains the Sensei_Course_Theme_Templates_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Course_Theme_Templates class.
 *
 * @group course-theme
 */
class Sensei_Course_Theme_Templates_Test extends WP_UnitTestCase {

	public function testCourseThemePatterns_WhenActionNotCalled_DoesNotCreateThePatterns() {
		/* Arrange. */
		$registry = \WP_Block_Patterns_Registry::get_instance();

		/* Assert. */
		self::assertFalse( $registry->is_registered( 'sensei-course-theme/header' ) );
	}

	public function testCourseThemePatterns_WhenActionCalled_CreatesThePatterns() {
		/* Arrange. */
		$registry = \WP_Block_Patterns_Registry::get_instance();

		/* Act. */
		do_action( 'sensei_course_theme_before_templates_load' );

		/* Assert. */
		self::assertTrue( $registry->is_registered( 'sensei-course-theme/header' ) );
	}
}

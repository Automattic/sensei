<?php
/**
 * This file contains the Sensei_Course_Theme_Templates class.
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
	public function testTemplateFilter_WhenCourseThemeEnabled_ReturnsFilteredTemplateList() {
		/* Arrange */
		$templates = [
			(object) [
				'template' => 'template-1.php',
				'id'       => 'test/test',
			],
			(object) [
				'template' => 'template-2.php',
				'id'       => 'course//single-lesson',
			],
		];

		/* Act */
		$filtered_templates = Sensei_Course_Theme_Templates::instance()->filter_single_lesson_template_in_learning_mode( $templates, 'Course' );

		/* Assert */
		$this->assertCount( 1, $filtered_templates );
	}

	public function testTemplateFilter_WhenCourseThemeNotEnabled_ReturnsSameTemplateList() {
		/* Arrange */
		$templates = [
			(object) [
				'template' => 'template-1.php',
				'id'       => 'test/test',
			],
			(object) [
				'template' => 'template-2.php',
				'id'       => 'course//single-lesson',
			],
		];

		/* Act */
		$filtered_templates = Sensei_Course_Theme_Templates::instance()->filter_single_lesson_template_in_learning_mode( $templates, 'Twenty' );

		/* Assert */
		$this->assertCount( 2, $filtered_templates );
	}
}

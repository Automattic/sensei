<?php
namespace SenseiTest\WPML;

use Sensei\WPML\Custom_Fields;

/**
 * Class Custom_Fields_Test
 *
 * @covers \Sensei\WPML\Custom_Fields
 */
class Custom_Fields_Test extends \WP_UnitTestCase {
	public function testUpdateCoursePrerequisiteBeforeCopied_WhenCalled_ReturnsMatchingPrerequisiteForNewCourse() {
		/* Arrange. */
		$custom_fields = new Custom_Fields();

		$language_code_filter = function () {
			return 'a';
		};
		add_filter( 'wpml_element_language_code', $language_code_filter, 10, 0 );

		$object_id_fitler = function () {
			return 4;
		};
		add_filter( 'wpml_object_id', $object_id_fitler, 10, 0 );

		/* Act. */
		$actual = $custom_fields->update_course_prerequisite_before_copied( 1, 2, 3, '_course_prerequisite' );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $language_code_filter );
		remove_filter( 'wpml_object_id', $object_id_fitler );
		$this->assertSame( 4, $actual );
	}

	public function testUpdateLessonCourseBeforeCopied_WhenCalled_ReturnsMatchingCourseForNewLesson() {
		/* Arrange. */
		$custom_fields = new Custom_Fields();

		$language_code_filter = function () {
			return 'a';
		};
		add_filter( 'wpml_element_language_code', $language_code_filter, 10, 0 );

		$object_id_fitler = function () {
			return 4;
		};
		add_filter( 'wpml_object_id', $object_id_fitler, 10, 0 );

		/* Act. */
		$actual = $custom_fields->update_lesson_course_before_copied( 1, 2, 3, '_lesson_course' );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $language_code_filter );
		remove_filter( 'wpml_object_id', $object_id_fitler );

		$this->assertSame( 4, $actual );
	}
}

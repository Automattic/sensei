<?php
namespace SenseiTest\WPML;

use Sensei\WPML\Custom_Fields;
use Sensei_Factory;

/**
 * Class Custom_Fields_Test
 *
 * @covers \Sensei\WPML\Custom_Fields
 */
class Custom_Fields_Test extends \WP_UnitTestCase {
	/**
	 * Sensei Factory.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public function set_up(): void {
		parent::set_up();
		$this->factory = new Sensei_Factory();
	}

	public function tear_down(): void {
		parent::tear_down();
		$this->factory->tearDown();
	}

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

	public function testUpdateQuizIdBeforeCopied_WhenCalled_ReturnsMatchingCourseForNewLesson() {
		/* Arrange. */
		$old_quistion_id = $this->factory->question->create();
		$new_question_id = $this->factory->question->create();

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
		$actual = $custom_fields->update_quiz_id_before_copied( 1, $old_quistion_id, $new_question_id, '_quiz_id' );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $language_code_filter );
		remove_filter( 'wpml_object_id', $object_id_fitler );

		$this->assertSame( 4, $actual );
	}
}

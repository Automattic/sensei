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

		$object_id_filter = function () {
			return 4;
		};
		add_filter( 'wpml_object_id', $object_id_filter, 10, 0 );

		/* Act. */
		$actual = $custom_fields->update_course_prerequisite_before_copied( 1, 2, 3, '_course_prerequisite' );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $language_code_filter );
		remove_filter( 'wpml_object_id', $object_id_filter );
		$this->assertSame( 4, $actual );
	}

	public function testUpdateLessonCourseBeforeCopied_WhenCalled_ReturnsMatchingCourseForNewLesson() {
		/* Arrange. */
		$custom_fields = new Custom_Fields();

		$language_code_filter = function () {
			return 'a';
		};
		add_filter( 'wpml_element_language_code', $language_code_filter, 10, 0 );

		$object_id_filter = function () {
			return 4;
		};
		add_filter( 'wpml_object_id', $object_id_filter, 10, 0 );

		/* Act. */
		$actual = $custom_fields->update_lesson_course_before_copied( 1, 2, 3, '_lesson_course' );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $language_code_filter );
		remove_filter( 'wpml_object_id', $object_id_filter );

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

		$object_id_filter = function () {
			return 4;
		};
		add_filter( 'wpml_object_id', $object_id_filter, 10, 0 );

		/* Act. */
		$actual = $custom_fields->update_quiz_id_before_copied( 1, $old_quistion_id, $new_question_id, '_quiz_id' );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $language_code_filter );
		remove_filter( 'wpml_object_id', $object_id_filter );

		$this->assertSame( 4, $actual );
	}

	public function testUpdateLessonQuizBeforeCopied_QuizWithATranslationGiven_ReturnsTheTranslatedQuiz() {
		/* Arrange. */
		$master_lesson_id     = $this->factory->lesson->create();
		$translated_lesson_id = $this->factory->lesson->create();

		$custom_fields = new Custom_Fields();

		$language_code_filter = function () {
			return 'es';
		};
		add_filter( 'wpml_element_language_code', $language_code_filter, 10, 0 );

		$object_id_filter = function () {
			return 7;
		};
		add_filter( 'wpml_object_id', $object_id_filter, 10, 0 );

		/* Act. */
		$actual = $custom_fields->update_lesson_quiz_before_copied( 5, $master_lesson_id, $translated_lesson_id, '_lesson_quiz' );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $language_code_filter );
		remove_filter( 'wpml_object_id', $object_id_filter );

		$this->assertSame( 7, $actual );
	}

	public function testUpdateLessonQuizBeforeCopied_QuizWithoutATranslationGiven_KeepsTheOriginalQuiz() {
		/* Arrange. */
		$master_lesson_id     = $this->factory->lesson->create();
		$translated_lesson_id = $this->factory->lesson->create();

		$custom_fields = new Custom_Fields();

		$language_code_filter = function () {
			return 'es';
		};
		add_filter( 'wpml_element_language_code', $language_code_filter, 10, 0 );

		// WPML returns the original ID only when asked to, and null otherwise.
		$object_id_filter = function ( $object_id, $element_type, $return_original_if_missing ) {
			return $return_original_if_missing ? $object_id : null;
		};
		add_filter( 'wpml_object_id', $object_id_filter, 10, 3 );

		/* Act. */
		$actual = $custom_fields->update_lesson_quiz_before_copied( 5, $master_lesson_id, $translated_lesson_id, '_lesson_quiz' );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $language_code_filter );
		remove_filter( 'wpml_object_id', $object_id_filter );

		$this->assertSame( 5, $actual, 'Without a quiz translation the meta must keep the original quiz, which is what the lesson falls back to.' );
	}

	public function testUpdateQuizLessonBeforeCopied_LessonWithATranslationGiven_ReturnsTheTranslatedLesson() {
		/* Arrange. */
		$master_quiz_id     = $this->factory->quiz->create();
		$translated_quiz_id = $this->factory->quiz->create();

		$custom_fields = new Custom_Fields();

		$language_code_filter = function () {
			return 'es';
		};
		add_filter( 'wpml_element_language_code', $language_code_filter, 10, 0 );

		$object_id_filter = function () {
			return 9;
		};
		add_filter( 'wpml_object_id', $object_id_filter, 10, 0 );

		/* Act. */
		$actual = $custom_fields->update_quiz_lesson_before_copied( 3, $master_quiz_id, $translated_quiz_id, '_quiz_lesson' );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $language_code_filter );
		remove_filter( 'wpml_object_id', $object_id_filter );

		$this->assertSame( 9, $actual );
	}

	public function testUpdateLessonPrerequisiteBeforeCopied_WhenCalled_ReturnsMatchingPrerequisiteForNewLesson() {
		/* Arrange. */
		$custom_fields = new Custom_Fields();

		$language_code_filter = function () {
			return 'a';
		};
		add_filter( 'wpml_element_language_code', $language_code_filter, 10, 0 );

		$object_id_filter = function () {
			return 4;
		};
		add_filter( 'wpml_object_id', $object_id_filter, 10, 0 );

		/* Act. */
		$actual = $custom_fields->update_lesson_prerequisite_before_copied( 1, 2, 3, '_lesson_prerequisite' );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $language_code_filter );
		remove_filter( 'wpml_object_id', $object_id_filter );

		$this->assertSame( 4, $actual );
	}

	public function testUpdateCourseWooCommerceProductBeforeCopied_WhenCalled_ReturnsMatchingCourseForNewLesson() {
		/* Arrange. */
		$custom_fields = new Custom_Fields();

		$language_code_filter = function () {
			return 'a';
		};
		add_filter( 'wpml_element_language_code', $language_code_filter, 10, 0 );

		$object_id_filter = function () {
			return 4;
		};
		add_filter( 'wpml_object_id', $object_id_filter, 10, 0 );

		/* Act. */
		$actual = $custom_fields->update_course_woocommerce_product_before_copied( 1, 2, 3, '_course_woocommerce_product' );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $language_code_filter );
		remove_filter( 'wpml_object_id', $object_id_filter );

		$this->assertSame( 4, $actual );
	}
}

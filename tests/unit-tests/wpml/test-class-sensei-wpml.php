<?php

namespace SenseiTest\WPML;

use Sensei_Factory;
use Sensei_WPML;

/**
* Class Sensei_WPML_Test.
*
* @covers \Sensei_WPML
*/
class Sensei_WPML_Test extends \WP_UnitTestCase {

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

	public function testSetLanguageDetailsWhenLessonCreated_WhenCalled_AppliesWpmlElementLanguageCodeFilter() {
		/* Arrange. */
		$wpml = new Sensei_WPML();

		$filter_applied  = false;
		$filter_function = function ( $language_code ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $language_code;
		};

		add_filter( 'wpml_element_language_code', $filter_function, 10, 1 );

		/* Act. */
		$wpml->set_language_details_when_lesson_created( 1, 2 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}

	public function testSetLanguageDetailsWhenLessonCreated_WhenCalled_AppliesWpmlCurrentLangugeFilter() {
		/* Arrange. */
		$wpml = new Sensei_WPML();

		$filter_language_code_function = function () {
			return null;
		};
		add_filter( 'wpml_element_language_code', $filter_language_code_function, 10, 0 );

		$filter_applied  = false;
		$filter_function = function ( $language_code ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $language_code;
		};
		add_filter( 'wpml_current_language', $filter_function, 10, 1 );

		/* Act. */
		$wpml->set_language_details_when_lesson_created( 1, 2 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $filter_language_code_function, 10 );
		remove_filter( 'wpml_current_language', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}

	public function testSetLanguageDetailsWhenLessonCreated_WhenCalled_AppliesWpmlSetElementLanguageDetails() {
		/* Arrange. */
		$wpml = new Sensei_WPML();

		$filter_applied  = false;
		$filter_function = function ( $data ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $data;
		};

		add_filter( 'wpml_set_element_language_details', $filter_function, 10, 1 );

		/* Act. */
		$wpml->set_language_details_when_lesson_created( 1, 2 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_set_element_language_details', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}

	public function testSetLanguageDetailsWhenQuizCreated_WhenCalled_AppliesWpmlElementLanguageCodeFilter() {
		/* Arrange. */
		$wpml = new Sensei_WPML();

		$filter_applied  = false;
		$filter_function = function ( $language_code ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $language_code;
		};

		add_filter( 'wpml_element_language_code', $filter_function, 10, 1 );

		/* Act. */
		$wpml->set_language_details_when_quiz_created( 1, 2 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}

	public function testSetLanguageDetailsWhenQuizCreated_WhenCalled_AppliesWpmlCurrentLangugeFilter() {
		/* Arrange. */
		$wpml = new Sensei_WPML();

		$filter_language_code_function = function () {
			return null;
		};
		add_filter( 'wpml_element_language_code', $filter_language_code_function, 10, 0 );

		$filter_applied  = false;
		$filter_function = function ( $language_code ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $language_code;
		};
		add_filter( 'wpml_current_language', $filter_function, 10, 1 );

		/* Act. */
		$wpml->set_language_details_when_quiz_created( 1, 2 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $filter_language_code_function, 10 );
		remove_filter( 'wpml_current_language', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}

	public function testSetLanguageDetailsWhenQuizCreated_WhenCalled_AppliesWpmlSetElementLanguageDetails() {
		/* Arrange. */
		$wpml = new Sensei_WPML();

		$filter_applied  = false;
		$filter_function = function ( $data ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $data;
		};

		add_filter( 'wpml_set_element_language_details', $filter_function, 10, 1 );

		/* Act. */
		$wpml->set_language_details_when_quiz_created( 1, 2 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_set_element_language_details', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}

	public function testUpdateCoursePrerequisiteBeforeCopied_WhenCalled_ReturnsMatchingPrerequisiteForNewCourse() {
		/* Arrange. */
		$wpml = new Sensei_WPML();

		$language_code_filter = function () {
			return 'a';
		};
		add_filter( 'wpml_element_language_code', $language_code_filter, 10, 0 );

		$object_id_fitler = function () {
			return 4;
		};
		add_filter( 'wpml_object_id', $object_id_fitler, 10, 0 );

		/* Act. */
		$actual = $wpml->update_course_prerequisite_before_copied( 1, 2, 3, '_course_prerequisite' );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $language_code_filter );
		remove_filter( 'wpml_object_id', $object_id_fitler );
		$this->assertSame( 4, $actual );
	}

	public function testUpdateLessonCourseBeforeCopied_WhenCalled_ReturnsMatchingCourseForNewLesson() {
		/* Arrange. */
		$wpml = new Sensei_WPML();

		$language_code_filter = function () {
			return 'a';
		};
		add_filter( 'wpml_element_language_code', $language_code_filter, 10, 0 );

		$object_id_fitler = function () {
			return 4;
		};
		add_filter( 'wpml_object_id', $object_id_fitler, 10, 0 );

		/* Act. */
		$actual = $wpml->update_lesson_course_before_copied( 1, 2, 3, '_lesson_course' );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_code', $language_code_filter );
		remove_filter( 'wpml_object_id', $object_id_fitler );

		$this->assertSame( 4, $actual );
	}

	public function testUpdateLessonPropertiesOnCourseTranslationCreated_WhenCalled_CreatesLessonTranslations() {
		/* Arrange. */
		$new_course_id  = $this->factory->course->create();
		$new_lesson1_id = $this->factory->lesson->create();
		$new_lesson2_id = $this->factory->lesson->create();
		$old_course     = $this->factory->get_course_with_lessons( array( 'lesson_count' => 2 ) );

		$wpml = new Sensei_WPML();

		$element_language_details_filter = function () {
			return array(
				'language_code'        => 'a',
				'source_language_code' => 'c',
			);
		};
		add_filter( 'wpml_element_language_details', $element_language_details_filter, 10, 2 );

		$object_id_fitler = function ( $object_id, $element_type ) use ( $new_course_id, $old_course ) {
			if ( $new_course_id === $object_id && 'course' === $element_type ) {
				return $old_course['course_id'];
			}

			return 0;
		};
		add_filter( 'wpml_object_id', $object_id_fitler, 10, 2 );

		$element_has_translations_filter = function () {
			return false;
		};
		add_filter( 'wpml_element_has_translations', $element_has_translations_filter, 10, 0 );

		$created_duplicates               = 0;
		$admin_make_post_duplicates_acton = function ( $post_id ) use ( &$created_duplicates, $old_course ) {
			if ( in_array( $post_id, $old_course['lesson_ids'], true ) ) {
				++$created_duplicates;
			}
		};
		add_action( 'wpml_admin_make_post_duplicates', $admin_make_post_duplicates_acton, 10, 0 );

		$new_lesson_ids         = array( $new_lesson1_id, $new_lesson2_id );
		$post_duplicates_filter = function ( $post_id ) use ( &$new_lesson_ids, $old_course ) {
			if ( in_array( $post_id, $old_course['lesson_ids'], true ) ) {
				$lesson_id = array_shift( $new_lesson_ids );
				return array(
					'a' => $lesson_id,
				);
			}

			return array();
		};
		add_filter( 'wpml_post_duplicates', $post_duplicates_filter, 10, 0 );

		/* Act. */
		$wpml->update_lesson_properties_on_course_translation_created( $new_course_id );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_details', $element_language_details_filter );
		remove_filter( 'wpml_object_id', $object_id_fitler );
		remove_filter( 'wpml_element_has_translations', $element_has_translations_filter );
		remove_action( 'wpml_admin_make_post_duplicates', $admin_make_post_duplicates_acton );
		remove_filter( 'wpml_post_duplicates', $post_duplicates_filter );

		$this->assertSame( 2, $created_duplicates );

		$expected = array( $new_course_id, $new_course_id );
		$actual   = array(
			(int) get_post_meta( $new_lesson1_id, '_lesson_course', true ),
			(int) get_post_meta( $new_lesson2_id, '_lesson_course', true ),
		);
		$this->assertSame( $expected, $actual, 'Lesson course should be set to the new course in lesson translations' );
	}

	public function testSetLanguageDetailsWhenQuestionCreated_WhenCalled_AppliesWpmlCurrentLanguageFilter() {
		/* Arrange. */
		$wpml = new Sensei_WPML();

		$filter_applied  = false;
		$filter_function = function () use ( &$filter_applied ) {
			$filter_applied = true;
			return 'a';
		};

		add_filter( 'wpml_current_language', $filter_function, 10, 0 );

		/* Act. */
		$wpml->set_language_details_when_question_created( 1 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_current_language', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}

	public function testSetLanguageDetailsWhenQuestionCreated_WhenCalled_AppliesWpmlSetElementLanguageDetails() {
		/* Arrange. */
		$wpml = new Sensei_WPML();

		$filter_applied  = false;
		$filter_function = function ( $data ) use ( &$filter_applied ) {
			$filter_applied = true;
			return $data;
		};

		add_filter( 'wpml_set_element_language_details', $filter_function, 10, 1 );

		/* Act. */
		$wpml->set_language_details_when_question_created( 1 );

		/* Clean up & Assert. */
		remove_filter( 'wpml_set_element_language_details', $filter_function, 10 );

		$this->assertTrue( $filter_applied );
	}


	public function testUpdateLessonTranslationsOnLessonTranslationCreated_WhenCalled_CreatesLessonTranslations() {
		/* Arrange. */
		$new_lesson_id = $this->factory->lesson->create();
		$old_lesson    = $this->factory->lesson->create();

		$wpml = new Sensei_WPML();

		$element_language_details_filter = function () {
			return array(
				'language_code'        => 'a',
				'source_language_code' => 'c',
			);
		};
		add_filter( 'wpml_element_language_details', $element_language_details_filter, 10, 0 );

		$object_id_fitler = function ( $object_id, $element_type ) use ( $new_lesson_id, $old_lesson ) {
			if ( $new_lesson_id === $object_id && 'lesson' === $element_type ) {
				return $old_lesson;
			}

			return 0;
		};
		add_filter( 'wpml_object_id', $object_id_fitler, 10, 2 );

		$element_has_translations_filter = function () {
			return false;
		};
		add_filter( 'wpml_element_has_translations', $element_has_translations_filter, 10, 0 );

		$admin_make_post_duplicates_acton = function ( $post_id ) use ( &$created_duplicates, $old_lesson ) {
			if ( $post_id === $old_lesson ) {
				++$created_duplicates;
			}
		};

		add_action( 'wpml_admin_make_post_duplicates', $admin_make_post_duplicates_acton, 10, 1 );

		$post_duplicates_filter = function ( $post_id ) use ( $new_lesson_id, $old_lesson ) {
			if ( $post_id === $old_lesson ) {
				return array(
					'a' => $new_lesson_id,
				);
			}

			return array();
		};

		add_filter( 'wpml_post_duplicates', $post_duplicates_filter, 10, 1 );

		/* Act. */
		$wpml->update_lesson_translations_on_lesson_translation_created( $new_lesson_id );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_details', $element_language_details_filter );
		remove_filter( 'wpml_object_id', $object_id_fitler );
		remove_filter( 'wpml_element_has_translations', $element_has_translations_filter );
		remove_action( 'wpml_admin_make_post_duplicates', $admin_make_post_duplicates_acton );
		remove_filter( 'wpml_post_duplicates', $post_duplicates_filter );

		$this->assertSame( 1, $created_duplicates );

		$expected = $new_lesson_id;
		$actual   = (int) get_post_meta( $new_lesson_id, '_lesson_course', true );
		$this->assertSame( $expected, $actual, 'Lesson course should be set to the new course in lesson translations' );
	}
}

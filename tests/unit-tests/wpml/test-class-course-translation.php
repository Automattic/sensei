<?php
namespace SenseiTest\WPML;

use Sensei\WPML\Course_Translation;
use Sensei_Factory;

/**
 * Class Course_Translation_Test
 *
 * @covers \Sensei\WPML\Course_Translation
 */
class Course_Translation_Test extends \WP_UnitTestCase {
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

	public function testUpdateLessonPropertiesOnCourseTranslationCreated_WhenCalled_CreatesLessonTranslations() {
		/* Arrange. */
		$new_course_id  = $this->factory->course->create();
		$new_lesson1_id = $this->factory->lesson->create();
		$new_lesson2_id = $this->factory->lesson->create();
		$old_course     = $this->factory->get_course_with_lessons( array( 'lesson_count' => 2 ) );

		$course_translation = new Course_Translation();

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

		$get_element_translations_filter = function () {
			return array();
		};
		add_filter( 'wpml_get_element_translations', $get_element_translations_filter, 10, 0 );

		$created_duplicates           = 0;
		$new_lessons_for_copy         = array( $new_lesson1_id, $new_lesson2_id );
		$copy_post_to_language_filter = function ( $post_id ) use ( &$created_duplicates, $old_course, &$new_lessons_for_copy ) {
			if ( in_array( $post_id, $old_course['lesson_ids'], true ) ) {
				++$created_duplicates;
			}
			return array_shift( $new_lessons_for_copy );
		};
		add_filter( 'wpml_copy_post_to_language', $copy_post_to_language_filter );

		$new_lessons_for_duplicates = array( $new_lesson1_id, $new_lesson2_id );
		$post_duplicates_filter     = function ( $post_id ) use ( &$new_lessons_for_duplicates, $old_course ) {
			if ( in_array( $post_id, $old_course['lesson_ids'], true ) ) {
				$lesson_id = array_shift( $new_lessons_for_duplicates );
				return array(
					'a' => $lesson_id,
				);
			}

			return array();
		};
		add_filter( 'wpml_post_duplicates', $post_duplicates_filter );

		/* Act. */
		$course_translation->update_lesson_properties_on_course_translation_created( $new_course_id );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_details', $element_language_details_filter );
		remove_filter( 'wpml_object_id', $object_id_fitler );
		remove_filter( 'wpml_get_element_translations', $get_element_translations_filter );
		remove_filter( 'wpml_copy_post_to_language', $copy_post_to_language_filter );
		remove_filter( 'wpml_post_duplicates', $post_duplicates_filter );

		$this->assertSame( 2, $created_duplicates );

		$expected = array( $new_course_id, $new_course_id );
		$actual   = array(
			(int) get_post_meta( $new_lesson1_id, '_lesson_course', true ),
			(int) get_post_meta( $new_lesson2_id, '_lesson_course', true ),
		);
		$this->assertSame( $expected, $actual, 'Lesson course should be set to the new course in lesson translations' );
	}

	public function testUpdateLessonPropertiesOnCourseTranslationCreated_TranslatedLessonsWithoutOrderMetaGiven_MirrorsMasterLessonOrder() {
		/* Arrange. */
		$master_course_id     = $this->factory->course->create();
		$translated_course_id = $this->factory->course->create();

		// A master course whose lessons were never explicitly reordered: no
		// order metas, so its display order comes from the post dates.
		$master_lesson_ids = $this->create_course_lessons(
			$master_course_id,
			array( '2024-01-11 10:00:00', '2024-01-12 10:00:00', '2024-01-13 10:00:00' )
		);
		// The translations were created in the reverse order, so their date
		// fallback disagrees with the master order.
		$translated_lesson_ids = $this->create_course_lessons(
			$translated_course_id,
			array( '2024-02-13 10:00:00', '2024-02-12 10:00:00', '2024-02-11 10:00:00' )
		);

		$counterparts = array_combine( $master_lesson_ids, $translated_lesson_ids )
			+ array( $master_course_id => $translated_course_id );
		$id_map       = array();
		foreach ( $counterparts as $original_id => $counterpart_id ) {
			$id_map[ $original_id ]    = $counterpart_id;
			$id_map[ $counterpart_id ] = $original_id;
		}

		add_filter(
			'wpml_element_language_details',
			function () {
				return array(
					'language_code'        => 'es',
					'source_language_code' => 'en',
				);
			},
			10,
			0
		);
		add_filter(
			'wpml_object_id',
			function ( $object_id, $element_type ) use ( $id_map ) {
				$counterpart_id = $id_map[ $object_id ] ?? 0;
				return $counterpart_id && get_post_type( $counterpart_id ) === $element_type ? $counterpart_id : 0;
			},
			10,
			2
		);
		// Every master lesson already has a translation...
		add_filter( 'wpml_element_trid', fn() => 1 );
		add_filter( 'wpml_get_element_translations', fn() => array( 'es' => true ), 10, 0 );
		// ...delivered as a WPML duplicate.
		add_filter(
			'wpml_post_duplicates',
			function ( $post_id ) use ( $counterparts ) {
				return isset( $counterparts[ $post_id ] ) ? array( 'es' => $counterparts[ $post_id ] ) : array();
			},
			10,
			1
		);

		$course_translation = new Course_Translation();

		/* Act. */
		$course_translation->update_lesson_properties_on_course_translation_created( $translated_course_id );

		/* Clean up & Assert. */
		remove_all_filters( 'wpml_element_language_details' );
		remove_all_filters( 'wpml_object_id' );
		remove_all_filters( 'wpml_element_trid' );
		remove_all_filters( 'wpml_get_element_translations' );
		remove_all_filters( 'wpml_post_duplicates' );

		$this->assertSame( $master_lesson_ids, array_map( fn( $id ) => $id_map[ $id ], Sensei()->course->course_lessons( $translated_course_id, 'any', 'ids' ) ) );
	}

	/**
	 * Create lessons attached to a course, one per post date, in the given
	 * order, without `_order_{course_id}` metas.
	 *
	 * @param int      $course_id  Course the lessons belong to.
	 * @param string[] $post_dates One lesson is created per date.
	 *
	 * @return int[] Lesson IDs, in the order given.
	 */
	private function create_course_lessons( $course_id, $post_dates ) {
		$lesson_ids = array();
		foreach ( $post_dates as $post_date ) {
			$lesson_ids[] = $this->factory->lesson->create(
				array(
					'post_date'  => $post_date,
					'meta_input' => array( '_lesson_course' => $course_id ),
				)
			);
		}

		return $lesson_ids;
	}
}

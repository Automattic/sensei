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

	public function testTranslateOutlineLessonIdsOnCourseTranslationCreated_OutlineWithSourceLessonIdsGiven_RewritesThemToTheCourseLanguage() {
		/* Arrange. */
		$source_lesson_id     = $this->factory->lesson->create();
		$translated_lesson_id = $this->factory->lesson->create();
		$course_id            = $this->factory->course->create(
			array( 'post_content' => $this->outline_content( array( array( $source_lesson_id, 'Titulo ES' ) ) ) )
		);

		$this->stub_course_language( 'es', 'en' );
		$this->stub_object_id_map( array( $source_lesson_id => $translated_lesson_id ) );

		$course_translation = new Course_Translation();

		/* Act. */
		$course_translation->translate_outline_lesson_ids_on_course_translation_created( $course_id );

		/* Clean up & Assert. */
		$this->remove_wpml_stubs();
		$content = get_post( $course_id )->post_content;
		$this->assertStringContainsString( '"id":' . $translated_lesson_id, $content, 'The outline should reference the lesson translation in the course language.' );
	}

	public function testTranslateOutlineLessonIdsOnCourseTranslationCreated_OutlineWithSourceLessonIdsGiven_KeepsTheSubmittedTitle() {
		/* Arrange. */
		$source_lesson_id     = $this->factory->lesson->create();
		$translated_lesson_id = $this->factory->lesson->create();
		$course_id            = $this->factory->course->create(
			array( 'post_content' => $this->outline_content( array( array( $source_lesson_id, 'Titulo ES' ) ) ) )
		);

		$this->stub_course_language( 'es', 'en' );
		$this->stub_object_id_map( array( $source_lesson_id => $translated_lesson_id ) );

		$course_translation = new Course_Translation();

		/* Act. */
		$course_translation->translate_outline_lesson_ids_on_course_translation_created( $course_id );

		/* Clean up & Assert. */
		$this->remove_wpml_stubs();
		$this->assertStringContainsString( 'Titulo ES', get_post( $course_id )->post_content, 'The delivered title should stay with the remapped outline item.' );
	}

	public function testTranslateOutlineLessonIdsOnCourseTranslationCreated_LessonWithoutTranslationGiven_DropsTheOutlineItem() {
		/* Arrange. */
		$source_lesson_id = $this->factory->lesson->create();
		$course_id        = $this->factory->course->create(
			array( 'post_content' => $this->outline_content( array( array( $source_lesson_id, 'Sin traduccion' ) ) ) )
		);

		$this->stub_course_language( 'es', 'en' );
		$this->stub_object_id_map( array() );

		$course_translation = new Course_Translation();

		/* Act. */
		$course_translation->translate_outline_lesson_ids_on_course_translation_created( $course_id );

		/* Clean up & Assert. */
		$this->remove_wpml_stubs();
		$this->assertStringNotContainsString( '"id":' . $source_lesson_id, get_post( $course_id )->post_content, 'An outline item without a translation in the course language should be dropped.' );
	}

	public function testTranslateOutlineLessonIdsOnCourseTranslationCreated_LessonAlreadyInCourseLanguageGiven_LeavesTheContentUntouched() {
		/* Arrange. */
		$own_lesson_id = $this->factory->lesson->create();
		$content       = $this->outline_content( array( array( $own_lesson_id, 'Propia' ) ) );
		$course_id     = $this->factory->course->create( array( 'post_content' => $content ) );

		$this->stub_course_language( 'es', 'en' );
		$this->stub_object_id_map( array( $own_lesson_id => $own_lesson_id ) );

		$course_translation = new Course_Translation();

		/* Act. */
		$course_translation->translate_outline_lesson_ids_on_course_translation_created( $course_id );

		/* Clean up & Assert. */
		$this->remove_wpml_stubs();
		$this->assertSame( $content, get_post( $course_id )->post_content, 'A lesson already in the course language should leave the content untouched.' );
	}

	public function testTranslateOutlineLessonIdsOnCourseTranslationCreated_ContentWithoutAnOutlineGiven_LeavesTheContentUntouched() {
		/* Arrange. */
		$content   = '<!-- wp:paragraph --><p>Course description.</p><!-- /wp:paragraph -->';
		$course_id = $this->factory->course->create( array( 'post_content' => $content ) );

		$this->stub_course_language( 'es', 'en' );
		$this->stub_object_id_map( array() );

		$course_translation = new Course_Translation();

		/* Act. */
		$course_translation->translate_outline_lesson_ids_on_course_translation_created( $course_id );

		/* Clean up & Assert. */
		$this->remove_wpml_stubs();
		$this->assertSame( $content, get_post( $course_id )->post_content, 'Content without an outline should not be touched.' );
	}

	public function testTranslateOutlineLessonIdsOnCourseTranslationCreated_ModuleWithNestedSourceLessonGiven_RewritesTheNestedLessonId() {
		/* Arrange. */
		$source_lesson_id     = $this->factory->lesson->create();
		$translated_lesson_id = $this->factory->lesson->create();
		$content              = '<!-- wp:sensei-lms/course-outline --><!-- wp:sensei-lms/course-outline-module {"id":7,"title":"Modulo"} --><!-- wp:sensei-lms/course-outline-lesson {"id":' . $source_lesson_id . ',"title":"Anidada"} /--><!-- /wp:sensei-lms/course-outline-module --><!-- /wp:sensei-lms/course-outline -->';
		$course_id            = $this->factory->course->create( array( 'post_content' => $content ) );

		$this->stub_course_language( 'es', 'en' );
		$this->stub_object_id_map( array( $source_lesson_id => $translated_lesson_id ) );

		$course_translation = new Course_Translation();

		/* Act. */
		$course_translation->translate_outline_lesson_ids_on_course_translation_created( $course_id );

		/* Clean up & Assert. */
		$this->remove_wpml_stubs();
		$this->assertStringContainsString( '"id":' . $translated_lesson_id, get_post( $course_id )->post_content, 'A lesson nested in a module should be remapped too.' );
	}

	public function testTranslateOutlineLessonIdsOnCourseTranslationCreated_TitleWithEscapedHtmlGiven_KeepsTheAttributeEncoding() {
		/* Arrange. */
		$source_lesson_id     = $this->factory->lesson->create();
		$translated_lesson_id = $this->factory->lesson->create();
		$title                = 'Lección <br/>escapada';
		$content              = '<!-- wp:sensei-lms/course-outline --><!-- wp:sensei-lms/course-outline-lesson ' . serialize_block_attributes(
			array(
				'id'    => $source_lesson_id,
				'title' => $title,
			)
		) . ' /--><!-- /wp:sensei-lms/course-outline -->';
		$course_id            = $this->factory->course->create( array( 'post_content' => wp_slash( $content ) ) );

		$this->stub_course_language( 'es', 'en' );
		$this->stub_object_id_map( array( $source_lesson_id => $translated_lesson_id ) );

		$course_translation = new Course_Translation();

		/* Act. */
		$course_translation->translate_outline_lesson_ids_on_course_translation_created( $course_id );

		/* Clean up & Assert. */
		$this->remove_wpml_stubs();
		$this->assertStringContainsString( '\u003cbr', get_post( $course_id )->post_content, 'The escaped attribute encoding should survive the remap round trip.' );
	}

	public function testTranslateOutlineLessonIdsOnCourseTranslationCreated_DeliveredTitleGiven_RenamesTheLessonTranslation() {
		/* Arrange. */
		$source_lesson_id     = $this->factory->lesson->create();
		$translated_lesson_id = $this->factory->lesson->create( array( 'post_title' => 'Source title' ) );
		$course_id            = $this->factory->course->create(
			array( 'post_content' => $this->outline_content( array( array( $source_lesson_id, 'Titulo entregado' ) ) ) )
		);

		$this->stub_course_language( 'es', 'en' );
		$this->stub_object_id_map( array( $source_lesson_id => $translated_lesson_id ) );

		$course_translation = new Course_Translation();

		/* Act. */
		$course_translation->translate_outline_lesson_ids_on_course_translation_created( $course_id );

		/* Clean up & Assert. */
		$this->remove_wpml_stubs();
		$this->assertSame( 'Titulo entregado', get_post( $translated_lesson_id )->post_title, 'The delivered outline title should land on the lesson translation.' );
	}

	public function testTranslateOutlineLessonIdsOnCourseTranslationCreated_DeliveredTitleAlreadyApplied_DoesNotRewriteTheLessonAgain() {
		/* Arrange. */
		$source_lesson_id     = $this->factory->lesson->create();
		$translated_lesson_id = $this->factory->lesson->create();
		$course_id            = $this->factory->course->create(
			array( 'post_content' => $this->outline_content( array( array( $source_lesson_id, 'Titulo especial' ) ) ) )
		);

		$this->stub_course_language( 'es', 'en' );
		$this->stub_object_id_map(
			array(
				$source_lesson_id     => $translated_lesson_id,
				$translated_lesson_id => $translated_lesson_id,
			)
		);

		$course_translation = new Course_Translation();
		$course_translation->translate_outline_lesson_ids_on_course_translation_created( $course_id );

		// Display filters (wptexturize, plugins) must not defeat the comparison.
		$title_filter = function ( $title ) {
			return $title . ' [filtered]';
		};
		add_filter( 'the_title', $title_filter );

		$lesson_updates = 0;
		$count_updates  = function ( $post_id ) use ( $translated_lesson_id, &$lesson_updates ) {
			if ( $post_id === $translated_lesson_id ) {
				++$lesson_updates;
			}
		};
		add_action( 'post_updated', $count_updates );

		/* Act: a re-delivery runs the handler again with the same titles. */
		$course_translation->translate_outline_lesson_ids_on_course_translation_created( $course_id );

		/* Clean up & Assert. */
		remove_action( 'post_updated', $count_updates );
		remove_filter( 'the_title', $title_filter );
		$this->remove_wpml_stubs();
		$this->assertSame( 0, $lesson_updates, 'A re-delivery with an unchanged title should not rewrite the lesson.' );
	}

	public function testTranslateOutlineLessonIdsOnCourseTranslationCreated_OutlineNestedInAWrapperBlockGiven_RewritesItsLessonIds() {
		/* Arrange. */
		$source_lesson_id     = $this->factory->lesson->create();
		$translated_lesson_id = $this->factory->lesson->create();
		$content              = '<!-- wp:group --><div class="wp-block-group">' . $this->outline_content( array( array( $source_lesson_id, 'Anidado' ) ) ) . '</div><!-- /wp:group -->';
		$course_id            = $this->factory->course->create( array( 'post_content' => $content ) );

		$this->stub_course_language( 'es', 'en' );
		$this->stub_object_id_map( array( $source_lesson_id => $translated_lesson_id ) );

		$course_translation = new Course_Translation();

		/* Act. */
		$course_translation->translate_outline_lesson_ids_on_course_translation_created( $course_id );

		/* Clean up & Assert. */
		$this->remove_wpml_stubs();
		$this->assertStringContainsString( '"id":' . $translated_lesson_id, get_post( $course_id )->post_content, 'An outline nested inside a wrapper block should be remapped too.' );
	}

	public function testTranslateOutlineLessonIdsOnCourseTranslationCreated_ModuleWithATranslationGiven_RewritesTheModuleId() {
		/* Arrange. */
		$content   = '<!-- wp:sensei-lms/course-outline --><!-- wp:sensei-lms/course-outline-module {"id":91,"title":"Modulo"} --><!-- /wp:sensei-lms/course-outline-module --><!-- /wp:sensei-lms/course-outline -->';
		$course_id = $this->factory->course->create( array( 'post_content' => $content ) );

		$this->stub_course_language( 'es', 'en' );
		$this->stub_object_id_map( array( 91 => 95 ) );

		$course_translation = new Course_Translation();

		/* Act. */
		$course_translation->translate_outline_lesson_ids_on_course_translation_created( $course_id );

		/* Clean up & Assert. */
		$this->remove_wpml_stubs();
		$this->assertStringContainsString( '"id":95', get_post( $course_id )->post_content, 'A module with a translation in the course language should be remapped to it.' );
	}

	public function testTranslateOutlineLessonIdsOnCourseTranslationCreated_ModuleWithoutATranslationGiven_DropsItsIdAndSlug() {
		/* Arrange. */
		$source_lesson_id     = $this->factory->lesson->create();
		$translated_lesson_id = $this->factory->lesson->create();
		$content              = '<!-- wp:sensei-lms/course-outline --><!-- wp:sensei-lms/course-outline-module {"id":91,"slug":"modulo","title":"Modulo"} --><!-- wp:sensei-lms/course-outline-lesson {"id":' . $source_lesson_id . ',"title":"Anidada"} /--><!-- /wp:sensei-lms/course-outline-module --><!-- /wp:sensei-lms/course-outline -->';
		$course_id            = $this->factory->course->create( array( 'post_content' => $content ) );

		$this->stub_course_language( 'es', 'en' );
		$this->stub_object_id_map( array( $source_lesson_id => $translated_lesson_id ) );

		$course_translation = new Course_Translation();

		/* Act. */
		$course_translation->translate_outline_lesson_ids_on_course_translation_created( $course_id );

		/* Clean up & Assert. */
		$this->remove_wpml_stubs();
		$module_attrs = parse_blocks( get_post( $course_id )->post_content )[0]['innerBlocks'][0]['attrs'];
		$this->assertSame( array( 'title' => 'Modulo' ), $module_attrs, 'A module without a translation should keep its title but lose its source term id and slug.' );
	}

	public function testTranslateOutlineLessonIdsOnCourseTranslationCreated_ModuleWithATranslationGiven_SyncsTheModuleSlug() {
		/* Arrange. */
		$translated_term = wp_insert_term( 'El modulo', 'module' );
		$content         = '<!-- wp:sensei-lms/course-outline --><!-- wp:sensei-lms/course-outline-module {"id":91,"slug":"the-module","title":"El modulo"} --><!-- /wp:sensei-lms/course-outline-module --><!-- /wp:sensei-lms/course-outline -->';
		$course_id       = $this->factory->course->create( array( 'post_content' => $content ) );

		$this->stub_course_language( 'es', 'en' );
		$this->stub_object_id_map( array( 91 => $translated_term['term_id'] ) );

		$course_translation = new Course_Translation();

		/* Act. */
		$course_translation->translate_outline_lesson_ids_on_course_translation_created( $course_id );

		/* Clean up & Assert. */
		$this->remove_wpml_stubs();
		$module_attrs = parse_blocks( get_post( $course_id )->post_content )[0]['innerBlocks'][0]['attrs'];
		$this->assertSame( 'el-modulo', $module_attrs['slug'], 'The remapped module should carry the translated term slug, not the source one.' );
	}

	public function testTranslateOutlineLessonIdsOnCourseTranslationCreated_CourseWithoutASourceLanguageGiven_LeavesTheContentUntouched() {
		/* Arrange. */
		$source_lesson_id = $this->factory->lesson->create();
		$content          = $this->outline_content( array( array( $source_lesson_id, 'Original' ) ) );
		$course_id        = $this->factory->course->create( array( 'post_content' => $content ) );

		$this->stub_course_language( 'en', '' );
		$this->stub_object_id_map( array() );

		$course_translation = new Course_Translation();

		/* Act. */
		$course_translation->translate_outline_lesson_ids_on_course_translation_created( $course_id );

		/* Clean up & Assert. */
		$this->remove_wpml_stubs();
		$this->assertSame( $content, get_post( $course_id )->post_content, 'A course that is not a translation should not have its outline touched.' );
	}

	public function testTranslateOutlineLessonIdsOnCourseDuplicated_DuplicateWithSourceTitlesGiven_LeavesTheLessonTitleAlone() {
		/* Arrange. */
		$source_lesson_id     = $this->factory->lesson->create();
		$translated_lesson_id = $this->factory->lesson->create( array( 'post_title' => 'Titulo traducido' ) );
		$duplicate_course_id  = $this->factory->course->create(
			array( 'post_content' => $this->outline_content( array( array( $source_lesson_id, 'Source title' ) ) ) )
		);

		$this->stub_object_id_map( array( $source_lesson_id => $translated_lesson_id ) );

		$course_translation = new Course_Translation();

		/* Act. */
		$course_translation->translate_outline_lesson_ids_on_course_duplicated( 123, 'es', array(), $duplicate_course_id );

		/* Clean up & Assert. */
		$this->remove_wpml_stubs();
		$this->assertSame( 'Titulo traducido', get_post( $translated_lesson_id )->post_title, 'A duplicate carries source-language titles, so it must not rename the lesson translation.' );
	}

	public function testTranslateOutlineLessonIdsOnCourseDuplicated_DuplicateWithSourceLessonIdsGiven_RewritesThemToTheDuplicateLanguage() {
		/* Arrange. */
		$source_lesson_id     = $this->factory->lesson->create();
		$translated_lesson_id = $this->factory->lesson->create();
		$duplicate_course_id  = $this->factory->course->create(
			array( 'post_content' => $this->outline_content( array( array( $source_lesson_id, 'Duplicada' ) ) ) )
		);

		$this->stub_object_id_map( array( $source_lesson_id => $translated_lesson_id ) );

		$course_translation = new Course_Translation();

		/* Act. */
		$course_translation->translate_outline_lesson_ids_on_course_duplicated( 123, 'es', array(), $duplicate_course_id );

		/* Clean up & Assert. */
		$this->remove_wpml_stubs();
		$this->assertStringContainsString( '"id":' . $translated_lesson_id, get_post( $duplicate_course_id )->post_content, 'The duplicated outline should reference the lessons of the duplicate language.' );
	}

	/**
	 * Build course content with an outline block holding the given lessons.
	 *
	 * @param array[] $lessons Pairs of lesson ID and title.
	 *
	 * @return string
	 */
	private function outline_content( array $lessons ) {
		$items = '';
		foreach ( $lessons as list( $lesson_id, $title ) ) {
			$items .= '<!-- wp:sensei-lms/course-outline-lesson {"id":' . $lesson_id . ',"title":"' . $title . '"} /-->';
		}

		return '<!-- wp:sensei-lms/course-outline -->' . $items . '<!-- /wp:sensei-lms/course-outline -->';
	}

	/**
	 * Stub the WPML language details for any element.
	 *
	 * @param string $language_code        Language of the translated course.
	 * @param string $source_language_code Language it was translated from.
	 */
	private function stub_course_language( $language_code, $source_language_code ) {
		add_filter(
			'wpml_element_language_details',
			function () use ( $language_code, $source_language_code ) {
				return array(
					'language_code'        => $language_code,
					'source_language_code' => $source_language_code,
				);
			},
			10,
			0
		);
	}

	/**
	 * Stub wpml_object_id with a fixed map; unmapped IDs resolve to nothing.
	 *
	 * @param array $map Object ID to translated object ID.
	 */
	private function stub_object_id_map( array $map ) {
		add_filter(
			'wpml_object_id',
			function ( $object_id ) use ( $map ) {
				return $map[ $object_id ] ?? null;
			},
			10,
			1
		);
	}

	/**
	 * Remove the WPML stubs added by the helpers above.
	 */
	private function remove_wpml_stubs() {
		remove_all_filters( 'wpml_element_language_details' );
		remove_all_filters( 'wpml_object_id' );
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

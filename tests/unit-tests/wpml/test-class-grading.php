<?php

namespace SenseiTest\WPML;

use Sensei\Internal\Services\Grading_Item;
use Sensei\WPML\Grading;

class Grading_Test extends \WP_UnitTestCase {

	public function testInit_WhenCalled_AddsFilters() {
		/* Arrange. */
		$grading = new Grading();

		/* Act. */
		$grading->init();

		/* Assert. */
		$this->assertEquals( 10, has_filter( 'sensei_grading_main_column_data', array( $grading, 'translate_row_titles' ) ) );
	}

	public function testTranslateRowTitles_LessonTranslationExists_ShowsCurrentLanguageLessonTitle() {
		/* Arrange. */
		list( $column_data, $item, $course_id ) = $this->arrange_row_with_translations();

		$grading = new Grading();

		/* Act. */
		$actual = $grading->translate_row_titles( $column_data, $item, $course_id );

		/* Assert. */
		$this->assertStringContainsString( 'Lección ES', $actual['lesson'] );
	}

	public function testTranslateRowTitles_LessonTranslationExists_KeepsOriginalLessonIdInLink() {
		/* Arrange. */
		list( $column_data, $item, $course_id ) = $this->arrange_row_with_translations();

		$grading = new Grading();

		/* Act. */
		$actual = $grading->translate_row_titles( $column_data, $item, $course_id );

		/* Assert. */
		$this->assertStringContainsString( 'lesson_id=' . $item->lesson_id, $actual['lesson'] );
	}

	public function testTranslateRowTitles_CourseTranslationExists_ShowsCurrentLanguageCourseTitle() {
		/* Arrange. */
		list( $column_data, $item, $course_id ) = $this->arrange_row_with_translations();

		$grading = new Grading();

		/* Act. */
		$actual = $grading->translate_row_titles( $column_data, $item, $course_id );

		/* Assert. */
		$this->assertStringContainsString( 'Curso ES', $actual['course'] );
	}

	public function testTranslateRowTitles_NoTranslationExists_ReturnsColumnDataUnchanged() {
		/* Arrange. */
		$factory   = new \Sensei_Factory();
		$lesson_id = $factory->lesson->create( array( 'post_title' => 'Lesson EN' ) );

		add_filter(
			'wpml_current_language',
			function () {
				return 'es';
			},
			10,
			0
		);
		// No wpml_object_id filter: WPML returns the same ID it was given.

		$column_data = array(
			'lesson' => '<a href="#original">Lesson EN</a>',
			'course' => '',
		);
		$item        = new Grading_Item( 'ungraded', 1, $lesson_id, current_time( 'mysql' ), null );

		$grading = new Grading();

		/* Act. */
		$actual = $grading->translate_row_titles( $column_data, $item, 0 );

		/* Assert. */
		$this->assertSame( $column_data, $actual );
	}

	/**
	 * Create an EN lesson/course pair with ES translations, register the WPML
	 * filters linking them (current language es), and build the row inputs.
	 *
	 * @return array{0: array, 1: Grading_Item, 2: int} Column data, item, and course ID.
	 */
	private function arrange_row_with_translations() {
		$factory = new \Sensei_Factory();

		$lesson_en = $factory->lesson->create( array( 'post_title' => 'Lesson EN' ) );
		$lesson_es = $factory->lesson->create( array( 'post_title' => 'Lección ES' ) );
		$course_en = $factory->course->create( array( 'post_title' => 'Course EN' ) );
		$course_es = $factory->course->create( array( 'post_title' => 'Curso ES' ) );

		add_filter(
			'wpml_current_language',
			function () {
				return 'es';
			},
			10,
			0
		);

		add_filter(
			'wpml_object_id',
			function ( $object_id, $type ) use ( $lesson_en, $lesson_es, $course_en, $course_es ) {
				if ( 'lesson' === $type && $lesson_en === $object_id ) {
					return $lesson_es;
				}
				if ( 'course' === $type && $course_en === $object_id ) {
					return $course_es;
				}
				return $object_id;
			},
			10,
			2
		);

		$column_data = array(
			'lesson' => '<a href="#original">Lesson EN</a>',
			'course' => '<a href="#original">Course EN</a>',
		);
		$item        = new Grading_Item( 'ungraded', 1, $lesson_en, current_time( 'mysql' ), null );

		return array( $column_data, $item, $course_en );
	}
}

<?php

namespace SenseiTest\WPML;

use Sensei\WPML\Lesson_Progress;

class Lesson_Progress_Test extends \WP_UnitTestCase {

	public function testInit_WhenCalled_AddsFilters() {
		/* Arrange. */
		$lesson_progress = new Lesson_Progress();

		/* Act. */
		$lesson_progress->init();

		/* Assert. */
		$this->assertEquals( 10, has_filter( 'sensei_utils_user_completed_lesson_lesson_id', array( $lesson_progress, 'translate_lesson_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_lesson_progress_create_lesson_id', array( $lesson_progress, 'translate_lesson_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_lesson_progress_get_lesson_id', array( $lesson_progress, 'translate_lesson_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_lesson_progress_has_lesson_id', array( $lesson_progress, 'translate_lesson_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_lesson_progress_delete_for_lesson_lesson_id', array( $lesson_progress, 'translate_lesson_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_lesson_progress_find_lesson_id', array( $lesson_progress, 'translate_lesson_id' ) ) );
	}

	public function testTranslateLessonId_WhenCalled_ReturnsMatchingValue() {
		/* Arrange. */
		$lesson_id = 1;

		$lesson_progress = new Lesson_Progress();

		add_filter(
			'wpml_element_language_details',
			function () {
				return array(
					'source_language_code' => 'en',
					'language_code'        => 'fr',
				);
			},
			10,
			0
		);

		add_filter(
			'wpml_object_id',
			function ( $lesson_id, $type, $original, $original_language_code ) {
				if ( 1 === $lesson_id && 'lesson' === $type && true === $original && 'en' === $original_language_code ) {
					return 2;
				} else {
					return 3;
				}
			},
			10,
			4
		);

		/* Act. */
		$actual = $lesson_progress->translate_lesson_id( $lesson_id );

		/* Assert. */
		$this->assertEquals( 2, $actual );
	}
}

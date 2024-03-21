<?php

namespace SenseiTest\WPML;

use Sensei\WPML\Quiz_Progress;

class Quiz_Progress_Test extends \WP_UnitTestCase {

	public function testInit_WhenCalled_AddsFilters() {
		/* Arrange. */
		$quiz_progress = new Quiz_Progress();

		/* Act. */
		$quiz_progress->init();

		/* Assert. */
		$this->assertEquals( 10, has_filter( 'sensei_quiz_progress_create_quiz_id', array( $quiz_progress, 'translate_quiz_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_quiz_progress_get_quiz_id', array( $quiz_progress, 'translate_quiz_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_quiz_progress_has_quiz_id', array( $quiz_progress, 'translate_quiz_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_quiz_progress_delete_for_quiz_quiz_id', array( $quiz_progress, 'translate_quiz_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_quiz_progress_find_quiz_id', array( $quiz_progress, 'translate_quiz_id' ) ) );
	}

	public function testTranslateQuizId_WhenCalled_ReturnsMatchingValue() {
		/* Arrange. */
		$quiz_id = 1;

		$quiz_progress = new Quiz_Progress();

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
			function ( $quiz_id, $type, $original, $original_language_code ) {
				if ( 1 === $quiz_id && 'quiz' === $type && true === $original && 'en' === $original_language_code ) {
					return 2;
				} else {
					return 3;
				}
			},
			10,
			4
		);

		/* Act. */
		$actual = $quiz_progress->translate_quiz_id( $quiz_id );

		/* Assert. */
		$this->assertEquals( 2, $actual );
	}
}

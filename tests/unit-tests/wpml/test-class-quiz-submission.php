<?php

namespace SenseiTest\WPML;

use Sensei\WPML\Quiz_Submission;

class Quiz_Submission_Test extends \WP_UnitTestCase {

	public function testInit_WhenCalled_AddsFilters() {
		/* Arrange. */
		$quiz_submission = new Quiz_Submission();

		/* Act. */
		$quiz_submission->init();

		/* Assert. */
		$this->assertEquals( 10, has_filter( 'sensei_quiz_submission_create_quiz_id', array( $quiz_submission, 'translate_quiz_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_quiz_submission_get_or_create_quiz_id', array( $quiz_submission, 'translate_quiz_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_quiz_submission_get_quiz_id', array( $quiz_submission, 'translate_quiz_id' ) ) );
	}

	public function testTranslateQuizId_WhenCalled_ReturnsMatchingValue() {
		/* Arrange. */
		$quiz_id = 1;

		$quiz_submission = new Quiz_Submission();

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
		$actual = $quiz_submission->translate_quiz_id( $quiz_id );

		/* Assert. */
		$this->assertEquals( 2, $actual );
	}
}

<?php

namespace SenseiTest\WPML;

use Sensei\WPML\Question_Submission;

class Question_Submission_Test extends \WP_UnitTestCase {

	public function testInit_WhenCalled_AddsFilters() {
		/* Arrange. */
		$question_submission = new Question_Submission();

		/* Act. */
		$question_submission->init();

		/* Assert. */
		$this->assertEquals( 10, has_filter( 'sensei_quiz_answer_create_question_id', array( $question_submission, 'translate_question_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_quiz_grade_create_question_id', array( $question_submission, 'translate_question_id' ) ) );
		$this->assertEquals( 10, has_filter( 'sensei_quiz_grade_save_many_question_id', array( $question_submission, 'translate_question_id' ) ) );
	}

	public function testTranslateQuestionId_WhenCalled_ReturnsMatchingValue() {
		/* Arrange. */
		$question_id = 1;

		$question_submission = new Question_Submission();

		add_filter(
			'wpml_element_language_details',
			function () {
				return array(
					'source_language_code' => 'en',
					'language_code'        => 'es',
				);
			},
			10,
			0
		);

		add_filter(
			'wpml_object_id',
			function ( $question_id, $type, $original, $original_language_code ) {
				if ( 1 === $question_id && 'question' === $type && true === $original && 'en' === $original_language_code ) {
					return 2;
				} else {
					return 3;
				}
			},
			10,
			4
		);

		/* Act. */
		$actual = $question_submission->translate_question_id( $question_id );

		/* Assert. */
		$this->assertEquals( 2, $actual );
	}
}

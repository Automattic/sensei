<?php

class Sensei_Class_Grading_Test extends WP_UnitTestCase {
	/**
	 * setup function
	 *
	 * This function sets up the lessons, quizes and their questions. This function runs before
	 * every single test in this class
	 */
	public function setUp() {
		parent::setup();

		Sensei()->grading = new WooThemes_Sensei_Grading( '' );
		$this->factory    = new Sensei_Factory();
	}//end setUp()

	public function tearDown() {
		parent::tearDown();
		$this->factory->tearDown();
	}

	/**
	 * Testing the quiz class to make sure it is loaded
	 */
	public function testClassInstance() {
		// setup the test
		// test if the global sensei quiz class is loaded
		$this->assertTrue( isset( Sensei()->grading ), 'Sensei Grading class is not loaded' );

	} // end testClassInstance

	/**
	 * Data source for ::testGradeGapFillQuestionRegEx
	 *
	 * @return array
	 */
	public function gradeGapFillQuestions() {
		return array(
			'simple-partial-word-case-insensitive' => array(
				'correct|Answer|simple',
				array( 'correct|Answer|simple', 'Correct', 'answer', 'correct', 'answer|simple' ),
				array( 'r|s', '|', 'bad' ),
				false,
			),
			'complete-word-only'                   => array(
				'^correct|Answer|simple$',
				array( 'Correct', 'answer', 'correct' ),
				array( 'incorrect' ),
				false,
			),
			'simple-case-sensitive'                => array(
				'correct|Answer|simple',
				array( 'simple', 'Answer', 'correct' ),
				array( 'r|s', '|', 'Correct', 'answer' ),
				true,
			),
			// See: https://github.com/Automattic/sensei/issues/1721
			'with-forward-slash'                   => array(
				'some|text|1.4|13/4',
				array( 'some|text|1.4|13/4', 'some', 'text', '1.4', '13/4' ),
				array( 'Some', 'Text', '4|13', '4' ),
				true,
			),
			'with-several-forward-slash'           => array(
				'some|text|1.4|13/4|13//3',
				array( 'some', 'text', '1.4', '13/4', '13//3' ),
				array( 'Some', 'Text', '4|13', '4', '13' ),
				true,
			),
			'all-valid'                            => array(
				'.+',
				array( 'some', 'text', 'dinosaur', '1', '0' ),
				array(),
				false,
			),
			'all-words-ending-in-s'                => array(
				'^[a-z]+s$',
				array( 'chickens', 'precious', 'dinosaurs' ),
				array( 'pie', 'beer', 'bread', 'spacepeople', '20' ),
				false,
			),
			'all-basic-integers'                   => array(
				'^[0-9]+$',
				array( '1', 1, '200', '34' ),
				array( '2e10', '2.2', 4.4, 'monkey', '' ),
				false,
			),
			'invalid-regex'                        => array(
				'[some|text|1.4|13/4',
				array( '[some|text|1.4|13/4' ),
				array( 'Some', 'Text', '4|13', '4', 'some', 'text', '1.4', '13/4' ),
				false,
			),
		);
	} // end gradeGapFillQuestions

	/**
	 * @dataProvider gradeGapFillQuestions
	 * @covers Sensei_Grading::grade_gap_fill_question
	 * @since 1.9.18
	 */
	public function testGradeGapFillQuestionRegEx( $answer, $found, $not_found, $case_sensitive ) {
		// Set up question
		$question_id = $this->getTestQuestion( 'gap-fill' );
		$this->assertNotFalse( $question_id );
		update_post_meta( $question_id, '_question_right_answer', 'pre||' . $answer . '||post' );
		if ( $case_sensitive ) {
			remove_filter( 'sensei_gap_fill_case_sensitive_grading', '__return_false' );
			add_filter( 'sensei_gap_fill_case_sensitive_grading', '__return_true' );
		} else {
			remove_filter( 'sensei_gap_fill_case_sensitive_grading', '__return_true' );
			add_filter( 'sensei_gap_fill_case_sensitive_grading', '__return_false' );
		}
		foreach ( $found as $found_item ) {
			$response = Sensei_Grading::grade_gap_fill_question( $question_id, $found_item );
			$this->assertEquals( 1, $response, "Expecting {$found_item} to match {$answer}" );
		}
		foreach ( $not_found as $not_found_item ) {
			$response = Sensei_Grading::grade_gap_fill_question( $question_id, $not_found_item );
			$this->assertFalse( $response, "Expecting {$not_found_item} to not match {$answer}" );
		}
	} // end testGradeGapFillQuestionRegEx

	/**
	 * Get a test question.
	 *
	 * @param string $question_type
	 * @return bool|int
	 */
	private function getTestQuestion( $question_type ) {
		$lesson_id = $this->factory->get_random_lesson_id();
		$quiz_id   = Sensei()->lesson->lesson_quizzes( $lesson_id );

		$question                = $this->factory->question->get_sample_question_data( $question_type );
		$question['quiz_id']     = $quiz_id;
		$question['post_author'] = get_post( $quiz_id )->post_author;
		return Sensei()->lesson->lesson_save_question( $question );
	} // end getTestQuestion
}//end class

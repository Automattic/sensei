<?php
/**
 * Tests for Sensei_Grading_User_Quiz.
 *
 * @package sensei
 */

class Sensei_Grading_User_Quiz_Test extends WP_UnitTestCase {

	/**
	 * Tests that get_question_graded_class returns the correct class
	 * for each combination of quiz grade type and user grade.
	 *
	 * @dataProvider provider_get_question_graded_class
	 *
	 * @param string $quiz_grade_type     Quiz grade type ('auto' or 'manual').
	 * @param mixed  $user_question_grade User's stored grade for the question.
	 * @param string $expected            Expected CSS class.
	 */
	public function test_get_question_graded_class( $quiz_grade_type, $user_question_grade, $expected ) {
		$method = new ReflectionMethod( Sensei_Grading_User_Quiz::class, 'get_question_graded_class' );
		$method->setAccessible( true );

		$actual = $method->invoke( null, $quiz_grade_type, $user_question_grade );

		$this->assertSame( $expected, $actual, "Expected class '{$expected}' for quiz_grade_type='{$quiz_grade_type}'." );
	}

	/**
	 * Data provider for test_get_question_graded_class.
	 *
	 * @return array[]
	 */
	public function provider_get_question_graded_class() {
		return array(
			'auto quiz, wrong'     => array( 'auto', 0, 'user_wrong' ),
			'auto quiz, right'     => array( 'auto', 1, 'user_right' ),
			'auto quiz, partial'   => array( 'auto', 5, 'user_right' ),
			'auto quiz, pending'   => array( 'auto', false, 'ungraded' ),
			'manual quiz, wrong'   => array( 'manual', 0, 'user_wrong' ),
			'manual quiz, right'   => array( 'manual', 2, 'user_right' ),
			'manual quiz, pending' => array( 'manual', false, 'ungraded' ),
			'unknown quiz, wrong'  => array( '', 0, 'ungraded' ),
		);
	}
}

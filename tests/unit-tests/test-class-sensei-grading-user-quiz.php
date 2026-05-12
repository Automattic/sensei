<?php
/**
 * Tests for Sensei_Grading_User_Quiz.
 *
 * @package sensei
 */

class Sensei_Grading_User_Quiz_Test extends WP_UnitTestCase {

	/**
	 * Tests that get_question_graded_class returns the correct class
	 * for each combination of quiz grade type, question grade type, and user grade.
	 *
	 * @dataProvider provider_get_question_graded_class
	 *
	 * @param string $quiz_grade_type     Quiz grade type ('auto' or 'manual').
	 * @param string $grade_type          Question grade type ('auto-grade' or 'manual-grade').
	 * @param mixed  $user_question_grade User's stored grade for the question.
	 * @param string $expected            Expected CSS class.
	 */
	public function test_get_question_graded_class( $quiz_grade_type, $grade_type, $user_question_grade, $expected ) {
		$method = new ReflectionMethod( Sensei_Grading_User_Quiz::class, 'get_question_graded_class' );
		$method->setAccessible( true );

		$actual = $method->invoke( null, $quiz_grade_type, $grade_type, $user_question_grade );

		$this->assertSame( $expected, $actual, "Expected class '{$expected}' for quiz_grade_type='{$quiz_grade_type}', grade_type='{$grade_type}'." );
	}

	/**
	 * Data provider for test_get_question_graded_class.
	 *
	 * @return array[]
	 */
	public function provider_get_question_graded_class() {
		return array(
			'auto, auto-grade, wrong'       => array( 'auto', 'auto-grade', 0, 'user_wrong' ),
			'auto, auto-grade, right'       => array( 'auto', 'auto-grade', 1, 'user_right' ),
			'auto, auto-grade, partial'     => array( 'auto', 'auto-grade', 5, 'user_right' ),
			'auto, auto-grade, pending'     => array( 'auto', 'auto-grade', false, 'ungraded' ),
			'auto, manual-grade, wrong'     => array( 'auto', 'manual-grade', 0, 'user_wrong' ),
			'auto, manual-grade, right'     => array( 'auto', 'manual-grade', 3, 'user_right' ),
			'auto, manual-grade, pending'   => array( 'auto', 'manual-grade', false, 'ungraded' ),
			'manual, manual-grade, wrong'   => array( 'manual', 'manual-grade', 0, 'user_wrong' ),
			'manual, manual-grade, right'   => array( 'manual', 'manual-grade', 2, 'user_right' ),
			'manual, manual-grade, pending' => array( 'manual', 'manual-grade', false, 'ungraded' ),
		);
	}
}

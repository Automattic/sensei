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

		$this->assertSame( $expected, $actual, sprintf( 'Expected class "%s" for quiz_grade_type="%s", grade_type="%s", user_question_grade=%s.', $expected, $quiz_grade_type, $grade_type, var_export( $user_question_grade, true ) ) );
	}

	/**
	 * Data provider for test_get_question_graded_class.
	 *
	 * @return array[]
	 */
	public function provider_get_question_graded_class() {
		return array(
			// Auto-graded quiz with auto-grade question types.
			'auto quiz, auto-grade question, wrong answer (grade=0)'    => array( 'auto', 'auto-grade', 0, 'user_wrong' ),
			'auto quiz, auto-grade question, correct answer (grade=1)'  => array( 'auto', 'auto-grade', 1, 'user_right' ),
			'auto quiz, auto-grade question, correct answer (grade=5)'  => array( 'auto', 'auto-grade', 5, 'user_right' ),
			'auto quiz, auto-grade question, not yet graded (false)'    => array( 'auto', 'auto-grade', false, 'ungraded' ),

			// Auto-graded quiz with manual-grade question types (e.g., essay in an auto quiz).
			'auto quiz, manual-grade question, grade=0'                 => array( 'auto', 'manual-grade', 0, 'user_wrong' ),
			'auto quiz, manual-grade question, grade=3'                 => array( 'auto', 'manual-grade', 3, 'user_right' ),
			'auto quiz, manual-grade question, not yet graded (false)'  => array( 'auto', 'manual-grade', false, 'ungraded' ),

			// Manual quiz (all question types become manual-grade).
			'manual quiz, manual-grade question, grade=0'               => array( 'manual', 'manual-grade', 0, 'user_wrong' ),
			'manual quiz, manual-grade question, grade=2'               => array( 'manual', 'manual-grade', 2, 'user_right' ),
			'manual quiz, manual-grade question, not yet graded (false)' => array( 'manual', 'manual-grade', false, 'ungraded' ),
		);
	}
}

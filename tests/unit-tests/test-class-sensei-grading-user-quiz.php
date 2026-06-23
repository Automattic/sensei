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

	/**
	 * The answer iframe sandbox must include allow-popups.
	 *
	 * Without allow-popups, a target="_blank" link inside the sandboxed srcdoc
	 * is silently blocked — teachers cannot open submitted file-upload answers.
	 *
	 * Delete-the-fix test: remove allow-popups from render_answer_iframe() and
	 * this assertion fails because the sandbox value no longer contains the token.
	 *
	 * @covers Sensei_Grading_User_Quiz::render_answer_iframe
	 */
	public function test_answer_iframe_sandbox_includes_allow_popups() {
		$html   = '<html><head><title></title></head><body><a href="http://example.com" target="_blank">View file</a></body></html>';
		$output = Sensei_Grading_User_Quiz::render_answer_iframe( $html );

		$this->assertStringContainsString(
			'sandbox="allow-same-origin allow-popups"',
			$output,
			'Answer iframe must allow popups so target="_blank" links open for graders.'
		);
	}
}

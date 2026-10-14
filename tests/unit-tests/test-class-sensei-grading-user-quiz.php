<?php
/**
 * Tests for Sensei_Grading_User_Quiz.
 *
 * @package sensei
 */

class Sensei_Grading_User_Quiz_Test extends WP_UnitTestCase {

	/**
	 * Submitted files can open and download from the grading page.
	 *
	 * @covers Sensei_Grading_User_Quiz::display
	 */
	public function testDisplay_FileUploadAnswerGiven_AllowsFileDownloadWithinSandbox() {
		$factory     = new Sensei_Factory();
		$user_id     = $factory->user->create();
		$lesson_id   = $factory->lesson->create();
		$quiz_id     = $factory->quiz->create( array( 'post_parent' => $lesson_id ) );
		$question_id = $factory->question->create(
			array(
				'quiz_id'       => $quiz_id,
				'question_type' => 'file-upload',
			)
		);
		update_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );
		update_post_meta( $quiz_id, '_quiz_lesson', $lesson_id );
		$attachment_id = $factory->attachment->create(
			array( 'file' => 'submitted.docx' )
		);
		Sensei()->quiz->save_user_answers( array( $question_id => (string) $attachment_id ), array(), $lesson_id, $user_id );
		$grading = new Sensei_Grading_User_Quiz( $user_id, $quiz_id );

		ob_start();
		$grading->display();
		$html = ob_get_clean();

		$iframe = new WP_HTML_Tag_Processor( $html );
		$iframe->next_tag(
			array(
				'tag_name'   => 'IFRAME',
				'class_name' => 'user-answer',
			)
		);
		$permissions = explode( ' ', (string) $iframe->get_attribute( 'sandbox' ) );
		$this->assertContains( 'allow-popups', $permissions, 'The sandbox should permit opening the submitted file.' );
		$this->assertContains( 'allow-downloads', $permissions, 'The submitted file should be downloadable.' );
		$link = new WP_HTML_Tag_Processor( $iframe->get_attribute( 'srcdoc' ) );
		$link->next_tag( array( 'tag_name' => 'A' ) );
		$this->assertSame( wp_get_attachment_url( $attachment_id ), $link->get_attribute( 'href' ), 'The link should use the submitted file URL.' );
	}

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
		Sensei_Unit_Tests_Bootstrap::make_reflection_accessible( $method );

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

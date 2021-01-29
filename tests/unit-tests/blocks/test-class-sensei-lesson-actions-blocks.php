<?php

/**
 * Tests for Lesson action blocks class.
 */
class Sensei_Lesson_Actions_Blocks extends WP_UnitTestCase {

	use Sensei_Course_Enrolment_Manual_Test_Helpers;
	use Sensei_Test_Login_Helpers;

	/**
	 * Constructor function
	 */
	public function __construct() {
		parent::__construct();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Test that the Next Lesson block is displayed in various scenarios.
	 */
	public function testNextLessonDisplayed() {
		$next_lesson = new Sensei_Next_Lesson_Block();

		$user_id            = $this->login_as_student()->get_user_by_role( 'subscriber' );
		$course_id          = $this->factory->course->create();
		$course_lesson_args = [
			'meta_input' => [
				'_lesson_course'       => $course_id,
				'_order_' . $course_id => 1,
			],
		];
		$lessons            = $this->factory->lesson->create_many( 2, $course_lesson_args );
		Sensei_Utils::update_lesson_status( $user_id, $lessons[0], 'passed' );
		Sensei_Utils::update_lesson_status( $user_id, $lessons[1], 'passed' );

		$this->set_current_lesson( $lessons[0] );
		$this->assertNotEmpty( $next_lesson->render( [], '' ), 'Next lesson button is not displayed on the first lesson.' );

		$this->set_current_lesson( $lessons[1] );
		$this->assertEmpty( $next_lesson->render( [], '' ), 'Next lesson button is displayed on the last lesson.' );

		Sensei_Utils::update_lesson_status( $user_id, $lessons[0], 'failed' );
		$this->set_current_lesson( $lessons[0] );
		$this->assertEmpty( $next_lesson->render( [], '' ), 'Next lesson button is displayed when the learner did not complete the lesson.' );
	}

	/**
	 * Test that the Complete Lesson block is displayed in various scenarios.
	 */
	public function testCompleteLessonDisplayed() {
		$complete_lesson = new Sensei_Complete_Lesson_Block();

		$user_id            = $this->login_as_student()->get_user_by_role( 'subscriber' );
		$course_id          = $this->factory->course->create();
		$course_lesson_args = [
			'meta_input' => [
				'_lesson_course'       => $course_id,
				'_order_' . $course_id => 1,
			],
		];
		$lesson_id          = $this->factory->lesson->create( $course_lesson_args );
		$this->set_current_lesson( $lesson_id );

		$this->assertEmpty( $complete_lesson->render( [], '' ), 'Complete lesson button is displayed when the user is not enrolled to the course.' );

		$this->manuallyEnrolStudentInCourse( $user_id, $course_id );
		$this->assertNotEmpty( $complete_lesson->render( [], '' ), 'Complete lesson button is not displayed when the user is enrolled to the course.' );

		Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'passed' );
		$this->assertEmpty( $complete_lesson->render( [], '' ), 'Complete lesson button is displayed when the user has completed the course.' );
	}

	/**
	 * Test that the View Quiz block is displayed in various scenarios.
	 */
	public function testViewQuizDisplayed() {
		$view_quiz = new Sensei_View_Quiz_Block();

		$user_id            = $this->login_as_student()->get_user_by_role( 'subscriber' );
		$course_id          = $this->factory->course->create();
		$course_lesson_args = [
			'meta_input' => [
				'_lesson_course'       => $course_id,
				'_order_' . $course_id => 1,
				'_quiz_has_questions'  => 1,
			],
		];
		$lesson_id          = $this->factory->lesson->create( $course_lesson_args );
		$this->set_current_lesson( $lesson_id );

		$this->assertEmpty( $view_quiz->render( [], '' ), 'View quiz button is displayed when there is no quiz to the lesson.' );

		$quiz_args = [
			'post_parent' => $lesson_id,
			'meta_input'  => [
				'_enable_quiz_reset' => 1,
			],
		];
		$this->factory->quiz->create( $quiz_args );
		$this->assertNotEmpty( $view_quiz->render( [], '' ), 'View quiz button is not displayed when there is a quiz to the lesson.' );

		Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'passed' );
		$this->assertEmpty( $view_quiz->render( [], '' ), 'View quiz button is displayed when the user has completed the lesson.' );
	}

	/**
	 * Test that the Reset Lesson block is displayed in various scenarios.
	 */
	public function testResetLessonDisplayed() {
		$reset_lesson = new Sensei_Reset_Lesson_Block();

		$user_id            = $this->login_as_student()->get_user_by_role( 'subscriber' );
		$course_id          = $this->factory->course->create();
		$course_lesson_args = [
			'meta_input' => [
				'_lesson_course'       => $course_id,
				'_order_' . $course_id => 1,
				'_quiz_has_questions'  => 1,
			],
		];
		$lesson_id          = $this->factory->lesson->create( $course_lesson_args );
		$quiz_args          = [
			'post_parent' => $lesson_id,
			'meta_input'  => [
				'_enable_quiz_reset' => 1,
			],
		];
		$quiz_id            = $this->factory->quiz->create( $quiz_args );
		$this->set_current_lesson( $lesson_id );

		$this->manuallyEnrolStudentInCourse( $user_id, $course_id );
		$this->assertEmpty( $reset_lesson->render( [], '' ), 'Reset lesson button is displayed when the user has not completed the lesson.' );

		Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'passed' );
		$this->assertNotEmpty( $reset_lesson->render( [], '' ), 'Reset lesson button is not displayed when the user has completed the lesson.' );

		update_post_meta( $quiz_id, '_enable_quiz_reset', 0 );
		$this->assertEmpty( $reset_lesson->render( [], '' ), 'Reset lesson button is displayed when reset is disabled.' );
	}

	/**
	 * Helper method to overwrite the post global.
	 *
	 * @param int $lesson_id The lesson id.
	 */
	private function set_current_lesson( int $lesson_id ) {
		$GLOBALS['post'] = (object) [
			'ID'        => $lesson_id,
			'post_type' => 'lesson',
		];
	}
}

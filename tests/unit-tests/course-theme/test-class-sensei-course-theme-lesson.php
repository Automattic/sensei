<?php
/**
 * This file contains the Sensei_Course_Theme_Lesson_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Course_Theme_Lesson_Test class.
 *
 * @group course-theme
 */
class Sensei_Course_Theme_Lesson_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	/**
	 * Setup function.
	 */
	public function setup() {
		parent::setup();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Testing the Course Theme Lesson class to make sure it is loaded.
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( 'Sensei_Course_Theme_Lesson' ), 'Sensei Course Theme class should exist' );
	}

	/**
	 * Testing quiz ungraded notice.
	 */
	public function testQuizUngradedNotice() {
		$lesson  = $this->create_lesson_with_submitted_answers();
		$user_id = get_current_user_id();

		\Sensei_Course_Theme_Lesson::instance()->init();

		$html = \Sensei_Context_Notices::instance( 'course_theme_lesson_quiz' )->get_notices_html( 'course-theme/lesson-quiz-notice.php' );

		$this->assertContains( 'Awaiting grade', $html, 'Should return quiz ungraded notice' );
	}

	/**
	 * Testing quiz failed notice.
	 */
	public function testQuizFailedNotice() {
		$lesson  = $this->create_lesson_with_submitted_answers();
		$user_id = get_current_user_id();

		Sensei_Utils::update_lesson_status( $user_id, $lesson->ID, 'failed' );
		\Sensei_Course_Theme_Lesson::instance()->init();

		$html = \Sensei_Context_Notices::instance( 'course_theme_lesson_quiz' )->get_notices_html( 'course-theme/lesson-quiz-notice.php' );

		$this->assertContains( 'You require <strong>80</strong>% to pass this course. Your grade is <strong>0</strong>%.', $html, 'Should return quiz failed notice' );
	}

	/**
	 * Testing quiz graded notice.
	 */
	public function testQuizGradedNotice() {
		$lesson  = $this->create_lesson_with_submitted_answers();
		$user_id = get_current_user_id();

		Sensei_Utils::update_lesson_status( $user_id, $lesson->ID, 'graded' );
		\Sensei_Course_Theme_Lesson::instance()->init();

		$html = \Sensei_Context_Notices::instance( 'course_theme_lesson_quiz' )->get_notices_html( 'course-theme/lesson-quiz-notice.php' );

		$this->assertContains( 'Your Grade <strong class="sensei-course-theme-lesson-quiz-notice__grade">0</strong>%', $html, 'Should return quiz graded notice' );
	}

	/**
	 * Testing prerequisite notice.
	 */
	public function testPrerequisiteNotice() {
		$prerequisite_lesson = $this->factory->lesson->create_and_get();
		$lesson              = $this->factory->lesson->create_and_get(
			[
				'meta_input' => [
					'_lesson_prerequisite' => $prerequisite_lesson->ID,
				],
			]
		);
		$GLOBALS['post']     = $lesson;

		\Sensei_Course_Theme_Lesson::instance()->init();

		$html = \Sensei_Context_Notices::instance( 'course_theme_locked_lesson' )->get_notices_html( 'course-theme/locked-lesson-notice.php' );

		$this->assertRegExp( '/Please complete the .* to view this lesson content/', $html, 'Should return prerequisite notice' );
	}

	/**
	 * Testing ungraded prerequisite notice.
	 */
	public function testUngradedPrerequisiteNotice() {
		$prerequisite_lesson = $this->create_lesson_with_submitted_answers();
		$lesson              = $this->factory->lesson->create_and_get(
			[
				'meta_input' => [
					'_lesson_prerequisite' => $prerequisite_lesson->ID,
				],
			]
		);
		$GLOBALS['post']     = $lesson;

		\Sensei_Course_Theme_Lesson::instance()->init();

		$html = \Sensei_Context_Notices::instance( 'course_theme_locked_lesson' )->get_notices_html( 'course-theme/locked-lesson-notice.php' );

		$this->assertRegExp( '/You will be able to view this lesson once the .* are completed and graded./', $html, 'Should return ungraded prerequisite notice' );
	}

	/**
	 * Create lesson with submitted answers.
	 *
	 * @return WP_Post Lesson post.
	 */
	private function create_lesson_with_submitted_answers() {
		$course          = $this->factory->course->create_and_get();
		$lesson          = $this->factory->lesson->create_and_get(
			[
				'meta_input' => [
					'_lesson_course'      => $course->ID,
					'_quiz_has_questions' => 1,
				],
			],
		);
		$GLOBALS['post'] = $lesson;
		$quiz_args       = [
			'post_parent' => $lesson->ID,
			'meta_input'  => [
				'_pass_required' => 'on',
				'_quiz_passmark' => '80',
			],
		];

		$this->factory->quiz->create( $quiz_args );
		$this->login_as_student();

		$user_id = get_current_user_id();
		Sensei()->frontend->manually_enrol_learner( get_current_user_id(), $course->ID );
		Sensei_Quiz::submit_answers_for_grading( [], [], $lesson->ID, $user_id );

		return $lesson;
	}
}

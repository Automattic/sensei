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
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Testing the Course Theme Lesson class to make sure it is loaded.
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( 'Sensei_Course_Theme_Lesson' ), 'Sensei Course Theme class should exist' );
	}

	/**
	 * Testing quiz progress notice.
	 */
	public function testQuizProgressNotice() {
		$lesson_id = $this->factory->get_lesson_with_quiz_and_questions(); // It creates a quiz with 3 questions.
		$lesson    = get_post( $lesson_id );
		$quiz_id   = Sensei()->lesson->lesson_quizzes( $lesson_id );
		$questions = Sensei()->lesson->lesson_quiz_questions( $quiz_id );

		// Set quiz pagination.
		update_post_meta(
			$quiz_id,
			'_pagination',
			wp_json_encode( [ 'pagination_number' => 2 ] )
		);

		$this->login_as_student();
		$GLOBALS['post'] = $lesson;

		$user_id = get_current_user_id();

		Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );

		// Set third question as unanswered.
		$user_quiz_answers = [
			$questions[0]->ID => '0',
			$questions[1]->ID => 'false',
			$questions[2]->ID => '',
		];
		$lesson_data_saved = Sensei()->quiz->save_user_answers( $user_quiz_answers, array(), $lesson_id, $user_id );

		\Sensei_Course_Theme_Lesson::instance()->init();
		$html = \Sensei_Context_Notices::instance( 'course_theme_lesson_quiz' )->get_notices_html( 'course-theme/lesson-quiz-notice.php' );

		$this->assertStringContainsString( 'Lesson quiz in progress', $html, 'Should return quiz progress notice' );
		$this->assertStringContainsString( '2 of 3', $html, 'Should return quiz progress notice' );
		$this->assertStringContainsString( 'quiz-page=2', $html, 'Should have the link to quiz page with the first unanswered question' );

		// Set second question as unanswered.
		$user_quiz_answers = [
			$questions[0]->ID => '0',
			$questions[1]->ID => '',
			$questions[2]->ID => 'false',
		];
		$lesson_data_saved = Sensei()->quiz->save_user_answers( $user_quiz_answers, array(), $lesson_id, $user_id );

		\Sensei_Course_Theme_Lesson::instance()->init();
		$html = \Sensei_Context_Notices::instance( 'course_theme_lesson_quiz' )->get_notices_html( 'course-theme/lesson-quiz-notice.php' );

		$this->assertStringContainsString( 'quiz-page=1', $html, 'Should have the link to quiz page with the first unanswered question' );

		// Remove quiz pagination.
		delete_post_meta( $quiz_id, '_pagination' );

		\Sensei_Course_Theme_Lesson::instance()->init();
		$html = \Sensei_Context_Notices::instance( 'course_theme_lesson_quiz' )->get_notices_html( 'course-theme/lesson-quiz-notice.php' );

		$this->assertStringNotContainsString( 'quiz-page=', $html, 'Should not have the link to a specific page' );
	}

	/**
	 * Testing quiz ungraded notice.
	 */
	public function testQuizUngradedNotice() {
		$lesson  = $this->create_lesson_with_submitted_answers();
		$user_id = get_current_user_id();

		\Sensei_Course_Theme_Lesson::instance()->init();

		$html = \Sensei_Context_Notices::instance( 'course_theme_lesson_quiz' )->get_notices_html( 'course-theme/lesson-quiz-notice.php' );

		$this->assertStringContainsString( 'Awaiting grade', $html, 'Should return quiz ungraded notice' );
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

		$this->assertStringContainsString( 'You require <strong>80</strong>% to pass this lesson\'s quiz. Your grade is <strong>0</strong>%.', $html, 'Should return quiz failed notice' );
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

		$this->assertStringContainsString( 'Your Grade: <strong class="sensei-course-theme-lesson-quiz-notice__grade">0</strong>%', $html, 'Should return quiz graded notice' );
	}

	/**
	 * Testing lesson prerequisite notice.
	 */
	public function testLessonPrerequisiteNotice() {
		$course_id           = $this->factory->course->create();
		$prerequisite_lesson = $this->factory->lesson->create_and_get();
		$lesson              = $this->factory->lesson->create_and_get(
			[
				'meta_input' => [
					'_lesson_course'       => $course_id,
					'_lesson_prerequisite' => $prerequisite_lesson->ID,
				],
			]
		);
		$GLOBALS['post']     = $lesson;

		$this->login_as_student();
		tests_add_filter( 'sensei_is_enrolled', '__return_true' );
		\Sensei_Course_Theme_Lesson::instance()->init();

		$html = \Sensei_Context_Notices::instance( 'course_theme_locked_lesson' )->get_notices_html( 'course-theme/locked-lesson-notice.php' );

		$this->assertMatchesRegularExpression( '/Please complete the .* to view this lesson content/', $html, 'Should return prerequisite notice' );
	}

	/**
	 * Testing ungraded lesson prerequisite notice.
	 */
	public function testUngradedLessonPrerequisiteNotice() {
		$course_id           = $this->factory->course->create();
		$prerequisite_lesson = $this->create_lesson_with_submitted_answers();
		$lesson              = $this->factory->lesson->create_and_get(
			[
				'meta_input' => [
					'_lesson_course'       => $course_id,
					'_lesson_prerequisite' => $prerequisite_lesson->ID,
				],
			]
		);
		$GLOBALS['post']     = $lesson;

		$this->login_as_student();
		tests_add_filter( 'sensei_is_enrolled', '__return_true' );
		\Sensei_Course_Theme_Lesson::instance()->init();

		$html = \Sensei_Context_Notices::instance( 'course_theme_locked_lesson' )->get_notices_html( 'course-theme/locked-lesson-notice.php' );

		$this->assertMatchesRegularExpression( '/You will be able to view this lesson once the .* are completed and graded./', $html, 'Should return ungraded prerequisite notice' );
	}

	/**
	 * Testing course prerequisite notice.
	 */
	public function testCoursePrerequisiteNotice() {
		$prerequisite_course_id = $this->factory->course->create();
		$course_id              = $this->factory->course->create(
			[
				'meta_input' => [
					'_course_prerequisite' => $prerequisite_course_id,
				],
			]
		);
		$lesson                 = $this->factory->lesson->create_and_get(
			[
				'meta_input' => [
					'_lesson_course' => $course_id,
				],
			]
		);
		$GLOBALS['post']        = $lesson;

		$this->login_as_student();
		\Sensei_Course_Theme_Lesson::instance()->init();

		$html = \Sensei_Context_Notices::instance( 'course_theme_locked_lesson' )->get_notices_html( 'course-theme/locked-lesson-notice.php' );

		$this->assertMatchesRegularExpression( '/You must first complete .* before taking this course./', $html, 'Should return course prerequisite notice' );
	}

	/**
	 * Testing not allowed self-enrollment notice.
	 */
	public function testCourseNotAllowedSelfEnrollmentNotice() {
		/* Arrange. */
		$prerequisite_course_id = $this->factory->course->create();
		$course_id              = $this->factory->course->create(
			[
				'meta_input' => [
					'_sensei_self_enrollment_not_allowed' => true,
				],
			]
		);
		$lesson                 = $this->factory->lesson->create_and_get(
			[
				'meta_input' => [
					'_lesson_course' => $course_id,
				],
			]
		);
		$GLOBALS['post']        = $lesson;

		$this->login_as_student();
		\Sensei_Course_Theme_Lesson::instance()->init();

		/* Act. */
		$html = \Sensei_Context_Notices::instance( 'course_theme_locked_lesson' )->get_notices_html( 'course-theme/locked-lesson-notice.php' );

		/* Assert. */
		$this->assertStringContainsString( 'Please contact the course administrator to take this lesson.', $html, 'Should return not allowed self-enrollment notice' );
	}

	/**
	 * Testing logged out notice.
	 */
	public function testLoggedOutNotice() {
		$lesson          = $this->factory->lesson->create_and_get();
		$GLOBALS['post'] = $lesson;

		wp_logout();
		\Sensei_Course_Theme_Lesson::instance()->init();

		$html = \Sensei_Context_Notices::instance( 'course_theme_locked_lesson' )->get_notices_html( 'course-theme/locked-lesson-notice.php' );

		$this->assertStringContainsString( 'Please register or sign in to access the course content.', $html, 'Should return logged out notice' );
	}

	/**
	 * Testing logged out preview notice.
	 */
	public function testLoggedOutPreviewNotice() {
		$lesson          = $this->factory->lesson->create_and_get(
			[
				'meta_input' => [
					'_lesson_preview' => 'preview',
				],
			]
		);
		$GLOBALS['post'] = $lesson;

		wp_logout();
		\Sensei_Course_Theme_Lesson::instance()->init();

		$html = \Sensei_Context_Notices::instance( 'course_theme_locked_lesson' )->get_notices_html( 'course-theme/locked-lesson-notice.php' );

		$this->assertStringContainsString( 'Register or sign in to take this lesson.', $html, 'Should return logged out preview notice' );
	}

	/**
	 * Testing not enrolled notice.
	 */
	public function testNotEnrolledNotice() {
		$lesson          = $this->factory->lesson->create_and_get();
		$GLOBALS['post'] = $lesson;

		$this->login_as_student();
		\Sensei_Course_Theme_Lesson::instance()->init();

		$html = \Sensei_Context_Notices::instance( 'course_theme_locked_lesson' )->get_notices_html( 'course-theme/locked-lesson-notice.php' );

		$this->assertStringContainsString( 'Please register for this course to access the content.', $html, 'Should return not enrolled notice' );
	}

	/**
	 * Testing not enrolled preview notice.
	 */
	public function testNotEnrolledPreviewNotice() {
		$lesson          = $this->factory->lesson->create_and_get(
			[
				'meta_input' => [
					'_lesson_preview' => 'preview',
				],
			]
		);
		$GLOBALS['post'] = $lesson;

		$this->login_as_student();
		\Sensei_Course_Theme_Lesson::instance()->init();

		$html = \Sensei_Context_Notices::instance( 'course_theme_locked_lesson' )->get_notices_html( 'course-theme/locked-lesson-notice.php' );

		$this->assertStringContainsString( 'Register for this course to take this lesson.', $html, 'Should return not enrolled preview notice' );
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
			]
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
		tests_add_filter( 'sensei_is_enrolled', '__return_true' );
		Sensei_Quiz::submit_answers_for_grading( [], [], $lesson->ID, $user_id );

		return $lesson;
	}
}

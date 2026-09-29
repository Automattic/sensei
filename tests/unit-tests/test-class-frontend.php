<?php
require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';

/**
 * Tests for Sensei_Frontend.
 *
 * @group frontend
 */
class Sensei_Frontend_Test extends WP_UnitTestCase {

	use Sensei_Course_Enrolment_Test_Helpers;
	use Sensei_Course_Enrolment_Manual_Test_Helpers;

	/**
	 * Test factory.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
		self::resetEnrolmentProviders();
		$this->prepareEnrolmentManager();
	}

	public function tearDown(): void {
		unset(
			$_POST['course_complete'],
			$_POST['course_complete_id'],
			$_POST['woothemes_sensei_complete_course_noonce'],
			$_POST['quiz_action'],
			$_POST['woothemes_sensei_complete_lesson_noonce']
		);

		remove_filter( 'sensei_is_login_required', '__return_false' );
		remove_filter( 'sensei_can_user_view_lesson', '__return_false' );

		parent::tearDown();
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		self::resetEnrolmentProviders();
	}

	/**
	 * An enrolled student can mark the course as complete.
	 */
	public function testSenseiCompleteCourse_StudentEnrolled_CompletesCourse() {
		$student_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$course_id  = $this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 2,
				'question_count' => 0,
			)
		)['course_id'];

		wp_set_current_user( $student_id );
		$this->manuallyEnrolStudentInCourse( $student_id, $course_id );
		$this->submitCompletion( $course_id );

		Sensei()->frontend->sensei_complete_course();

		self::assertTrue(
			Sensei_Utils::user_completed_course( $course_id, $student_id ),
			'An enrolled student should be able to complete the course.'
		);
	}

	/**
	 * A student who is not enrolled cannot mark the course as complete.
	 */
	public function testSenseiCompleteCourse_StudentNotEnrolled_DoesNotCompleteCourse() {
		$student_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$course_id  = $this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 2,
				'question_count' => 0,
			)
		)['course_id'];

		wp_set_current_user( $student_id );
		$this->submitCompletion( $course_id );

		Sensei()->frontend->sensei_complete_course();

		self::assertFalse(
			Sensei_Utils::user_completed_course( $course_id, $student_id ),
			'A student who is not enrolled should not be able to complete the course.'
		);
	}

	public function testSenseiCompleteLesson_StudentEnrolled_CompletesLesson() {
		$student_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$course     = $this->createCourseWithLessons( 1 );
		$lesson_id  = $course['lesson_ids'][0];

		wp_set_current_user( $student_id );
		$this->manuallyEnrolStudentInCourse( $student_id, $course['course_id'] );
		$this->submitLessonCompletion( $lesson_id );

		Sensei()->frontend->sensei_complete_lesson();

		self::assertTrue(
			Sensei_Utils::user_completed_lesson( $lesson_id, $student_id ),
			'An enrolled student should be able to complete the lesson.'
		);
	}

	public function testSenseiCompleteLesson_StudentEnrolledInAnotherCourse_DoesNotCompleteLesson() {
		$student_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$source     = $this->createCourseWithLessons( 1 );
		$target     = $this->createCourseWithLessons( 1 );
		$lesson_id  = $target['lesson_ids'][0];

		// Without the login requirement sensei_can_user_view_lesson() passes, so only
		// the enrolment check stands between the attacker and the target lesson.
		add_filter( 'sensei_is_login_required', '__return_false' );

		wp_set_current_user( $student_id );

		// The attacker is enrolled in the source course only, which is where the
		// replayed nonce comes from, and targets a lesson of the other course.
		$this->manuallyEnrolStudentInCourse( $student_id, $source['course_id'] );
		$this->submitLessonCompletion( $lesson_id );

		Sensei()->frontend->sensei_complete_lesson();

		self::assertFalse(
			Sensei_Utils::user_completed_lesson( $lesson_id, $student_id ),
			'Being enrolled in another course should not allow completing this lesson.'
		);
	}

	public function testSenseiCompleteLesson_ResetByStudentEnrolledInAnotherCourse_KeepsTheProgress() {
		$student_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$source     = $this->createCourseWithLessons( 1 );
		$target     = $this->createCourseWithLessons( 1 );
		$lesson_id  = $target['lesson_ids'][0];

		// Seed the progress the reset would destroy.
		Sensei_Utils::sensei_start_lesson( $lesson_id, $student_id, true );

		wp_set_current_user( $student_id );
		$this->manuallyEnrolStudentInCourse( $student_id, $source['course_id'] );
		$this->submitLessonCompletion( $lesson_id, 'lesson-reset' );

		Sensei()->frontend->sensei_complete_lesson();

		self::assertTrue(
			Sensei_Utils::user_completed_lesson( $lesson_id, $student_id ),
			'The lesson should stay complete: being enrolled in another course should not allow resetting it.'
		);
	}

	public function testSenseiCompleteLesson_LessonTheUserCannotViewGiven_DoesNotCompleteLesson() {
		$student_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$course     = $this->createCourseWithLessons( 1 );
		$lesson_id  = $course['lesson_ids'][0];

		// Enrolled and no prerequisite, but the lesson itself is locked, as content
		// drip or any other sensei_can_user_view_lesson filter would do.
		add_filter( 'sensei_can_user_view_lesson', '__return_false' );

		wp_set_current_user( $student_id );
		$this->manuallyEnrolStudentInCourse( $student_id, $course['course_id'] );
		$this->submitLessonCompletion( $lesson_id );

		Sensei()->frontend->sensei_complete_lesson();

		self::assertFalse(
			Sensei_Utils::user_completed_lesson( $lesson_id, $student_id ),
			'A lesson the student cannot view should not be completable.'
		);
	}

	public function testSenseiCompleteLesson_IncompletePrerequisiteAndLoginNotRequiredGiven_DoesNotCompleteLesson() {
		$student_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$course     = $this->createCourseWithLessons( 2 );

		list( $first_lesson, $locked_lesson ) = $course['lesson_ids'];

		update_post_meta( $locked_lesson, '_lesson_prerequisite', $first_lesson );

		// Without the login requirement, sensei_can_user_view_lesson() short-circuits
		// to true and stops considering the prerequisite.
		add_filter( 'sensei_is_login_required', '__return_false' );

		wp_set_current_user( $student_id );
		$this->manuallyEnrolStudentInCourse( $student_id, $course['course_id'] );
		$this->submitLessonCompletion( $locked_lesson );

		Sensei()->frontend->sensei_complete_lesson();

		self::assertFalse(
			Sensei_Utils::user_completed_lesson( $locked_lesson, $student_id ),
			'A locked lesson should not be completable even when login is not required.'
		);
	}

	/**
	 * Create a course with the given number of lessons and no quiz questions.
	 *
	 * @param int $lesson_count Lessons to create.
	 * @return array Factory data, with course_id and lesson_ids.
	 */
	private function createCourseWithLessons( int $lesson_count = 1 ): array {
		return $this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => $lesson_count,
				'question_count' => 0,
			)
		);
	}

	/**
	 * Populate the request as the "Complete Lesson" form submission does.
	 *
	 * The nonce is bound to the already-set current user, and carries no lesson id.
	 *
	 * @param int    $lesson_id Lesson the request targets.
	 * @param string $action    Submitted quiz_action, lesson-complete or lesson-reset.
	 */
	private function submitLessonCompletion( $lesson_id, $action = 'lesson-complete' ) {
		$GLOBALS['post'] = get_post( $lesson_id );

		$_POST['quiz_action']                             = $action;
		$_POST['woothemes_sensei_complete_lesson_noonce'] = wp_create_nonce( 'woothemes_sensei_complete_lesson_noonce' );
	}

	/**
	 * Populate the request as the "Mark as Complete" form submission does.
	 *
	 * The nonce is bound to the already-set current user.
	 *
	 * @param int $course_id Course being completed.
	 */
	private function submitCompletion( $course_id ) {
		$_POST['course_complete']                         = 'Mark as Complete';
		$_POST['course_complete_id']                      = $course_id;
		$_POST['woothemes_sensei_complete_course_noonce'] = wp_create_nonce( 'woothemes_sensei_complete_course_noonce' );
	}
}

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
			$_POST['woothemes_sensei_complete_course_noonce']
		);

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

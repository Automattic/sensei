<?php
/**
 * This file contains the Lesson_Actions_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';

use \Sensei\Blocks\Course_Theme\Lesson_Actions;

/**
 * Tests for Lesson_Actions_Test class.
 *
 * @group course-theme
 */
class Lesson_Actions_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Test_Helpers;
	use Sensei_Test_Login_Helpers;

	/**
	 * Setup function.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
		self::resetEnrolmentProviders();
		$this->prepareEnrolmentManager();

		if ( ! isset( Sensei()->admin ) ) {
			Sensei()->admin = new Sensei_Admin();
		}

		WP_Block_Supports::$block_to_render = [
			'attrs'     => [],
			'blockName' => 'sensei-lms/course-theme-lesson-actions',
		];
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		self::resetEnrolmentProviders();
		WP_Block_Supports::$block_to_render = null;
	}

	/**
	 * Testing the Lesson_Actions class to make sure it is loaded.
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( '\Sensei\Blocks\Course_Theme\Lesson_Actions' ), '\Sensei\Blocks\Course_Theme\Lesson_Actions class should exist' );
	}

	/**
	 * Test lesson actions block when there is no post.
	 */
	public function testNoPost() {
		$this->create_enrolled_lesson();

		$GLOBALS['post'] = null;
		$block           = new Lesson_Actions();

		// Check for empty response.
		$this->assertEmpty( $block->render(), 'Should render empty string if there is no post.' );
	}

	/**
	 * Test lesson actions block when the post is not lesson.
	 */
	public function testNotLesson() {
		list( , $course ) = $this->create_enrolled_lesson();

		$GLOBALS['post'] = $course;
		$block           = new Lesson_Actions();

		// Check for empty response.
		$this->assertEmpty( $block->render(), 'Should render empty string if the post is not a lesson.' );
	}

	/**
	 * Test lesson actions block when the user is not enrolled.
	 */
	public function testNotEnrolled() {
		$lesson = $this->factory->lesson->create_and_get();
		$this->login_as_student();
		$GLOBALS['post'] = $lesson;

		$block = new Lesson_Actions();

		// Check for empty response.
		$this->assertEmpty( $block->render(), 'Should render empty string if user is not enrolled.' );
	}

	/**
	 * Test lesson actions block when lesson has a quiz with pass not required.
	 */
	public function testQuizPassNotRequired() {

		$this->create_enrolled_lesson_with_quiz();

		$block      = new Lesson_Actions();
		$block_html = $block->render();

		// Check for is-secondary class suffix.
		$this->assertMatchesRegularExpression( '/<button.*is-secondary.*>.*\n.*Complete Lesson/', $block_html, 'Should render complete button as secondary CTA' );
		$this->assertStringContainsString( 'Take Quiz', $block_html, 'Should render the take quiz button' );
	}

	/**
	 * Test lesson actions block for a lesson with quiz that requires passing.
	 */
	public function testQuizPassRequired() {
		$quiz_args = [
			'meta_input' => [
				'_pass_required' => 'on',
			],
		];
		$this->create_enrolled_lesson_with_quiz( $quiz_args );

		$block = new Lesson_Actions();

		// Check for empty response.
		$this->assertStringNotContainsString( 'Complete Lesson', $block->render(), 'Should not render the complete lesson button.' );
		$this->assertStringContainsString( 'Take Quiz', $block->render(), 'Should render the take quiz button.' );
	}

	/**
	 * Test lesson actions block for a lesson with quiz that requires passing and is submitted.
	 */
	public function testQuizPassRequiredSubmitted() {
		$quiz_args      = [
			'meta_input' => [
				'_pass_required' => 'on',
			],
		];
		list( $lesson ) = $this->create_enrolled_lesson_with_quiz( $quiz_args );
		Sensei_Quiz::submit_answers_for_grading( [], [], $lesson->ID, get_current_user_id() );

		$block = new Lesson_Actions();

		$this->assertEmpty( $block->render(), 'Should render empty string if quiz requires passing and it is submitted.' );
	}

	/**
	 * Test lesson actions block when user already completed the lesson.
	 */
	public function testAlreadyCompletedShowsCompletedBadge() {
		list( $lesson ) = $this->create_enrolled_lesson_with_quiz();
		\Sensei_Utils::sensei_start_lesson( $lesson->ID, get_current_user_id(), true );

		$block = new Lesson_Actions();

		$this->assertStringContainsString( 'Completed', $block->render(), 'Should render "Completed" button if user already completed the lesson.' );
	}

	/**
	 * Test lesson actions block when user already completed the lesson.
	 */
	public function testAlreadyCompletedNextLesson() {
		list( $lesson, $course ) = $this->create_enrolled_lesson();
		$lesson2                 = $this->factory->lesson->create_and_get(
			[
				'meta_input' => [
					'_lesson_course' => $course->ID,
				],
			]
		);
		$lesson_order            = [ $lesson->ID, $lesson2->ID ];
		Sensei()->admin->save_lesson_order( implode( ',', $lesson_order ), $course->ID );

		\Sensei_Utils::sensei_start_lesson( $lesson->ID, get_current_user_id(), true );

		$block = new Lesson_Actions();

		$this->assertStringContainsString( 'Next Lesson', $block->render( [ 'options' => [ 'nextLesson' => true ] ] ), 'Should render "Next Lesson" link if the option is enabled and there is a next lesson in the course.' );
	}

	/**
	 * Test lesson actions block when user already completed the lesson.
	 */
	public function testAlreadyCompletedNoNextLesson() {
		$this->create_enrolled_lesson();

		$block = new Lesson_Actions();

		$this->assertStringNotContainsString( 'Next Lesson', $block->render( [ 'options' => [ 'nextLesson' => true ] ] ), 'Should not render "Next Lesson" link if the option is enabled but there is no next lesson in the course.' );
	}

	/**
	 * Test lesson actions block for a lesson with a pre-requisite lesson.
	 */
	public function testHasPreRequisite() {
		list( $lesson1, $course ) = $this->create_enrolled_lesson_with_quiz();
		$lesson2                  = $this->factory->lesson->create_and_get(
			[
				'meta_input' => [
					'_lesson_course'       => $course->ID,
					'_lesson_prerequisite' => $lesson1->ID,
					'_quiz_has_questions'  => 1,
				],
			]
		);
		$quiz_args                = [
			'post_parent' => $lesson2->ID,
		];
		$this->factory->quiz->create( $quiz_args );

		$GLOBALS['post'] = $lesson2;
		$block           = new Lesson_Actions();

		// Check for disabled button.
		$this->assertStringContainsString( ' disabled', $block->render(), 'Should render disabled button if lesson has a pre-requisite.' );
		$this->assertStringContainsString( 'aria-disabled="true"', $block->render(), 'Should render disabled button if lesson has a pre-requisite.' );
	}

	/**
	 * Test lesson actions block when lesson can be marked as complete.
	 */
	public function testBlock() {
		$this->create_enrolled_lesson_with_quiz();

		$block = new Lesson_Actions();

		// Check for Complete lesson button.
		$this->assertStringContainsString( 'Complete Lesson', $block->render(), 'Should render "Complete lesson" button if user can mark lesson as complete.' );
	}


	/**
	 * Create a course and lesson, log in as student and enroll in course.
	 *
	 * @param array $lesson_args Lesson creation arguments.
	 *
	 * @return array Tuple of lesson, course, user
	 */
	private function create_enrolled_lesson( $lesson_args = [] ) {
		$course = $this->factory->course->create_and_get();
		$lesson = $this->factory->lesson->create_and_get( $lesson_args );
		add_post_meta( $lesson->ID, '_lesson_course', $course->ID );
		$user = $this->login_as_student();
		Sensei()->frontend->manually_enrol_learner( get_current_user_id(), $course->ID );
		$GLOBALS['post'] = $lesson;

		return [ $lesson, $course, $user ];
	}

	/**
	 * Create a course and lesson, add a quiz, log in as student and enroll in course.
	 *
	 * @param array $quiz_args Quiz creation arguments.
	 *
	 * @return array Tuple of lesson, course, user, quiz
	 */
	private function create_enrolled_lesson_with_quiz( $quiz_args = [] ) {
		$lesson_args                    = [
			'meta_input' => [
				'_quiz_has_questions' => 1,
			],
		];
		list( $lesson, $course, $user ) = $this->create_enrolled_lesson( $lesson_args );
		$quiz_args['post_parent']       = $lesson->ID;
		$quiz                           = $this->factory->quiz->create( $quiz_args );

		return [ $lesson, $course, $user, $quiz ];
	}
}

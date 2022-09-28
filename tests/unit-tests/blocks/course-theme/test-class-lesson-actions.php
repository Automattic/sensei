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

use \Sensei\Blocks\Learning_Mode\Lesson_Actions;

/**
 * Tests for Lesson_Actions_Test class.
 *
 * @group learning-mode
 */
class Lesson_Actions_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Test_Helpers;
	use Sensei_Test_Login_Helpers;
	/**
	 * Setup function.
	 */
	public function setup() {
		parent::setup();

		$this->factory = new Sensei_Factory();
		self::resetEnrolmentProviders();
		$this->prepareEnrolmentManager();

		WP_Block_Supports::$block_to_render = [
			'attrs'     => [],
			'blockName' => 'sensei-lms/learning-mode-lesson-actions',
		];
	}

	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		self::resetEnrolmentProviders();
		WP_Block_Supports::$block_to_render = null;
	}

	/**
	 * Testing the Lesson_Actions class to make sure it is loaded.
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( '\Sensei\Blocks\Learning_Mode\Lesson_Actions' ), '\Sensei\Blocks\Learning_Mode\Lesson_Actions class should exist' );
	}

	/**
	 * Test lesson actions block when there is no post.
	 */
	public function testNoPost() {
		$course = $this->factory->course->create_and_get();
		$lesson = $this->factory->lesson->create_and_get();
		add_post_meta( $lesson->ID, '_lesson_course', $course->ID );
		$this->login_as_student();
		Sensei()->frontend->manually_enrol_learner( get_current_user_id(), $course->ID );

		$GLOBALS['post'] = null;
		$block           = new Lesson_Actions();

		// Check for empty response.
		$this->assertEmpty( $block->render(), 'Should render empty string if there is no post.' );
	}

	/**
	 * Test lesson actions block when the post is not lesson.
	 */
	public function testNotLesson() {
		$course = $this->factory->course->create_and_get();
		$lesson = $this->factory->lesson->create_and_get();
		add_post_meta( $lesson->ID, '_lesson_course', $course->ID );
		$this->login_as_student();
		Sensei()->frontend->manually_enrol_learner( get_current_user_id(), $course->ID );

		$GLOBALS['post'] = $course;
		$block           = new Lesson_Actions();

		// Check for empty response.
		$this->assertEmpty( $block->render(), 'Should render empty string if the post is not a lesson.' );
	}

	/**
	 * Test lesson actions block when the user is not enrolled.
	 */
	public function testNotEnrolled() {
		$course = $this->factory->course->create_and_get();
		$lesson = $this->factory->lesson->create_and_get();
		add_post_meta( $lesson->ID, '_lesson_course', $course->ID );
		$this->login_as_student();

		$GLOBALS['post'] = $lesson;
		$block           = new Lesson_Actions();

		// Check for empty response.
		$this->assertEmpty( $block->render(), 'Should render empty string if user is not enrolled.' );
	}

	/**
	 * Test lesson actions block when lesson has a quiz with pass not required.
	 */
	public function testQuizPassNotRequired() {
		$course      = $this->factory->course->create_and_get();
		$lesson_args = [
			'meta_input' => [
				'_lesson_course'      => $course->ID,
				'_quiz_has_questions' => 1,
			],
		];
		$lesson      = $this->factory->lesson->create_and_get( $lesson_args );
		$quiz_args   = [
			'post_parent' => $lesson->ID,
		];
		$this->factory->quiz->create( $quiz_args );
		$this->login_as_student();
		Sensei()->frontend->manually_enrol_learner( get_current_user_id(), $course->ID );

		$GLOBALS['post'] = $lesson;
		$block           = new Lesson_Actions();
		$block_html      = $block->render();

		// Check for is-secondary class suffix.
		$this->assertRegExp( '/<button.*is-secondary.*>.*\n.*Complete lesson/', $block_html, 'Should render complete button as secondary CTA' );
		$this->assertContains( 'Take quiz', $block_html, 'Should render the take quiz button' );
	}

	/**
	 * Test lesson actions block for a lesson with quiz that requires passing.
	 */
	public function testQuizPassRequired() {
		$course      = $this->factory->course->create_and_get();
		$lesson_args = [
			'meta_input' => [
				'_lesson_course'      => $course->ID,
				'_quiz_has_questions' => 1,
			],
		];
		$lesson      = $this->factory->lesson->create_and_get( $lesson_args );
		$quiz_args   = [
			'post_parent' => $lesson->ID,
			'meta_input'  => [
				'_pass_required' => 'on',
			],
		];
		$this->factory->quiz->create( $quiz_args );
		$this->login_as_student();
		Sensei()->frontend->manually_enrol_learner( get_current_user_id(), $course->ID );

		$GLOBALS['post'] = $lesson;
		$block           = new Lesson_Actions();

		// Check for empty response.
		$this->assertNotContains( 'Complete lesson', $block->render(), 'Should not render the complete lesson button.' );
		$this->assertContains( 'Take quiz', $block->render(), 'Should render the take quiz button.' );
	}

	/**
	 * Test lesson actions block for a lesson with quiz that requires passing and is submitted.
	 */
	public function testQuizPassRequiredSubmitted() {
		$course      = $this->factory->course->create_and_get();
		$lesson_args = [
			'meta_input' => [
				'_lesson_course'      => $course->ID,
				'_quiz_has_questions' => 1,
			],
		];
		$lesson      = $this->factory->lesson->create_and_get( $lesson_args );
		$quiz_args   = [
			'post_parent' => $lesson->ID,
			'meta_input'  => [
				'_pass_required' => 'on',
			],
		];
		$this->factory->quiz->create( $quiz_args );
		$this->login_as_student();
		Sensei()->frontend->manually_enrol_learner( get_current_user_id(), $course->ID );
		Sensei_Quiz::submit_answers_for_grading( [], [], $lesson->ID, get_current_user_id() );

		$GLOBALS['post'] = $lesson;
		$block           = new Lesson_Actions();

		$this->assertEmpty( $block->render(), 'Should render empty string if quiz requires passing and it is submitted.' );
	}

	/**
	 * Test lesson actions block when user already completed the lesson.
	 */
	public function testAlreadyCompleted() {
		$course = $this->factory->course->create_and_get();
		$lesson = $this->factory->lesson->create_and_get();
		add_post_meta( $lesson->ID, '_lesson_course', $course->ID );
		$student = $this->login_as_student();
		Sensei()->frontend->manually_enrol_learner( get_current_user_id(), $course->ID );
		\Sensei_Utils::sensei_start_lesson( $lesson->ID, get_current_user_id(), true );

		$GLOBALS['post'] = $lesson;
		$block           = new Lesson_Actions();

		$this->assertEmpty( $block->render(), 'Should render empty string if user already completed the lesson.' );
	}

	/**
	 * Test lesson actions block for a lesson with a pre-requisite lesson.
	 */
	public function testHasPreRequisite() {
		$course    = $this->factory->course->create_and_get();
		$lesson1   = $this->factory->lesson->create_and_get(
			[
				'meta_input' => [
					'_lesson_course' => $course->ID,
				],
			]
		);
		$lesson2   = $this->factory->lesson->create_and_get(
			[
				'meta_input' => [
					'_lesson_course'       => $course->ID,
					'_lesson_prerequisite' => $lesson1->ID,
					'_quiz_has_questions'  => 1,
				],
			]
		);
		$quiz_args = [
			'post_parent' => $lesson2->ID,
		];
		$this->factory->quiz->create( $quiz_args );
		$this->login_as_student();
		Sensei()->frontend->manually_enrol_learner( get_current_user_id(), $course->ID );

		$GLOBALS['post'] = $lesson2;
		$block           = new Lesson_Actions();

		// Check for disabled button.
		$this->assertContains( ' disabled', $block->render(), 'Should render disabled button if lesson has a pre-requisite.' );
		$this->assertContains( 'aria-disabled="true"', $block->render(), 'Should render disabled button if lesson has a pre-requisite.' );
	}

	/**
	 * Test lesson actions block when lesson can be marked as complete.
	 */
	public function testBlock() {
		$course = $this->factory->course->create_and_get();
		$lesson = $this->factory->lesson->create_and_get();
		add_post_meta( $lesson->ID, '_lesson_course', $course->ID );
		$student = $this->login_as_student();
		Sensei()->frontend->manually_enrol_learner( get_current_user_id(), $course->ID );

		$GLOBALS['post'] = $lesson;
		$block           = new Lesson_Actions();

		// Check for Complete lesson button.
		$this->assertContains( 'Complete lesson', $block->render(), 'Should render "Complete lesson" button if user can mark lesson as complete.' );
	}
}

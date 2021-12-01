<?php
/**
 * This file contains the Complete_Lesson_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei\Blocks\Course_Theme\Complete_Lesson;

/**
 * Tests for Complete_Lesson_Test class.
 *
 * @group course-theme
 */
class Complete_Lesson_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;
	/**
	 * Setup function.
	 */
	public function setup() {
		parent::setup();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Testing the Complete_Lesson class to make sure it is loaded.
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( '\Sensei\Blocks\Course_Theme\Complete_Lesson' ), '\Sensei\Blocks\Course_Theme\Complete_Lesson class should exist' );
	}

	/**
	 * Test complete lesson block when there is no post.
	 */
	public function testNoPost() {
		$course = $this->factory->course->create_and_get();
		$lesson = $this->factory->lesson->create_and_get();
		add_post_meta( $lesson->ID, '_lesson_course', $course->ID );
		$this->login_as_student();
		\Sensei()->frontend->manually_enrol_learner( get_current_user_id(), $course->ID );

		$GLOBALS['post'] = null;
		$block           = new Complete_Lesson();

		// Check for empty response.
		$this->assertEquals( '', $block->render(), 'Should render empty string if there is no post.' );
	}

	/**
	 * Test complete lesson block when the post is not lesson.
	 */
	public function testNotLesson() {
		$course = $this->factory->course->create_and_get();
		$lesson = $this->factory->lesson->create_and_get();
		add_post_meta( $lesson->ID, '_lesson_course', $course->ID );
		$this->login_as_student();
		\Sensei()->frontend->manually_enrol_learner( get_current_user_id(), $course->ID );

		$GLOBALS['post'] = $course;
		$block           = new Complete_Lesson();

		// Check for empty response.
		$this->assertEquals( '', $block->render(), 'Should render empty string if the post is not a lesson.' );
	}

	/**
	 * Test complete lesson block when the user is not enrolled.
	 */
	public function testNotEnrolled() {
		$course = $this->factory->course->create_and_get();
		$lesson = $this->factory->lesson->create_and_get();
		add_post_meta( $lesson->ID, '_lesson_course', $course->ID );
		$this->login_as_student();

		$GLOBALS['post'] = $lesson;
		$block           = new Complete_Lesson();

		// Check for empty response.
		$this->assertEquals( '', $block->render(), 'Should render empty string if user is not enrolled.' );
	}

	/**
	 * Test complete lesson block when lesson has a quiz.
	 *
	 * @group course-theme-one
	 */
	public function testQuiz() {
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
		\Sensei()->frontend->manually_enrol_learner( get_current_user_id(), $course->ID );

		$GLOBALS['post'] = $lesson;
		$block           = new Complete_Lesson();

		// Check for is-secondary class.
		$this->assertContains( 'is-secondary', $block->render(), 'Should render as a secondary CTA if lesson has quiz but not required to pass.' );
	}

	/**
	 * Test complete lesson block for a lesson with quiz that requires passing.
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
		\Sensei()->frontend->manually_enrol_learner( get_current_user_id(), $course->ID );

		$GLOBALS['post'] = $lesson;
		$block           = new Complete_Lesson();

		// Check for empty response.
		$this->assertEquals( '', $block->render(), 'Should render empty string if the lesson has quiz with questions and requires passing.' );
	}

	/**
	 * Test complete lesson block when user already completed the lesson.
	 */
	public function testAlreadyCompleted() {
		$course = $this->factory->course->create_and_get();
		$lesson = $this->factory->lesson->create_and_get();
		add_post_meta( $lesson->ID, '_lesson_course', $course->ID );
		$student = $this->login_as_student();
		\Sensei()->frontend->manually_enrol_learner( get_current_user_id(), $course->ID );
		\Sensei_Utils::sensei_start_lesson( $lesson->ID, get_current_user_id(), true );

		$GLOBALS['post'] = $lesson;
		$block           = new Complete_Lesson();

		// Check for empty response.
		$this->assertContains( '', $block->render(), 'Should render empty string if user already completed the lesson.' );
	}

	/**
	 * Test complete lesson block for a lesson with a pre-requisite lesson.
	 */
	public function testHasPreRequisite() {
		$course  = $this->factory->course->create_and_get();
		$lesson1 = $this->factory->lesson->create_and_get(
			[
				'meta_input' => [
					'_lesson_course' => $course->ID,
				],
			]
		);
		$lesson2 = $this->factory->lesson->create_and_get(
			[
				'meta_input' => [
					'_lesson_course'       => $course->ID,
					'_lesson_prerequisite' => $lesson1->ID,
				],
			]
		);
		$this->login_as_student();
		\Sensei()->frontend->manually_enrol_learner( get_current_user_id(), $course->ID );

		$GLOBALS['post'] = $lesson2;
		$block           = new Complete_Lesson();

		// Check for disabled button
		$this->assertContains( 'disabled', $block->render(), 'Should render disabled button if lesson has a pre-requisite.' );
	}

	/**
	 * Test complete lesson block when lesson can be marked as complete.
	 */
	public function testBlock() {
		$course = $this->factory->course->create_and_get();
		$lesson = $this->factory->lesson->create_and_get();
		add_post_meta( $lesson->ID, '_lesson_course', $course->ID );
		$student = $this->login_as_student();
		\Sensei()->frontend->manually_enrol_learner( get_current_user_id(), $course->ID );

		$GLOBALS['post'] = $lesson;
		$block           = new Complete_Lesson();

		// Check for Complete lesson button.
		$this->assertContains( 'Complete lesson', $block->render(), 'Should render "Complete lesson" button if user can mark lesson as complete.' );
	}
}

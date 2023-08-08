<?php
/**
 * This file contains the Quiz_Back_To_Lesson_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei\Blocks\Course_Theme\Quiz_Back_To_Lesson;

/**
 * Tests for Quiz_Back_To_Lesson_Test class.
 *
 * @group course-theme
 */
class Quiz_Back_To_Lesson_Test extends WP_UnitTestCase {
	/**
	 * Setup function.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();

		WP_Block_Supports::$block_to_render = [
			'attrs'     => [],
			'blockName' => 'sensei-lms/quiz-back-to-lesson',
		];
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		WP_Block_Supports::$block_to_render = null;
	}

	/**
	 * Testing the Quiz Back To Lesson class to make sure it is loaded.
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( '\Sensei\Blocks\Course_Theme\Quiz_Back_To_Lesson' ), 'Quiz Back To Lesson class should exist' );
	}

	/**
	 * Tests that back to the lesson link is correct.
	 */
	public function testBackToTheLessonLink() {
		$lesson_id = $this->factory->lesson->create();
		$quiz      = $this->factory->quiz->create_and_get( [ 'post_parent' => $lesson_id ] );

		$GLOBALS['post'] = $quiz;

		$block = new Quiz_Back_To_Lesson();
		$html  = $block->render();

		$this->assertStringContainsString( get_permalink( $lesson_id ), $html );
	}

	/**
	 * Tests that back to the lesson is empty of of the quiz.
	 */
	public function testBackToTheLessonIsEmptyOutOfQuiz() {
		$lesson_id = $this->factory->lesson->create();
		$this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );

		$GLOBALS['post'] = get_post( $lesson_id );

		$block = new Quiz_Back_To_Lesson();
		$html  = $block->render();

		$this->assertEmpty( $html );
	}
}

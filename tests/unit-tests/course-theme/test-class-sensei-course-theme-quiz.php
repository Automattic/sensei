<?php

namespace SenseiTest;

use Sensei_Factory;

class Sensei_Course_Theme_Quiz_Test extends \WP_UnitTestCase {

	/**
	 * Sensei factory.
	 *
	 * @var Sensei_Facotry
	 */
	protected $factory;

	public function setUp(): void {
		parent::setUp();
		$this->factory = new Sensei_Factory();
	}

	public function testInit_QuizPassed_AddsNotice(): void {
		/* Arrange. */
		global $post;

		$notices   = \Sensei_Context_Notices::instance( 'course_theme_quiz_grade' );
		$lesson_id = $this->factory->lesson->create();
		$quiz      = $this->factory->quiz->create_and_get( array( 'post_parent' => $lesson_id ) );
		$user      = $this->factory->user->create_and_get();
		$post      = $quiz;
		wp_set_current_user( $user->ID );
		$notices->remove_notice( 'course-theme-quiz-grade' );

		Sensei()->lesson_progress_repository->create( $lesson_id, $user->ID );
		$quiz_progress = Sensei()->quiz_progress_repository->create( $quiz->ID, $user->ID );
		$quiz_progress->pass();
		Sensei()->quiz_progress_repository->save( $quiz_progress );

		$course_theme_quiz = \Sensei_Course_Theme_Quiz::instance();

		/* Act. */
		$course_theme_quiz->init();

		/* Assert. */
		$notices_html = $notices->get_notices_html( 'course-theme/lesson-quiz-notice.php' );
		$this->assertStringContainsString( 'Continue to next lesson', $notices_html );
	}

	public function testInit_QuizInProgress_DoesntAddNotice(): void {
		/* Arrange. */
		global $post;

		$notices   = \Sensei_Context_Notices::instance( 'course_theme_quiz_grade' );
		$lesson_id = $this->factory->lesson->create();
		$quiz      = $this->factory->quiz->create_and_get( array( 'post_parent' => $lesson_id ) );
		$user      = $this->factory->user->create_and_get();
		$post      = $quiz;
		wp_set_current_user( $user->ID );
		$notices->remove_notice( 'course-theme-quiz-grade' );

		Sensei()->lesson_progress_repository->create( $lesson_id, $user->ID );
		$quiz_progress = Sensei()->quiz_progress_repository->create( $quiz->ID, $user->ID );
		$quiz_progress->start();
		Sensei()->quiz_progress_repository->save( $quiz_progress );

		$course_theme_quiz = \Sensei_Course_Theme_Quiz::instance();

		/* Act. */
		$course_theme_quiz->init();

		/* Assert. */
		$notices_html = $notices->get_notices_html( 'course-theme/lesson-quiz-notice.php' );
		$this->assertStringNotContainsString( 'Continue to next lesson', $notices_html );
	}
}

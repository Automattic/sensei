<?php

namespace SenseiTest\Internal\Student_Progress\Services;

use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Interface;
use Sensei\Internal\Student_Progress\Services\Lesson_Deleted_Handler;

class Lesson_Deleted_Handler_Test extends \WP_UnitTestCase {
	/**
	 * Tests that the handler is initialized.
	 */
	public function testInit_WhenCalled_AddsAction() {
		/* Arrange. */
		$lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );
		$handler                    = new Lesson_Deleted_Handler( $lesson_progress_repository );

		/* Act. */
		$handler->init();

		/* Assert. */
		$this->assertEquals( 10, has_action( 'deleted_post', [ $handler, 'handle' ] ) );
	}

	public function testHandle_WhenPostWasNotLesson_DoesntCallDeleteForLesson() {
		/* Arrange. */
		$deleted_post               = new \WP_Post( (object) [ 'post_type' => 'post' ] );
		$lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );

		$handler = new Lesson_Deleted_Handler( $lesson_progress_repository );

		/* Expect & Act. */
		$lesson_progress_repository
			->expects( $this->never() )
			->method( 'delete_for_lesson' );
		$handler->handle( 1, $deleted_post );
	}

	public function testHandle_WhenLessonGiven_CallsDeleteForLesson() {
		/* Arrange. */
		$deleted_post               = new \WP_Post( (object) [ 'post_type' => 'lesson' ] );
		$lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );

		$handler = new Lesson_Deleted_Handler( $lesson_progress_repository );

		/* Expect & Act. */
		$lesson_progress_repository
			->expects( $this->once() )
			->method( 'delete_for_lesson' )
			->with( 1 );
		$handler->handle( 1, $deleted_post );
	}
}

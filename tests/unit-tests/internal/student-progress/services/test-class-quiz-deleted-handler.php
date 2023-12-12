<?php

namespace SenseiTest\Internal\Student_Progress\Services;

use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Interface;
use Sensei\Internal\Student_Progress\Services\Quiz_Deleted_Handler;

class Quiz_Deleted_Handler_Test extends \WP_UnitTestCase {
	public function testInit_WhenCalled_AddsAction(): void {
		/* Arrange. */
		$quiz_progress_repository = $this->createMock( Quiz_Progress_Repository_Interface::class );
		$handler                  = new Quiz_Deleted_Handler( $quiz_progress_repository );

		/* Act. */
		$handler->init();

		/* Assert. */
		$actual_priority = has_action( 'deleted_post', [ $handler, 'handle' ] );
		$this->assertEquals( 10, $actual_priority );
	}

	public function testHandle_NonQuizPostGiven_DoesntCallDeleteMethod(): void {
		/* Arrange. */
		$deleted_post             = new \WP_Post( (object) [ 'post_type' => 'post' ] );
		$quiz_progress_repository = $this->createMock( Quiz_Progress_Repository_Interface::class );
		$handler                  = new Quiz_Deleted_Handler( $quiz_progress_repository );

		/* Expect & Act. */
		$quiz_progress_repository
			->expects( $this->never() )
			->method( 'delete_for_quiz' );
		$handler->handle( 1, $deleted_post );
	}

	public function testHandle_QuizGiven_CallsDeleteMethod(): void {
		/* Arrange. */
		$deleted_post             = new \WP_Post( (object) [ 'post_type' => 'quiz' ] );
		$quiz_progress_repository = $this->createMock( Quiz_Progress_Repository_Interface::class );
		$handler                  = new Quiz_Deleted_Handler( $quiz_progress_repository );

		/* Expect & Act. */
		$quiz_progress_repository->expects( $this->once() )->method( 'delete_for_quiz' )->with( 1 );
		$handler->handle( 1, $deleted_post );
	}
}

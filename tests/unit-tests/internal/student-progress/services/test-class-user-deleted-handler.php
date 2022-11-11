<?php

namespace SenseiTest\Internal\Student_Progress\Services;

use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Interface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Interface;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Interface;
use Sensei\Internal\Student_Progress\Services\User_Deleted_Handler;

class User_Deleted_Handler_Test extends \WP_UnitTestCase {
	public function testInit_WhenCalled_AddsAction(): void {
		/* Arrange. */
		$course_progress_repository = $this->createMock( Course_Progress_Repository_Interface::class );
		$lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );
		$quiz_progress_repository   = $this->createMock( Quiz_Progress_Repository_Interface::class );

		$handler = new User_Deleted_Handler( $course_progress_repository, $lesson_progress_repository, $quiz_progress_repository );

		/* Act. */
		$handler->init();

		/* Assert. */
		$this->assertEquals( 10, has_action( 'deleted_user', [ $handler, 'handle' ] ) );
	}

	public function testHandle_WhenCalled_CallsDeleteForAllProgress(): void {
		/* Arrange. */
		$course_progress_repository = $this->createMock( Course_Progress_Repository_Interface::class );
		$lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );
		$quiz_progress_repository   = $this->createMock( Quiz_Progress_Repository_Interface::class );

		$handler = new User_Deleted_Handler( $course_progress_repository, $lesson_progress_repository, $quiz_progress_repository );

		/* Expect & Act. */
		$course_progress_repository->expects( $this->once() )->method( 'delete_for_user' )->with( 1 );
		$lesson_progress_repository->expects( $this->once() )->method( 'delete_for_user' )->with( 1 );
		$quiz_progress_repository->expects( $this->once() )->method( 'delete_for_user' )->with( 1 );
		$handler->handle( 1 );
	}
}

<?php

namespace SenseiTest\Internal\Student_Progress\Services;

use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Interface;
use Sensei\Internal\Student_Progress\Services\Course_Deleted_Handler;
use WP_Post;

class Course_Deleted_Handler_Test extends \WP_UnitTestCase {
	public function testHandle_NonCoursePostGiven_DoesntCallDeleteMethod() {
		/* Arrange. */
		$course_progress_repository = $this->createMock( Course_Progress_Repository_Interface::class );

		$handler      = new Course_Deleted_Handler( $course_progress_repository );
		$deleted_post = new WP_Post( (object) [ 'post_type' => 'post' ] );

		/* Expect & Act. */
		$course_progress_repository
			->expects( $this->never() )
			->method( 'delete_for_course' );
		$handler->handle( 1, $deleted_post );
	}

	/**
	 * Tests that the handler deletes progress for a course post.
	 */
	public function testHandle_CourseGiven_CallsDeleteMethod() {
		$course_progress_repository = $this->createMock( Course_Progress_Repository_Interface::class );
		$course_progress_repository->expects( $this->once() )->method( 'delete_for_course' );

		$handler = new Course_Deleted_Handler( $course_progress_repository );
		$handler->handle( 1, new WP_Post( (object) [ 'post_type' => 'course' ] ) );
	}

	public function testInit_WhenCalled_AddsAction() {
		/* Arrange. */
		$course_progress_repository = $this->createMock( Course_Progress_Repository_Interface::class );
		$handler                    = new Course_Deleted_Handler( $course_progress_repository );

		/* Act. */
		$handler->init();

		/* Assert. */
		$actual_priority = has_action( 'deleted_post', [ $handler, 'handle' ] );
		$this->assertEquals( 10, $actual_priority );
	}
}


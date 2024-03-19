<?php

namespace SenseiTest\Internal\Emails\Generators;

use Sensei\Internal\Emails\Email_Repository;
use Sensei\Internal\Emails\Generators\Student_Completes_Lesson;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress_Interface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Interface;
use Sensei_Lesson;

/**
 * Tests for Sensei\Internal\Emails\Generators\Student_Completes_Lesson class.
 *
 * @covers \Sensei\Internal\Emails\Generators\Student_Completes_Lesson
 */
class Student_Completes_Lesson_Test extends \WP_UnitTestCase {
	public function testIsEmailActive_EmailNotFound_ReturnsFalse() {
		/* Arrange. */
		$email_repository = $this->createMock( Email_Repository::class );
		$email_repository->method( 'get' )->with( 'student_completes_lesson' )->willReturn( null );

		$lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );

		$generator = new Student_Completes_Lesson( $email_repository, $lesson_progress_repository );

		/* Act. */
		$is_active = $generator->is_email_active();

		/* Assert. */
		self::assertFalse( $is_active );
	}

	public function testIsEmailActive_EmailNotPublished_ReturnsFalse() {
		/* Arrange. */
		$email = new \WP_Post( (object) [ 'post_status' => 'draft' ] );

		$email_repository = $this->createMock( Email_Repository::class );
		$email_repository->method( 'get' )->with( 'student_completes_lesson' )->willReturn( $email );

		$lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );

		$generator = new Student_Completes_Lesson( $email_repository, $lesson_progress_repository );

		/* Act. */
		$is_active = $generator->is_email_active();

		/* Assert. */
		self::assertFalse( $is_active );
	}

	public function testIsEmailActive_PublishedEmailFound_ReturnsTrue() {
		/* Arrange. */
		$email = new \WP_Post( (object) [ 'post_status' => 'publish' ] );

		$email_repository = $this->createMock( Email_Repository::class );
		$email_repository->method( 'get' )->with( 'student_completes_lesson' )->willReturn( $email );

		$lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );

		$generator = new Student_Completes_Lesson( $email_repository, $lesson_progress_repository );

		/* Act. */
		$is_active = $generator->is_email_active();

		/* Assert. */
		self::assertTrue( $is_active );
	}

	public function testInit_WhenCalled_AddsHooksForInitializingIndividualEmails() {
		/* Arrange. */
		$email_repository           = $this->createMock( Email_Repository::class );
		$lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );
		$generator                  = new Student_Completes_Lesson( $email_repository, $lesson_progress_repository );

		$email = new \WP_Post( (object) [ 'post_status' => 'publish' ] );
		$email_repository->method( 'get' )->with( 'student_completes_lesson' )->willReturn( $email );
		/* Act. */
		$generator->init();

		/* Assert. */
		do_action( 'sensei_user_lesson_end', 1, 2 );
		$priority = has_action( 'sensei_user_lesson_end', [ $generator, 'student_completed_lesson_mail_to_teacher' ] );
		self::assertSame( 10, $priority );
	}

	public function testStudentCompletedLessonMailToTeacher_LessonProgressNotFound_DoesntCallSendEmailFilter() {
		/* Arrange. */
		$email_repository           = $this->createMock( Email_Repository::class );
		$lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );
		$generator                  = new Student_Completes_Lesson( $email_repository, $lesson_progress_repository );

		$filter_called = false;
		$filter        = function() use ( &$filter_called ) {
			$filter_called = true;
		};
		add_filter( 'sensei_email_send', $filter, 10, 2 );

		/* Act. */
		$generator->student_completed_lesson_mail_to_teacher( 1, 2 );

		/* Assert. */
		self::assertFalse( $filter_called );

		/* Cleanup. */
		remove_filter( 'sensei_email_send', $filter, 10 );
	}

	public function testStudentCompletedLessonMailToTeacher_LessonNotCompleted_DoesntCallSendEmailFilter() {
		/* Arrange. */
		$email_repository = $this->createMock( Email_Repository::class );

		$lesson_progress = $this->createMock( Lesson_Progress_Interface::class );
		$lesson_progress->method( 'is_complete' )->willReturn( false );

		$lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );
		$lesson_progress_repository->method( 'get' )->with( 2, 1 )->willReturn( $lesson_progress );

		$generator = new Student_Completes_Lesson( $email_repository, $lesson_progress_repository );

		$filter_called = false;
		$filter        = function() use ( &$filter_called ) {
			$filter_called = true;
		};
		add_filter( 'sensei_email_send', $filter, 10, 2 );

		/* Act. */
		$generator->student_completed_lesson_mail_to_teacher( 1, 2 );

		/* Assert. */
		self::assertFalse( $filter_called );

		/* Cleanup. */
		remove_filter( 'sensei_email_send', $filter, 10 );
	}

	public function testStudentCompletedLessonMailToTeacher_LessonCompleted_CallsSendEmailFilterWithMathchingArguments() {
		/* Arrange. */
		$factory          = new \Sensei_Factory();
		$student_id       = $factory->user->create(
			[
				'display_name' => 'Test Student',
			]
		);
		$teacher_id       = $factory->user->create(
			[
				'user_email' => 'test@a.com',
			]
		);
		$course_id        = $factory->course->create(
			[
				'post_title'  => 'Test Course',
				'post_author' => $teacher_id,
			]
		);
		$lesson_id        = $factory->lesson->create(
			[
				'post_title' => '“Lesson with Special Characters…?”',
				'meta_input' => [
					'_lesson_course' => $course_id,
				],
			]
		);
		$email_repository = $this->createMock( Email_Repository::class );

		$lesson_progress = $this->createMock( Lesson_Progress_Interface::class );
		$lesson_progress->method( 'is_complete' )->willReturn( true );

		$lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );
		$lesson_progress_repository->method( 'get' )->with( $lesson_id, $student_id )->willReturn( $lesson_progress );

		$generator = new Student_Completes_Lesson( $email_repository, $lesson_progress_repository );

		$actual_data = [];
		$filter      = function( $email, $options ) use ( &$actual_data ) {
			$actual_data = [
				'email'   => $email,
				'options' => $options,
			];
		};
		add_filter( 'sensei_email_send', $filter, 10, 2 );

		/* Act. */
		$generator->student_completed_lesson_mail_to_teacher( $student_id, $lesson_id );

		/* Assert. */
		$expected = [
			'email'   => 'student_completes_lesson',
			'options' => [
				'test@a.com' => [
					'student:id'          => $student_id,
					'student:displayname' => 'Test Student',
					'course:id'           => $course_id,
					'course:name'         => 'Test Course',
					'lesson:id'           => $lesson_id,
					'lesson:name'         => '“Lesson with Special Characters…?”',
					'manage:students'     => esc_url(
						admin_url( "admin.php?page=sensei_learners&course_id={$course_id}&lesson_id={$lesson_id}&view=learners" )
					),
				],
			],
		];
		self::assertSame( $expected, $actual_data );

		/* Cleanup. */
		remove_filter( 'sensei_email_send', $filter, 10 );
		$factory->tearDown();
	}

	public function testStudentCompletedLessonMailToTeacher_LessonCompletedAndTeachersFilterHooked_CallsSendEmailFilterWithMathchingArguments() {
		/* Arrange. */
		$factory          = new \Sensei_Factory();
		$student_id       = $factory->user->create(
			[
				'display_name' => 'Test Student',
			]
		);
		$teacher1_id      = $factory->user->create(
			[
				'user_email' => 'test@a.com',
			]
		);
		$teacher2_id      = $factory->user->create(
			[
				'user_email' => 'test@b.com',
			]
		);
		$course_id        = $factory->course->create(
			[
				'post_title'  => 'Test Course',
				'post_author' => $teacher1_id,
			]
		);
		$lesson_id        = $factory->lesson->create(
			[
				'post_title' => '“Lesson with Special Characters…?”',
				'meta_input' => [
					'_lesson_course' => $course_id,
				],
			]
		);
		$email_repository = $this->createMock( Email_Repository::class );

		$lesson_progress = $this->createMock( Lesson_Progress_Interface::class );
		$lesson_progress->method( 'is_complete' )->willReturn( true );

		$lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );
		$lesson_progress_repository->method( 'get' )->with( $lesson_id, $student_id )->willReturn( $lesson_progress );

		$generator = new Student_Completes_Lesson( $email_repository, $lesson_progress_repository );

		$teachers_filter = function( $teachers, $course_id ) use ( $teacher2_id ) {
			$teachers[] = $teacher2_id;
			return $teachers;
		};
		add_filter( 'sensei_email_course_teachers', $teachers_filter, 10, 2 );

		$actual_data = [];
		$filter      = function( $email, $options ) use ( &$actual_data ) {
			$actual_data = [
				'email'   => $email,
				'options' => $options,
			];
		};
		add_filter( 'sensei_email_send', $filter, 10, 2 );

		/* Act. */
		$generator->student_completed_lesson_mail_to_teacher( $student_id, $lesson_id );

		/* Assert. */
		$expected = [
			'email'   => 'student_completes_lesson',
			'options' => [
				'test@a.com' => [
					'student:id'          => $student_id,
					'student:displayname' => 'Test Student',
					'course:id'           => $course_id,
					'course:name'         => 'Test Course',
					'lesson:id'           => $lesson_id,
					'lesson:name'         => '“Lesson with Special Characters…?”',
					'manage:students'     => esc_url(
						admin_url( "admin.php?page=sensei_learners&course_id={$course_id}&lesson_id={$lesson_id}&view=learners" )
					),
				],
				'test@b.com' => [
					'student:id'          => $student_id,
					'student:displayname' => 'Test Student',
					'course:id'           => $course_id,
					'course:name'         => 'Test Course',
					'lesson:id'           => $lesson_id,
					'lesson:name'         => '“Lesson with Special Characters…?”',
					'manage:students'     => esc_url(
						admin_url( "admin.php?page=sensei_learners&course_id={$course_id}&lesson_id={$lesson_id}&view=learners" )
					),
				],
			],
		];
		self::assertSame( $expected, $actual_data );

		/* Cleanup. */
		remove_filter( 'sensei_email_send', $filter, 10 );
		remove_filter( 'sensei_email_course_teachers', $teachers_filter, 10, 2 );
		$factory->tearDown();
	}
}

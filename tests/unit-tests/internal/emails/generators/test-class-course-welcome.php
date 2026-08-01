<?php

namespace SenseiTest\Internal\Emails\Generators;

use Sensei\Internal\Emails\Email_Repository;
use Sensei\Internal\Emails\Generators\Course_Welcome;

/**
 * Tests for Sensei\Internal\Emails\Generators\Course_Welcome class.
 *
 * @covers \Sensei\Internal\Emails\Generators\Course_Welcome
 */
class Course_Welcome_Test extends \WP_UnitTestCase {
	public function testIsEmailActive_EmailNotFound_ReturnsFalse() {
		/* Arrange. */
		$email_repository = $this->createMock( Email_Repository::class );
		$email_repository->method( 'get' )->with( 'course_welcome' )->willReturn( null );

		$generator = new Course_Welcome( $email_repository );

		/* Act. */
		$is_active = $generator->is_email_active();

		/* Assert. */
		self::assertFalse( $is_active );
	}

	public function testIsEmailActive_EmailNotPublished_ReturnsFalse() {
		/* Arrange. */
		$email = new \WP_Post( (object) [ 'post_status' => 'draft' ] );

		$email_repository = $this->createMock( Email_Repository::class );
		$email_repository->method( 'get' )->with( 'course_welcome' )->willReturn( $email );

		$generator = new Course_Welcome( $email_repository );

		/* Act. */
		$is_active = $generator->is_email_active();

		/* Assert. */
		self::assertFalse( $is_active );
	}

	public function testIsEmailActive_PublishedEmailFound_ReturnsTrue() {
		/* Arrange. */
		$email = new \WP_Post( (object) [ 'post_status' => 'publish' ] );

		$email_repository = $this->createMock( Email_Repository::class );
		$email_repository->method( 'get' )->with( 'course_welcome' )->willReturn( $email );

		$generator = new Course_Welcome( $email_repository );

		/* Act. */
		$is_active = $generator->is_email_active();

		/* Assert. */
		self::assertTrue( $is_active );
	}

	public function testInit_WhenCalled_AddsHooksForInitializingIndividualEmails() {
		/* Arrange. */
		$email = new \WP_Post( (object) [ 'post_status' => 'publish' ] );

		$email_repository = $this->createMock( Email_Repository::class );
		$generator        = new Course_Welcome( $email_repository );

		$email_repository->method( 'get' )->with( 'course_welcome' )->willReturn( $email );

		/* Act. */
		$generator->init();

		/* Assert. */
		do_action( 'sensei_course_enrolment_status_changed', 1, 1 );
		do_action( 'sensei_pro_course_access_start_student_email_send', 1, 1 );
		do_action( 'sensei_email_sent', 'course_welcome', 'test@a.com', array() );

		$priority_for_immediate_start = has_action( 'sensei_course_enrolment_status_changed', [ $generator, 'welcome_to_course_for_student' ] );
		$priority_for_access_start    = has_action( 'sensei_pro_course_access_start_student_email_send', [ $generator, 'welcome_to_course_on_access_start' ] );
		$priority_for_mark_on_sent    = has_action( 'sensei_email_sent', array( $generator, 'mark_welcome_email_sent_on_dispatch' ) );
		self::assertSame( 10, $priority_for_immediate_start );
		self::assertSame( 10, $priority_for_access_start );
		self::assertSame( 10, $priority_for_mark_on_sent );
	}

	public function testWelcomeToCourseForStudent_WhenCalled_CallsSenseiEmailSendFilterWithMatchingArguments() {
		/* Arrange. */
		$factory    = new \Sensei_Factory();
		$student_id = $factory->user->create(
			[
				'display_name' => 'Test Student',
				'user_email'   => 'test@a.com',
			]
		);
		$teacher_id = $factory->user->create(
			[
				'display_name' => 'Test Teacher',
			]
		);
		$course_id  = $factory->course->create(
			[
				'post_title'  => '“Course with Special Characters…?”',
				'post_author' => $teacher_id,
			]
		);
		$lesson_id  = $factory->lesson->create(
			[
				'meta_input' => [ '_lesson_course' => $course_id ],
			]
		);

		$email_repository = $this->createMock( Email_Repository::class );
		$email_repository->method( 'get' )->with( 'course_welcome' )->willReturn( new \WP_Post( (object) [ 'post_status' => 'publish' ] ) );

		$generator = new Course_Welcome( $email_repository );

		$actual_data = [];
		$filter      = function ( $email, $options ) use ( &$actual_data ) {
			$actual_data = [
				'email'   => $email,
				'options' => $options,
			];
		};
		add_filter( 'sensei_email_send', $filter, 10, 2 );

		/* Act. */
		$generator->welcome_to_course_for_student( $student_id, $course_id );

		/* Assert. */
		$expected = [
			'email'   => 'course_welcome',
			'options' => [
				'test@a.com' => [
					'teacher:id'              => $teacher_id,
					'teacher:displayname'     => 'Test Teacher',
					'student:id'              => $student_id,
					'student:displayname'     => 'Test Student',
					'course:id'               => $course_id,
					'course:name'             => '“Course with Special Characters…?”',
					'course:url'              => esc_url(
						get_permalink( $course_id )
					),
					'course:first_lesson_url' => esc_url(
						get_permalink( $lesson_id )
					),
				],
			],
		];
		self::assertSame( $expected, $actual_data );

		/* Cleanup. */
		remove_filter( 'sensei_email_send', $filter, 10 );
		$factory->tearDown();
	}

	public function testWelcomeToCourseForStudent_WhenCalledForCourseWithoutLessons_CallsSenseiEmailSendFilterWithCourseUrlPlaceholder() {
		/* Arrange. */
		$factory    = new \Sensei_Factory();
		$student_id = $factory->user->create(
			[
				'display_name' => 'Test Student',
				'user_email'   => 'test@a.com',
			]
		);
		$teacher_id = $factory->user->create(
			[
				'display_name' => 'Test Teacher',
			]
		);
		$course_id  = $factory->course->create(
			[
				'post_title'  => 'Course without Lessons',
				'post_author' => $teacher_id,
			]
		);

		$email_repository = $this->createMock( Email_Repository::class );
		$email_repository->method( 'get' )->with( 'course_welcome' )->willReturn( new \WP_Post( (object) [ 'post_status' => 'publish' ] ) );

		$generator = new Course_Welcome( $email_repository );

		$actual_data = [];
		$filter      = function ( $email, $options ) use ( &$actual_data ) {
			$actual_data = [
				'email'   => $email,
				'options' => $options,
			];
		};
		add_filter( 'sensei_email_send', $filter, 10, 2 );

		/* Act. */
		$generator->welcome_to_course_for_student( $student_id, $course_id );

		/* Assert. */
		$actual_first_lesson_url = $actual_data['options']['test@a.com']['course:first_lesson_url'];
		self::assertSame( esc_url( get_permalink( $course_id ) ), $actual_first_lesson_url, 'First lesson url should fall back to the course url when the course has no lessons.' );

		/* Cleanup. */
		remove_filter( 'sensei_email_send', $filter, 10 );
		$factory->tearDown();
	}

	public function testWelcomeToCourseForStudent_WhenCalledForWPMLCopy_CallsEmailSendActionOnlyForTheRealCourse() {
		/* Arrange. */
		$factory    = new \Sensei_Factory();
		$student_id = $factory->user->create(
			[
				'display_name' => 'Test Student',
				'user_email'   => 'test@a.com',
			]
		);
		$teacher_id = $factory->user->create(
			[
				'display_name' => 'Test Teacher',
			]
		);
		$course_id  = $factory->course->create(
			[
				'post_title'  => '“Course with Special Characters…?”',
				'post_author' => $teacher_id,
			]
		);

		$course_id_translated = $factory->course->create(
			[
				'post_title'  => '“Course with Special Characters…? Translated”',
				'post_author' => $teacher_id,
			]
		);

		$email_repository = $this->createMock( Email_Repository::class );
		$email_repository->method( 'get' )->with( 'course_welcome' )->willReturn( new \WP_Post( (object) [ 'post_status' => 'publish' ] ) );

		$generator = new Course_Welcome( $email_repository );

		$actual_data = [];
		$filter      = function ( $email, $options ) use ( &$actual_data ) {
			$actual_data = [
				'email'   => $email,
				'options' => $options,
			];
		};
		add_filter( 'sensei_email_send', $filter, 10, 2 );

		add_filter(
			'wpml_original_element_id',
			function ( $modifiable, $current_course_id ) use ( $course_id_translated, $course_id ) {
				if ( $current_course_id === $course_id_translated ) {
					return "$course_id";
				}
				return $current_course_id;
			},
			10,
			3
		);

		/* Act. */
		$generator->welcome_to_course_for_student( $student_id, $course_id );
		$generator->welcome_to_course_for_student( $student_id, $course_id_translated );

		/* Assert. */
		$expected = [
			'email'   => 'course_welcome',
			'options' => [
				'test@a.com' => [
					'teacher:id'          => $teacher_id,
					'teacher:displayname' => 'Test Teacher',
					'student:id'          => $student_id,
					'student:displayname' => 'Test Student',
					'course:id'           => $course_id,
					'course:name'         => '“Course with Special Characters…?”',
					'course:url'          => esc_url(
						get_permalink( $course_id )
					),
				],
			],
		];
		self::assertSame( $expected, $actual_data );

		/* Cleanup. */
		remove_filter( 'sensei_email_send', $filter, 10 );
		$factory->tearDown();
	}

	public function testWelcomeToCourseForStudent_StudentAlreadyCompletedCourse_DoesNotSendEmail() {
		/* Arrange. */
		$factory    = new \Sensei_Factory();
		$student_id = $factory->user->create( array( 'user_email' => 'completed@a.com' ) );
		$course_id  = $factory->course->create();

		\Sensei_Utils::update_course_status( $student_id, $course_id, 'complete' );

		$email_repository = $this->createMock( Email_Repository::class );
		$email_repository->method( 'get' )->with( 'course_welcome' )->willReturn( new \WP_Post( (object) array( 'post_status' => 'publish' ) ) );

		$generator = new Course_Welcome( $email_repository );

		$actual_data = array();
		$filter      = function ( $email, $options ) use ( &$actual_data ) {
			$actual_data = array(
				'email'   => $email,
				'options' => $options,
			);
		};
		add_filter( 'sensei_email_send', $filter, 10, 2 );

		/* Act. */
		$generator->welcome_to_course_for_student( $student_id, $course_id );

		/* Cleanup. */
		remove_filter( 'sensei_email_send', $filter, 10 );
		$factory->tearDown();

		/* Assert. */
		self::assertSame( array(), $actual_data, 'Welcome email should not be sent to a student who already completed the course.' );
	}

	public function testWelcomeToCourseForStudent_StudentAlreadyStartedLessonInCourse_DoesNotSendEmail() {
		/* Arrange. */
		$factory    = new \Sensei_Factory();
		$student_id = $factory->user->create( array( 'user_email' => 'partway@a.com' ) );
		$course_id  = $factory->course->create();
		$lesson_id  = $factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );

		\Sensei_Utils::update_lesson_status( $student_id, $lesson_id, 'in-progress' );

		$email_repository = $this->createMock( Email_Repository::class );
		$email_repository->method( 'get' )->with( 'course_welcome' )->willReturn( new \WP_Post( (object) array( 'post_status' => 'publish' ) ) );

		$generator = new Course_Welcome( $email_repository );

		$actual_data = array();
		$filter      = function ( $email, $options ) use ( &$actual_data ) {
			$actual_data = array(
				'email'   => $email,
				'options' => $options,
			);
		};
		add_filter( 'sensei_email_send', $filter, 10, 2 );

		/* Act. */
		$generator->welcome_to_course_for_student( $student_id, $course_id );

		/* Cleanup. */
		remove_filter( 'sensei_email_send', $filter, 10 );
		$factory->tearDown();

		/* Assert. */
		self::assertSame( array(), $actual_data, 'Welcome email should not be sent to a student who already started a lesson in the course.' );
	}

	public function testWelcomeToCourseOnAccessStart_StudentOnlyStartedLessonInCourse_SendsEmail() {
		/* Arrange. */
		$factory    = new \Sensei_Factory();
		$student_id = $factory->user->create( array( 'user_email' => 'access-start-partway@a.com' ) );
		$course_id  = $factory->course->create();
		$lesson_id  = $factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );

		\Sensei_Utils::update_lesson_status( $student_id, $lesson_id, 'in-progress' );

		$email_repository = $this->createMock( Email_Repository::class );
		$email_repository->method( 'get' )->with( 'course_welcome' )->willReturn( new \WP_Post( (object) array( 'post_status' => 'publish' ) ) );

		$generator = new Course_Welcome( $email_repository );

		$actual_data = array();
		$filter      = function ( $email, $options ) use ( &$actual_data ) {
			$actual_data = array(
				'email'   => $email,
				'options' => $options,
			);
		};
		add_filter( 'sensei_email_send', $filter, 10, 2 );

		/* Act. */
		$generator->welcome_to_course_on_access_start( $student_id, $course_id );

		/* Cleanup. */
		remove_filter( 'sensei_email_send', $filter, 10 );
		$factory->tearDown();

		/* Assert. */
		self::assertNotSame( array(), $actual_data, 'Opening a lesson marks it in-progress even before access starts, so it must not stop the deferred welcome email.' );
	}

	public function testWelcomeToCourseOnAccessStart_StudentAlreadyCompletedCourse_DoesNotSendEmail() {
		/* Arrange. */
		$factory    = new \Sensei_Factory();
		$student_id = $factory->user->create( array( 'user_email' => 'access-start-completed@a.com' ) );
		$course_id  = $factory->course->create();

		\Sensei_Utils::update_course_status( $student_id, $course_id, 'complete' );

		$email_repository = $this->createMock( Email_Repository::class );
		$email_repository->method( 'get' )->with( 'course_welcome' )->willReturn( new \WP_Post( (object) array( 'post_status' => 'publish' ) ) );

		$generator = new Course_Welcome( $email_repository );

		$actual_data = array();
		$filter      = function ( $email, $options ) use ( &$actual_data ) {
			$actual_data = array(
				'email'   => $email,
				'options' => $options,
			);
		};
		add_filter( 'sensei_email_send', $filter, 10, 2 );

		/* Act. */
		$generator->welcome_to_course_on_access_start( $student_id, $course_id );

		/* Cleanup. */
		remove_filter( 'sensei_email_send', $filter, 10 );
		$factory->tearDown();

		/* Assert. */
		self::assertSame( array(), $actual_data, 'Completing a course proves earlier access, so a completed student should not be re-welcomed when a new access period starts.' );
	}

	public function testWelcomeToCourseForStudent_WelcomeAlreadyMarkedSent_DoesNotSendEmail() {
		/* Arrange. */
		$factory    = new \Sensei_Factory();
		$student_id = $factory->user->create( array( 'user_email' => 'already-sent@a.com' ) );
		$course_id  = $factory->course->create();

		update_user_meta( $student_id, Course_Welcome::get_welcome_sent_meta_key( $course_id ), '2026-01-01 00:00:00' );

		$email_repository = $this->createMock( Email_Repository::class );
		$email_repository->method( 'get' )->with( 'course_welcome' )->willReturn( new \WP_Post( (object) array( 'post_status' => 'publish' ) ) );

		$generator = new Course_Welcome( $email_repository );

		$actual_data = array();
		$filter      = function ( $email, $options ) use ( &$actual_data ) {
			$actual_data = array(
				'email'   => $email,
				'options' => $options,
			);
		};
		add_filter( 'sensei_email_send', $filter, 10, 2 );

		/* Act. */
		$generator->welcome_to_course_for_student( $student_id, $course_id );

		/* Cleanup. */
		remove_filter( 'sensei_email_send', $filter, 10 );
		$factory->tearDown();

		/* Assert. */
		self::assertSame( array(), $actual_data, 'Welcome email should not be sent when it has already been marked as sent for the course.' );
	}

	public function testWelcomeToCourseForStudent_CalledTwice_SendsEmailOnlyOnce() {
		/* Arrange. */
		$factory    = new \Sensei_Factory();
		$student_id = $factory->user->create( array( 'user_email' => 'once@a.com' ) );
		$course_id  = $factory->course->create();

		$email_repository = $this->createMock( Email_Repository::class );
		$email_repository->method( 'get' )->with( 'course_welcome' )->willReturn( new \WP_Post( (object) array( 'post_status' => 'publish' ) ) );

		$generator = new Course_Welcome( $email_repository );

		$send_count = 0;
		$filter     = function () use ( &$send_count ) {
			++$send_count;
		};
		add_filter( 'sensei_email_send', $filter );

		/* Act. */
		$generator->welcome_to_course_for_student( $student_id, $course_id );
		$generator->mark_welcome_email_sent_on_dispatch(
			'course_welcome',
			'once@a.com',
			array(
				'student:id' => $student_id,
				'course:id'  => $course_id,
			)
		);
		$generator->welcome_to_course_for_student( $student_id, $course_id );

		/* Cleanup. */
		remove_filter( 'sensei_email_send', $filter, 10 );
		$factory->tearDown();

		/* Assert. */
		self::assertSame( 1, $send_count, 'The welcome email should be sent only once even when the enrolment status changes repeatedly.' );
	}

	public function testMarkWelcomeEmailSentOnDispatch_ForCourseWelcome_MarksWelcomeAsSent() {
		/* Arrange. */
		$factory    = new \Sensei_Factory();
		$student_id = $factory->user->create( array( 'user_email' => 'dispatched@a.com' ) );
		$course_id  = $factory->course->create();

		$email_repository = $this->createMock( Email_Repository::class );
		$generator        = new Course_Welcome( $email_repository );

		/* Act. */
		$generator->mark_welcome_email_sent_on_dispatch(
			'course_welcome',
			'dispatched@a.com',
			array(
				'student:id' => $student_id,
				'course:id'  => $course_id,
			)
		);

		/* Assert. */
		$flag = get_user_meta( $student_id, Course_Welcome::get_welcome_sent_meta_key( $course_id ), true );
		self::assertNotEmpty( $flag, 'The welcome email sent flag should be set once the email has been dispatched.' );

		/* Cleanup. */
		$factory->tearDown();
	}

	public function testMarkWelcomeEmailSentOnDispatch_ForOtherEmail_DoesNotMark() {
		/* Arrange. */
		$factory    = new \Sensei_Factory();
		$student_id = $factory->user->create( array( 'user_email' => 'other-email@a.com' ) );
		$course_id  = $factory->course->create();

		$email_repository = $this->createMock( Email_Repository::class );
		$generator        = new Course_Welcome( $email_repository );

		/* Act. */
		$generator->mark_welcome_email_sent_on_dispatch(
			'some_other_email',
			'other-email@a.com',
			array(
				'student:id' => $student_id,
				'course:id'  => $course_id,
			)
		);

		/* Assert. */
		$flag = get_user_meta( $student_id, Course_Welcome::get_welcome_sent_meta_key( $course_id ), true );
		self::assertEmpty( $flag, 'The welcome email sent flag should not be set for a different email identifier.' );

		/* Cleanup. */
		$factory->tearDown();
	}
}

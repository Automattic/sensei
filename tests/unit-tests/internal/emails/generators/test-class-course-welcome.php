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
		do_action( 'sensei_user_course_start', 1, 1 );
		$priority = has_action( 'sensei_user_course_start', [ $generator, 'welcome_to_course_for_student' ] );
		self::assertSame( 10, $priority );
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

		$email_repository = $this->createMock( Email_Repository::class );
		$email_repository->method( 'get' )->with( 'course_welcome' )->willReturn( new \WP_Post( (object) [ 'post_status' => 'publish' ] ) );

		$generator = new Course_Welcome( $email_repository );

		$actual_data = [];
		$filter      = function( $email, $options ) use ( &$actual_data ) {
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
}

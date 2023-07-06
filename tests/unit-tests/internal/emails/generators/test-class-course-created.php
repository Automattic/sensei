<?php

namespace SenseiTest\Internal\Emails\Generators;

use Sensei\Internal\Emails\Email_Repository;
use Sensei\Internal\Emails\Generators\Course_Created;

/**
 * Tests for Sensei\Internal\Emails\Generators\Course_Created class.
 *
 * @covers \Sensei\Internal\Emails\Generators\Course_Created
 */
class Course_Created_Test extends \WP_UnitTestCase {

	public function testIsEmailActive_EmailNotFound_ReturnsFalse() {
		/* Arrange. */
		$email_repository = $this->createMock( Email_Repository::class );
		$email_repository->method( 'get' )->with( 'course_created' )->willReturn( null );

		$generator = new Course_Created( $email_repository );

		/* Act. */
		$is_active = $generator->is_email_active();

		/* Assert. */
		self::assertFalse( $is_active );
	}

	public function testIsEmailActive_EmailNotPublished_ReturnsFalse() {
		/* Arrange. */
		$email = new \WP_Post( (object) [ 'post_status' => 'draft' ] );

		$email_repository = $this->createMock( Email_Repository::class );
		$email_repository->method( 'get' )->with( 'course_created' )->willReturn( $email );

		$generator = new Course_Created( $email_repository );

		/* Act. */
		$is_active = $generator->is_email_active();

		/* Assert. */
		self::assertFalse( $is_active );
	}

	public function testIsEmailActive_PublishedEmailFound_ReturnsTrue() {
		/* Arrange. */
		$email = new \WP_Post( (object) [ 'post_status' => 'publish' ] );

		$email_repository = $this->createMock( Email_Repository::class );
		$email_repository->method( 'get' )->with( 'course_created' )->willReturn( $email );

		$generator = new Course_Created( $email_repository );

		/* Act. */
		$is_active = $generator->is_email_active();

		/* Assert. */
		self::assertTrue( $is_active );
	}

	public function testInit_WhenCalled_AddsHooksForInitializingIndividualEmails() {
		/* Arrange. */
		$email            = new \WP_Post( (object) [ 'post_status' => 'publish' ] );
		$email_repository = $this->createMock( Email_Repository::class );
		$generator        = new Course_Created( $email_repository );
		$email_repository->method( 'get' )->with( 'course_created' )->willReturn( $email );

		/* Act. */
		$generator->init();
		do_action( 'transition_post_status', 'draft', 'draft', $email );

		/* Assert. */
		$priority = has_action( 'transition_post_status', [ $generator, 'course_created_to_admin' ] );
		self::assertSame( 10, $priority );
	}

	public function testCourseCreatedToAdmin_WhenCalled_CallsSendEmailFilterWithMathchingArguments() {
		/* Arrange. */
		$factory          = new \Sensei_Factory();
		$admin_email      = get_option( 'admin_email', true );
		$teacher_id       = $factory->user->create(
			[
				'user_email'   => 'test@a.com',
				'display_name' => 'Test Teacher',
			]
		);
		$course_id        = $factory->course->create(
			[
				'post_title'  => 'Test Course',
				'post_author' => $teacher_id,
			]
		);
		$email_repository = $this->createMock( Email_Repository::class );
		$generator        = new Course_Created( $email_repository );

		$actual_data = [];
		$filter      = function( $email, $options ) use ( &$actual_data ) {
			$actual_data = [
				'email'   => $email,
				'options' => $options,
			];
		};
		add_filter( 'sensei_email_send', $filter, 10, 2 );

		/* Act. */
		$generator->course_created_to_admin( 'publish', 'draft', get_post( $course_id ) );

		/* Assert. */
		$expected = [
			'email'   => 'course_created',
			'options' => [
				$admin_email => [
					'teacher:id'          => $teacher_id,
					'teacher:displayname' => 'Test Teacher',
					'course:id'           => $course_id,
					'course:name'         => 'Test Course',
					'manage:course'       => esc_url(
						admin_url( "post.php?post={$course_id}&action=edit" )
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

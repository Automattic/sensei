<?php

namespace SenseiTest\Internal\Emails\Generators;

use Sensei\Internal\Emails\Email_Repository;
use Sensei\Internal\Emails\Generators\Student_Sends_Message;
use Sensei\Internal\Emails\Email_Customization;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Interface;
use Sensei_Factory;

/**
 * Tests for Sensei\Internal\Emails\Student_Sends_Message class.
 *
 * @covers \Sensei\Internal\Emails\Generators\Student_Sends_Message
 */
class Student_Sends_Message_Test extends \WP_UnitTestCase {
	use \Sensei_Test_Redirect_Helpers;

	/**
	 * Factory for creating test data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Email repository instance.
	 *
	 * @var Email_Repository
	 */
	protected $email_repository;

	/**
	 * Lesson progress repository instance.
	 *
	 * @var Lesson_Progress_Repository_Interface
	 */
	protected $lesson_progress_repository;

	public function setUp(): void {
		parent::setUp();

		$this->factory                    = new Sensei_Factory();
		$this->email_repository           = new Email_Repository();
		$this->lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );
		Email_Customization::instance( $this->createMock( \Sensei_Settings::class ), $this->createMock( \Sensei_Assets::class ), $this->lesson_progress_repository )->disable_legacy_emails();
		$this->prevent_wp_redirect();
	}

	public function testStudentSendsNewMessage_WhenEventFires_CallsEmailSendingActionWithRightData() {
		/* Arrange. */
		$student_id = $this->factory->user->create(
			[
				'display_name' => 'Test Student',
				'user_login'   => 'test_student',
			]
		);
		$teacher_id = $this->factory->user->create(
			[
				'user_email' => 'test@a.com',
				'user_login' => 'test_teacher',
			]
		);

		$course      = $this->factory->course->create_and_get( [ 'post_author' => $teacher_id ] );
		$message     = $this->factory->post->create_and_get(
			[
				'post_type'   => 'sensei_message',
				'post_title'  => '“Message with Special Characters…?”',
				'post_status' => 'publish',
				'post_author' => $student_id,
				'meta_input'  => [
					'_post'     => $course->ID,
					'_sender'   => 'test_student',
					'_receiver' => 'test_teacher',
				],
			]
		);
		$message_url = esc_url( get_permalink( $message->ID ) );

		( new Student_Sends_Message( $this->email_repository ) )->init();

		$email_data = [
			'name' => '',
			'data' => null,
		];

		add_action(
			'sensei_email_send',
			function ( $email_name, $replacements ) use ( &$email_data ) {
				$email_data['name'] = $email_name;
				$email_data['data'] = $replacements;
			},
			10,
			2
		);
		Email_Customization::instance( $this->createMock( \Sensei_Settings::class ), $this->createMock( \Sensei_Assets::class ), $this->lesson_progress_repository )->disable_legacy_emails();

		/* Act. */
		$this->expectException( \Sensei_WP_Redirect_Exception::class );
		do_action( 'sensei_new_private_message', $message->ID );

		/* Assert. */
		$this->expectException( WPDieException::class );
		self::assertEquals( 'student_sends_message', $email_data['name'] );
		self::assertArrayHasKey( 'test@a.com', $email_data['data'] );
		self::assertEquals( 'Test Student', $email_data['data']['test@a.com']['student:displayname'] );
		self::assertEquals( $course->post_title, $email_data['data']['test@a.com']['course:name'] );
		self::assertArrayHasKey( 'message:displaymessage', $email_data['data']['test@a.com'] );
		self::assertEquals( $message->post_content, $email_data['data']['test@a.com']['message:displaymessage'] );
		self::assertArrayHasKey( 'reply:url', $email_data['data']['test@a.com'] );
		self::assertEquals( $message_url, $email_data['data']['test@a.com']['reply:url'] );
	}

	public function testStudentSendsNewMessage_WhenCalled_DoesNotSendMailIfAnyOfTheUsersDoesNotExist() {
		/* Arrange. */
		$student_id = $this->factory->user->create(
			[
				'display_name' => 'Test Student',
				'user_login'   => 'test_student',
			]
		);
		$teacher_id = $this->factory->user->create(
			[
				'user_email' => 'test@a.com',
				'user_login' => 'test_teacher_test',
			]
		);

		$course  = $this->factory->course->create_and_get( [ 'post_author' => $teacher_id ] );
		$message = $this->factory->post->create_and_get(
			[
				'post_type'   => 'sensei_message',
				'post_title'  => '“Message with Special Characters…?”',
				'post_status' => 'publish',
				'post_author' => $student_id,
				'meta_input'  => [
					'_post'     => $course->ID,
					'_sender'   => 'test_student',
					'_receiver' => 'test_teacher',
				],
			]
		);

		( new Student_Sends_Message( $this->email_repository ) )->init();

		$email_data = [
			'name' => '',
			'data' => null,
		];

		add_action(
			'sensei_email_send',
			function ( $email_name, $replacements ) use ( &$email_data ) {
				$email_data['name'] = $email_name;
				$email_data['data'] = $replacements;
			},
			10,
			2
		);

		/* Act. */
		$this->expectException( \Sensei_WP_Redirect_Exception::class );
		do_action( 'sensei_new_private_message', $message->ID );

		/* Assert. */
		self::assertEquals( '', $email_data['name'] );
	}
}

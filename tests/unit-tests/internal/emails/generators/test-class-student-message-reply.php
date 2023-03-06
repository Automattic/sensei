<?php

namespace SenseiTest\Internal\Emails\Generators;

use Sensei\Internal\Emails\Email_Repository;
use Sensei\Internal\Emails\Generators\Student_Message_Reply;
use Sensei_Factory;

/**
 * Tests for Sensei\Internal\Emails\Student_Message_Reply class.
 *
 * @covers \Sensei\Internal\Emails\Student_Message_Reply
 */
class Student_Message_Reply_Test extends \WP_UnitTestCase {

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

	public function setUp(): void {
		parent::setUp();

		$this->factory          = new Sensei_Factory();
		$this->email_repository = new Email_Repository();
	}

	public function testTeacherRepliesMail_WhenCalled_CallsEmailSendingActionWithRightData() {
		/* Arrange. */
		$student_id = $this->factory->user->create(
			[
				'display_name' => 'Test Student',
				'user_login'   => 'test_student',
				'user_email'   => 'test@a.com',
			]
		);
		$teacher_id = $this->factory->user->create(
			[
				'display_name' => 'Test Teacher',
				'user_login'   => 'test_teacher',
			]
		);

		$data = $this->factory->get_course_with_lessons(
			[
				'course_args' => [
					'post_author' => $teacher_id,
				],
				'lesson_args' => [
					'post_author' => $teacher_id,
				],
			]
		);

		$course_id = $data['course_id'];
		$course    = get_post( $course_id );

		$message = $this->factory->post->create_and_get(
			[
				'post_type'   => 'sensei_message',
				'post_title'  => 'Test message',
				'post_status' => 'publish',
				'post_author' => $student_id,
				'meta_input'  => [
					'_post'     => $course->ID,
					'_sender'   => 'test_student',
					'_receiver' => 'test_teacher',
				],
			]
		);

		$comment_id = wp_insert_comment(
			[
				'comment_post_ID'  => $course_id,
				'user_id'          => $teacher_id,
				'comment_type'     => 'comment',
				'comment_content'  => 'Test reply',
				'comment_approved' => 1,
			]
		);

		$comment     = get_comment( $comment_id );
		$comment_url = esc_url( get_comment_link( $comment ) );

		( new Student_Message_Reply( $this->email_repository ) )->init();

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
		do_action( 'sensei_private_message_reply', $comment, $message );

		/* Assert. */
		self::assertEquals( 'student_message_reply', $email_data['name'] );
		self::assertArrayHasKey( 'test@a.com', $email_data['data'] );
		self::assertEquals( 'Test Teacher', $email_data['data']['test@a.com']['teacher:displayname'] );
		self::assertEquals( $course->post_title, $email_data['data']['test@a.com']['course:name'] );
		self::assertArrayHasKey( 'message:displaymessage', $email_data['data']['test@a.com'] );
		self::assertEquals( $comment->comment_content, $email_data['data']['test@a.com']['message:displaymessage'] );
		self::assertArrayHasKey( 'reply:url', $email_data['data']['test@a.com'] );
		self::assertEquals( $comment_url, $email_data['data']['test@a.com']['reply:url'] );
	}

	public function testReplyMail_WhenCalled_DoesNotSendMailToStudentWhenStudentReplies() {
		/* Arrange. */
		$student_id = $this->factory->user->create(
			[
				'display_name' => 'Test Student',
				'user_login'   => 'test_student',
				'user_email'   => 'test@a.com',
			]
		);
		$teacher_id = $this->factory->user->create(
			[
				'display_name' => 'Test Teacher',
				'user_login'   => 'test_teacher',
			]
		);

		$data = $this->factory->get_course_with_lessons(
			[
				'course_args' => [
					'post_author' => $teacher_id,
				],
				'lesson_args' => [
					'post_author' => $teacher_id,
				],
			]
		);

		$course_id = $data['course_id'];
		$course    = get_post( $course_id );

		$message = $this->factory->post->create_and_get(
			[
				'post_type'   => 'sensei_message',
				'post_title'  => 'Test message',
				'post_status' => 'publish',
				'post_author' => $student_id,
				'meta_input'  => [
					'_post'     => $course->ID,
					'_sender'   => 'test_student',
					'_receiver' => 'test_teacher',
				],
			]
		);

		$comment_id = wp_insert_comment(
			[
				'comment_post_ID'  => $course_id,
				'user_id'          => $student_id,
				'comment_type'     => 'comment',
				'comment_content'  => 'Test reply',
				'comment_approved' => 1,
			]
		);

		$comment = get_comment( $comment_id );

		( new Student_Message_Reply( $this->email_repository ) )->init();

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
		do_action( 'sensei_private_message_reply', $comment, $message );

		/* Assert. */
		self::assertEquals( '', $email_data['name'] );
	}
}

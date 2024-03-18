<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Repository;
use Sensei\Internal\Emails\Generators\Teacher_Message_Reply;
use Sensei_Factory;

/**
 * Tests for Sensei\Internal\Emails\Teacher_Message_Reply class.
 *
 * @covers \Sensei\Internal\Emails\Generators\Teacher_Message_Reply
 */
class Teacher_Message_Reply_Test extends \WP_UnitTestCase {

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
		$this->email_repository = $this->createMock( Email_Repository::class );
		$this->email_repository->method( 'get' )
			->willReturn( new \WP_Post( (object) [ 'post_status' => 'publish' ] ) );
	}

	public function testStudentRepliesMail_WhenCalled_CallsEmailSendingActionWithRightData() {
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
				'comment_content'  => '“Message Reply with Special Characters…?”',
				'comment_approved' => 1,
			]
		);

		$comment     = get_comment( $comment_id );
		$comment_url = esc_url( get_comment_link( $comment ) );

		( new Teacher_Message_Reply( $this->email_repository ) )->init();

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
		self::assertEquals( 'teacher_message_reply', $email_data['name'] );
		self::assertArrayHasKey( 'test@a.com', $email_data['data'] );
		self::assertEquals( 'Test Student', $email_data['data']['test@a.com']['student:displayname'] );
		self::assertEquals( $course->post_title, $email_data['data']['test@a.com']['course:name'] );
		self::assertArrayHasKey( 'message:displaymessage', $email_data['data']['test@a.com'] );
		self::assertEquals( $comment->comment_content, $email_data['data']['test@a.com']['message:displaymessage'] );
		self::assertArrayHasKey( 'reply:url', $email_data['data']['test@a.com'] );
		self::assertEquals( $comment_url, $email_data['data']['test@a.com']['reply:url'] );
	}

	public function testReplyMail_WhenCalled_DoesNotSendMailToTeacherWhenTeacherReplies() {
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

		$message = $this->factory->post->create_and_get(
			[
				'post_type'   => 'sensei_message',
				'post_title'  => 'Test message',
				'post_status' => 'publish',
				'post_author' => $student_id,
				'meta_input'  => [
					'_post'     => $course_id,
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
				'comment_content'  => '“Message Reply with Special Characters…?”',
				'comment_approved' => 1,
			]
		);
		$comment    = get_comment( $comment_id );

		( new Teacher_Message_Reply( $this->email_repository ) )->init();

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

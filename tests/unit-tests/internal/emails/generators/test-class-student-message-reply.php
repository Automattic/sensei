<?php

namespace SenseiTest\Internal\Emails\Generators;

use Sensei\Internal\Emails\Email_Repository;
use Sensei\Internal\Emails\Generators\Student_Message_Reply;
use Sensei_Factory;

/**
 * Tests for Sensei\Internal\Emails\Student_Message_Reply class.
 *
 * @covers \Sensei\Internal\Emails\Generators\Student_Message_Reply
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

	/**
	 * ID of the student who sent the original message.
	 *
	 * @var int
	 */
	private $student_id;

	/**
	 * ID of the teacher who replied to the original message.
	 *
	 * @var int
	 */
	private $teacher_id;

	/**
	 * Test data with courses, lessons and quizzes.
	 *
	 * @var array
	 */
	private $data;

	public function setUp(): void {
		parent::setUp();

		$this->factory          = new Sensei_Factory();
		$this->email_repository = $this->createMock( Email_Repository::class );
		$this->email_repository->method( 'get' )
			->willReturn( new \WP_Post( (object) [ 'post_status' => 'publish' ] ) );

		// Create some test data.
		$this->student_id = $this->factory->user->create(
			[
				'display_name' => 'Test Student',
				'user_login'   => 'test_student',
				'user_email'   => 'test@a.com',
			]
		);

		$this->teacher_id = $this->factory->user->create(
			[
				'display_name' => 'Test Teacher',
				'user_login'   => 'test_teacher',
			]
		);

		$this->data = $this->factory->get_course_with_lessons(
			[
				'course_args' => [
					'post_author' => $this->teacher_id,
				],
				'lesson_args' => [
					'post_author' => $this->teacher_id,
				],
			]
		);
	}

	public function testTeacherRepliesMail_WhenCalled_CallsEmailSendingActionWithRightData() {
		$course_id = $this->data['course_id'];
		$course    = get_post( $course_id );

		$message = $this->factory->post->create_and_get(
			[
				'post_type'   => 'sensei_message',
				'post_title'  => 'Test message',
				'post_status' => 'publish',
				'post_author' => $this->student_id,
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
				'user_id'          => $this->teacher_id,
				'comment_type'     => 'comment',
				'comment_content'  => '“Message Reply with Special Characters…?”',
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

	public function testTeacherRepliesMail_WhenMessageSentFromLesson_CallsEmailSendingActionWithRightData() {
		/* Arrange. */
		$course    = get_post( $this->data['course_id'] );
		$lesson_id = $this->data['lesson_ids'][0];
		$lesson    = get_post( $lesson_id );

		$message = $this->factory->post->create_and_get(
			[
				'post_type'   => 'sensei_message',
				'post_title'  => 'Test message',
				'post_status' => 'publish',
				'post_author' => $this->student_id,
				'meta_input'  => [
					'_post'     => $lesson_id,
					'_sender'   => 'test_student',
					'_receiver' => 'test_teacher',
				],
			]
		);

		$comment_id = wp_insert_comment(
			[
				'comment_post_ID'  => $lesson_id,
				'user_id'          => $this->teacher_id,
				'comment_type'     => 'comment',
				'comment_content'  => '“Message Reply with Special Characters…?”',
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

	public function testTeacherRepliesMail_WhenMessageSentFromQuiz_CallsEmailSendingActionWithRightData() {
		/* Arrange. */
		$course  = get_post( $this->data['course_id'] );
		$quiz_id = $this->data['quiz_ids'][0];
		$quiz    = get_post( $quiz_id );

		$message = $this->factory->post->create_and_get(
			[
				'post_type'   => 'sensei_message',
				'post_title'  => 'Test message',
				'post_status' => 'publish',
				'post_author' => $this->student_id,
				'meta_input'  => [
					'_post'     => $quiz_id,
					'_sender'   => 'test_student',
					'_receiver' => 'test_teacher',
				],
			]
		);

		$comment_id = wp_insert_comment(
			[
				'comment_post_ID'  => $quiz_id,
				'user_id'          => $this->teacher_id,
				'comment_type'     => 'comment',
				'comment_content'  => '“Message Reply with Special Characters…?”',
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
		$course_id = $this->data['course_id'];
		$course    = get_post( $course_id );

		$message = $this->factory->post->create_and_get(
			[
				'post_type'   => 'sensei_message',
				'post_title'  => 'Test message',
				'post_status' => 'publish',
				'post_author' => $this->student_id,
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
				'user_id'          => $this->student_id,
				'comment_type'     => 'comment',
				'comment_content'  => '“Message Reply with Special Characters…?”',
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

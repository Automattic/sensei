<?php
/**
 * File with class for testing Sensei Messages.
 *
 * @package sensei-tests
 */

/**
 * Class for testing Sensei_Messages class.
 *
 * @group messages
 *
 * phpcs:disable Generic.Commenting.DocComment.MissingShort
 */
class Sensei_Messages_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;
	use Sensei_Test_Redirect_Helpers;

	/**
	 * Factory object.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Make sure non-messages are not affected.
	 */
	public function testUserMessagesCapCheckNonMessage() {
		$this->login_as_teacher();

		$instance  = new Sensei_Messages();
		$course_id = $this->factory->course->create();

		$this->assertEquals( [], $instance->user_messages_cap_check( [], [ 'read' ], [ 'read_post', get_current_user_id(), $course_id ] ) );
	}

	/**
	 * Make sure participants in a private message have access to the message.
	 */
	public function testUserMessagesCapCheckAsParticipant() {
		$this->login_as_teacher();
		$course_id  = $this->factory->course->create();
		$teacher_id = get_current_user_id();

		$this->login_as_student();
		$student_id = get_current_user_id();

		$instance   = new Sensei_Messages();
		$message_id = $this->factory->message->create(
			[
				'meta_input' => [
					'_post'     => $course_id,
					'_posttype' => 'course',
					'_receiver' => get_user_by( 'ID', $teacher_id )->user_login,
					'_sender'   => get_user_by( 'ID', $student_id )->user_login,
				],
			]
		);

		$this->assertEquals( [ 'read' => true ], $instance->user_messages_cap_check( [], [ 'read' ], [ 'read_post', $teacher_id, $message_id ] ) );
		$this->assertEquals( [ 'read' => true ], $instance->user_messages_cap_check( [], [ 'read' ], [ 'read_post', $student_id, $message_id ] ) );
	}

	/**
	 * Make sure other students and teachers do not have access to messages but admins do.
	 */
	public function testUserMessagesCapCheckAsNonParticipant() {
		$this->login_as_teacher();
		$course_id  = $this->factory->course->create();
		$teacher_id = get_current_user_id();

		$this->login_as_student();
		$student_id = get_current_user_id();

		$instance   = new Sensei_Messages();
		$message_id = $this->factory->message->create(
			[
				'meta_input' => [
					'_post'     => $course_id,
					'_posttype' => 'course',
					'_receiver' => get_user_by( 'ID', $teacher_id )->user_login,
					'_sender'   => get_user_by( 'ID', $student_id )->user_login,
				],
			]
		);

		$this->login_as_teacher_b();
		$this->assertEquals( [ 'read' => false ], $instance->user_messages_cap_check( [], [ 'read' ], [ 'read_post', get_current_user_id(), $message_id ] ), 'Other teachers should not have access' );

		$this->login_as_student_b();
		$this->assertEquals( [ 'read' => false ], $instance->user_messages_cap_check( [], [ 'read' ], [ 'read_post', get_current_user_id(), $message_id ] ), 'Other students should not have access' );

		$this->login_as_admin();
		$this->assertEquals( [ 'read' => true ], $instance->user_messages_cap_check( [], [ 'read' ], [ 'read_post', get_current_user_id(), $message_id ] ), 'Admins should still have access' );
	}

	public function testSaveNewMessagePost_WhenSuccessful_TriggersHook() {
		/* Arrange. */
		$teacher_id = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$student_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$course_id  = $this->factory->course->create();
		$instance   = new Sensei_Messages();

		// Remove the hooks to avoid side effects.
		remove_all_actions( 'sensei_new_private_message' );

		/* Act. */
		$instance->save_new_message_post( $student_id, $teacher_id, 'message', $course_id );

		/* Assert. */
		$this->assertEquals( 1, did_action( 'sensei_new_private_message' ) );
	}

	public function testShowSuccessNotice_WhenNotRestRequest_DoesRedirect() {
		/* Arrange. */
		$instance = new Sensei_Messages();

		$this->prevent_wp_redirect();

		/* Act. */
		try {
			$instance->show_success_notice();
		} catch ( \Sensei_WP_Redirect_Exception $e ) {
			$redirect_status   = $e->getCode();
			$redirect_location = $e->getMessage();
		}

		/* Assert. */
		$this->assertSame( 302, $redirect_status );
		$this->assertStringContainsString( 'send=complete', $redirect_location );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testShowSuccessNotice_WhenRestRequest_DoesNotRedirect() {
		/* Arrange. */
		$instance = new Sensei_Messages();

		define( 'REST_REQUEST', true );

		$this->prevent_wp_redirect();

		/* Act. */
		try {
			$instance->show_success_notice();
		} catch ( \Sensei_WP_Redirect_Exception $e ) {
			$redirect_status = $e->getCode();
		}

		/* Assert. */
		$this->assertFalse( isset( $redirect_status ) );
	}

	public function testGettingMessageContentAndTitle_WhenGot_ReplacesBracketsByUnicode() {
		$this->login_as_teacher();
		$course_id  = $this->factory->course->create();
		$teacher_id = get_current_user_id();

		$this->login_as_student();
		$student_id = get_current_user_id();

		$instance   = new Sensei_Messages();
		$message_id = $this->factory->message->create(
			[
				'meta_input' => [
					'_post'     => $course_id,
					'_posttype' => 'course',
					'_receiver' => get_user_by( 'ID', $teacher_id )->user_login,
					'_sender'   => get_user_by( 'ID', $student_id )->user_login,
				],
			]
		);

		$this->go_to( get_permalink( $message_id ) );

		$content = $instance->message_content( 'This is a message with [brackets] [[brackets]] [[[brackets]]].' );
		$title   = $instance->message_title( 'This is a title with [brackets] [[brackets]] [[[brackets]]].' );

		$this->assertStringNotContainsString( '[', $content );
		$this->assertStringNotContainsString( ']', $content );
		$this->assertStringNotContainsString( '[', $title );
		$this->assertStringNotContainsString( ']', $title );
		$this->assertStringContainsString( '&#91;', $content );
		$this->assertStringContainsString( '&#93;', $content );
		$this->assertStringContainsString( '&#91;', $title );
		$this->assertStringContainsString( '&#93;', $title );
	}

	public function testGettingPostContentAndTitle_DoesNotReplaceBrackets_IfNotSingleMessagePostInLoop() {
		$this->login_as_teacher();

		$instance = new Sensei_Messages();
		$post_id  = $this->factory->post->create();

		$this->go_to( get_permalink( $post_id ) );

		$content = $instance->message_content( 'This is a message with [brackets] [[brackets]] [[[brackets]]].' );
		$title   = $instance->message_title( 'This is a title with [brackets] [[brackets]] [[[brackets]]].' );

		$this->assertEquals( 'This is a message with [brackets] [[brackets]] [[[brackets]]].', $content );
		$this->assertEquals( 'This is a title with [brackets] [[brackets]] [[[brackets]]].', $title );
	}
}

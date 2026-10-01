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

	public function testSaveNewMessage_PostIdOfCourseTheUserIsNotEnrolledInGiven_DoesNotCreateMessage() {
		/* Arrange. */
		$teacher_id = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$course_a   = $this->factory->course->create( array( 'post_author' => $teacher_id ) );
		$course_b   = $this->factory->course->create( array( 'post_author' => $teacher_id ) );
		$student_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		// The attacker is enrolled in course A only, and targets course B.
		$this->enrol_only_in( $student_id, $course_a );

		/* Act. */
		$messages = $this->submit_contact_form( $student_id, $course_b );

		/* Assert. */
		$this->assertSame( 0, $messages );
	}

	public function testSaveNewMessage_PostIdOfLessonInEnrolledCourseGiven_CreatesMessage() {
		/* Arrange. */
		$student_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$course     = $this->create_course_with_a_lesson_and_quiz();

		$this->enrol_only_in( $student_id, $course['course_id'] );

		/* Act. */
		$messages = $this->submit_contact_form( $student_id, $course['lesson_ids'][0] );

		/* Assert. */
		$this->assertSame( 1, $messages );
	}

	public function testSaveNewMessage_PostIdOfQuizInEnrolledCourseGiven_CreatesMessage() {
		/* Arrange. */
		$student_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$course     = $this->create_course_with_a_lesson_and_quiz();

		$this->enrol_only_in( $student_id, $course['course_id'] );

		/* Act. */
		$messages = $this->submit_contact_form( $student_id, $course['quiz_ids'][0] );

		/* Assert. */
		$this->assertSame( 1, $messages );
	}

	public function testSaveNewMessage_PostIdOfEnrolledCourseGiven_CreatesMessage() {
		/* Arrange. */
		$teacher_id = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$course_id  = $this->factory->course->create( array( 'post_author' => $teacher_id ) );
		$student_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		$this->enrol_only_in( $student_id, $course_id );

		/* Act. */
		$messages = $this->submit_contact_form( $student_id, $course_id );

		/* Assert. */
		$this->assertSame( 1, $messages );
	}

	public function testSaveNewMessage_PostIdThatDoesNotResolveToAPostGiven_DoesNotCreateMessage() {
		/* Arrange. */
		$student_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		/* Act. */
		$messages = $this->submit_contact_form( $student_id, PHP_INT_MAX );

		/* Assert. */
		$this->assertSame( 0, $messages );
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

	public function testPreventMessageCanonicalRedirect_AnonymousRequestedMessageGiven_ReturnsFalse() {
		/* Arrange. */
		$message_id = $this->create_message();
		$this->logout();
		$this->go_to( '/?p=' . $message_id );
		$instance = new Sensei_Messages();

		/* Act. */
		$actual = $instance->prevent_message_canonical_redirect( 'https://example.org/messages/secret/' );

		/* Assert. */
		$this->assertFalse( $actual );
	}

	public function testPreventMessageCanonicalRedirect_ParticipantRequestedMessageGiven_ReturnsRedirectUrl() {
		/* Arrange. */
		$message_id = $this->create_message();
		$this->login_as_student();
		$this->go_to( '/?p=' . $message_id );
		$instance = new Sensei_Messages();

		/* Act. */
		$actual = $instance->prevent_message_canonical_redirect( 'https://example.org/messages/secret/' );

		/* Assert. */
		$this->assertSame( 'https://example.org/messages/secret/', $actual );
	}

	public function testPreventMessageCanonicalRedirect_AnonymousRequestedMessagePermalinkGiven_ReturnsFalse() {
		/* Arrange. */
		$message_id = $this->create_message();
		$this->logout();
		$this->go_to( get_permalink( $message_id ) );
		$instance = new Sensei_Messages();

		/* Act. */
		$actual = $instance->prevent_message_canonical_redirect( 'https://example.org/messages/secret/' );

		/* Assert. */
		$this->assertFalse( $actual );
	}

	public function testPreventMessageCanonicalRedirect_NonParticipantTeacherRequestedPrivateMessageGiven_ReturnsFalse() {
		/* Arrange. */
		$message_id = $this->create_message( array( 'post_status' => 'private' ) );
		$this->login_as_teacher_b();
		$this->go_to( '/?p=' . $message_id );
		$instance = new Sensei_Messages();

		/* Act. */
		$actual = $instance->prevent_message_canonical_redirect( 'https://example.org/messages/secret/' );

		/* Assert. */
		$this->assertFalse( $actual );
	}

	public function testPreventMessageCanonicalRedirect_AnonymousRequestedRegularPostGiven_ReturnsRedirectUrl() {
		/* Arrange. */
		$post_id = $this->factory->post->create();
		$this->logout();
		$this->go_to( '/?p=' . $post_id );
		$instance = new Sensei_Messages();

		/* Act. */
		$actual = $instance->prevent_message_canonical_redirect( 'https://example.org/hello-world/' );

		/* Assert. */
		$this->assertSame( 'https://example.org/hello-world/', $actual );
	}

	public function testMessageLogin_AnonymousRequestedMessageGiven_RedirectsThroughTheMessageId() {
		/* Arrange. */
		$my_courses_page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		add_filter( 'sensei_settings_my_course_page_id', fn() => $my_courses_page_id );
		$message_id = $this->create_message();
		$this->logout();
		$this->go_to( get_permalink( $message_id ) );
		$this->prevent_wp_redirect();
		$instance = new Sensei_Messages();

		/* Act. */
		try {
			$instance->message_login();
		} catch ( Sensei_WP_Redirect_Exception $e ) {
			$redirect_location = $e->getMessage();
		}

		/* Assert. */
		$this->assertSame(
			add_query_arg( 'redirect_to', home_url( '/?p=' . $message_id ), get_permalink( $my_courses_page_id ) ),
			$redirect_location
		);
	}

	public function testMessageLogin_AnonymousRequestedMessageIdGiven_KeepsTheNotFoundResponse() {
		/* Arrange. */
		$message_id = $this->create_message();
		$this->logout();
		$this->go_to( '/?p=' . $message_id );
		$this->prevent_wp_redirect();
		$instance = new Sensei_Messages();

		/* Act. */
		$instance->message_login();

		/* Assert. */
		$this->assertTrue( is_404() );
	}

	public function testOnlyShowMessagesToOwner_NonParticipantTeacherRequestedMessageGiven_ReturnsNotFound() {
		/* Arrange. */
		$message_id = $this->create_message();
		$this->login_as_teacher_b();

		/* Act. */
		$this->go_to( '/?post_type=sensei_message&p=' . $message_id );

		/* Assert. */
		$this->assertTrue( is_404() );
	}

	public function testOnlyShowMessagesToOwner_ParticipantTeacherRequestedMessageGiven_ReturnsTheMessage() {
		/* Arrange. */
		$message_id = $this->create_message();
		$this->login_as_teacher();

		/* Act. */
		$this->go_to( '/?post_type=sensei_message&p=' . $message_id );

		/* Assert. */
		$this->assertSame( $message_id, get_queried_object_id() );
	}

	public function testRemoveSenseiMessageFromPostTypeArray_AnonymousRequestedMessageArchiveAsArray_ReturnsNoMessages() {
		/* Arrange. */
		$this->create_message();
		$this->logout();

		/* Act. */
		$this->go_to( '/?post_type[]=sensei_message' );

		/* Assert. */
		$this->assertNotContains( 'sensei_message', wp_list_pluck( $GLOBALS['wp_query']->posts, 'post_type' ) );
	}

	public function testRemoveSenseiMessageFromPostTypeArray_StringGiven_LeavesItUntouched() {
		/* Arrange. */
		$instance   = new Sensei_Messages();
		$query_vars = array( 'post_type' => 'sensei_message' );

		/* Act. */
		$actual = $instance->remove_sensei_message_from_post_type_array( $query_vars );

		/* Assert. */
		$this->assertSame( 'sensei_message', $actual['post_type'] );
	}

	public function testRemoveSenseiMessageFromPostTypeArray_MixedArrayWithMessagesGiven_DropsTheMessages() {
		/* Arrange. */
		$instance   = new Sensei_Messages();
		$query_vars = array( 'post_type' => array( 'post', 'sensei_message' ) );

		/* Act. */
		$actual = $instance->remove_sensei_message_from_post_type_array( $query_vars );

		/* Assert. */
		$this->assertSame( array( 'post' ), $actual['post_type'] );
	}

	public function testRemoveSenseiMessageFromPostTypeArray_OnlyMessagesGiven_ReturnsAnEmptyArray() {
		/* Arrange. */
		$instance   = new Sensei_Messages();
		$query_vars = array( 'post_type' => array( 'sensei_message' ) );

		/* Act. */
		$actual = $instance->remove_sensei_message_from_post_type_array( $query_vars );

		/* Assert. */
		$this->assertSame( array(), $actual['post_type'] );
	}

	/**
	 * Create a private message from the shared student to the shared teacher.
	 *
	 * @param array $args Extra post arguments.
	 * @return int Message ID.
	 */
	private function create_message( array $args = array() ): int {
		return $this->factory->message->create(
			$args + array(
				'meta_input' => array(
					'_receiver' => get_userdata( $this->get_user_by_role( 'teacher' ) )->user_login,
					'_sender'   => get_userdata( $this->get_user_by_role( 'subscriber' ) )->user_login,
				),
			)
		);
	}

	/**
	 * Treat the given student as enrolled in the given course and in no other.
	 *
	 * Scoped to that student on purpose, so a test cannot pass if the code under test
	 * checks a different account.
	 *
	 * The real enrolment providers keep cached results keyed by user and course id, and
	 * both ids are reused across the suite, so enrolling for real is not reliable here.
	 * This filter is the documented way to override the check, and WP_UnitTestCase
	 * restores the hooks after each test.
	 *
	 * @param int $student_id Student the enrolment applies to.
	 * @param int $course_id  Course the student is enrolled in.
	 */
	private function enrol_only_in( int $student_id, int $course_id ) {
		add_filter(
			'sensei_is_enrolled',
			function ( $is_enrolled, $user_id, $checked_course_id ) use ( $student_id, $course_id ) {
				if ( (int) $user_id !== $student_id ) {
					return $is_enrolled;
				}

				return (int) $checked_course_id === $course_id;
			},
			10,
			3
		);
	}

	/**
	 * Create a course with one lesson and its quiz, all owned by the same teacher.
	 *
	 * The teacher matters because a message is addressed to the author of the submitted post.
	 *
	 * @return array Keys course_id, lesson_ids and quiz_ids, as returned by the factory.
	 */
	private function create_course_with_a_lesson_and_quiz(): array {
		$teacher_id = $this->factory->user->create( array( 'role' => 'teacher' ) );

		return $this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 1,
				'question_count' => 0,
				'course_args'    => array( 'post_author' => $teacher_id ),
				'lesson_args'    => array( 'post_author' => $teacher_id ),
				'quiz_args'      => array( 'post_author' => $teacher_id ),
			)
		);
	}

	/**
	 * Submit the contact form as the given user and return how many messages they have afterwards.
	 *
	 * @param int $student_id User sending the message.
	 * @param int $post_id    Value submitted as post_id, which is what the attack tampers with.
	 */
	private function submit_contact_form( int $student_id, int $post_id ): int {
		wp_set_current_user( $student_id );

		// The constructor registers the listener that redirects on message creation,
		// so the instance has to exist before the hooks are removed.
		$instance = new Sensei_Messages();
		remove_all_actions( 'sensei_new_private_message' );

		$_POST[ Sensei_Messages::NONCE_FIELD_NAME ] = wp_create_nonce( Sensei_Messages::NONCE_ACTION_NAME );
		$_POST['post_id']                           = $post_id;
		$_POST['contact_message']                   = 'Message';

		$instance->save_new_message();

		return count(
			get_posts(
				array(
					'post_type'   => 'sensei_message',
					'author'      => $student_id,
					'post_status' => 'any',
				)
			)
		);
	}
}

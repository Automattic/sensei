<?php
/**
 * Sensei REST API: private message replies stay restricted to participants on the core comments endpoint.
 *
 * @package sensei-lms
 */

/**
 * Asserts that approved replies on a private sensei_message are not exposed through
 * WordPress core's comments REST API to users who cannot read the message.
 */
class Sensei_Message_Comments_REST_Tests extends WP_Test_REST_TestCase {

	/**
	 * A server instance used to dispatch requests.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Message post id.
	 *
	 * @var int
	 */
	protected $message_id;

	/**
	 * Approved reply comment id.
	 *
	 * @var int
	 */
	protected $reply_id;

	/**
	 * Sender (participant) user id.
	 *
	 * @var int
	 */
	protected $sender_id;

	/**
	 * Receiver (participant) user id.
	 *
	 * @var int
	 */
	protected $receiver_id;

	public function setUp(): void {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );

		$this->sender_id   = $this->factory->user->create(
			array(
				'role'       => 'subscriber',
				'user_login' => 'sender_login',
			)
		);
		$this->receiver_id = $this->factory->user->create(
			array(
				'role'       => 'subscriber',
				'user_login' => 'receiver_login',
			)
		);

		$this->message_id = $this->factory->post->create(
			array(
				'post_type'   => 'sensei_message',
				'post_status' => 'publish',
				'post_title'  => 'Private message',
				'meta_input'  => array(
					'_sender'   => 'sender_login',
					'_receiver' => 'receiver_login',
				),
			)
		);

		$this->reply_id = $this->factory->comment->create(
			array(
				'comment_post_ID'  => $this->message_id,
				'comment_content'  => 'SECRET private reply',
				'comment_approved' => 1,
				'user_id'          => $this->receiver_id,
			)
		);
	}

	public function tearDown(): void {
		parent::tearDown();

		global $wp_rest_server;
		$wp_rest_server = null;
	}

	/**
	 * An anonymous visitor querying a message's comments must be denied.
	 */
	public function testCheckReadPermission_AnonymousRequestedMessageComments_ReturnsUnauthorized() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'GET', '/wp/v2/comments' );
		$request->set_param( 'post', $this->message_id );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status(), 'Anonymous visitor must be denied private message replies.' );
	}

	/**
	 * An anonymous visitor reading a reply by comment id must be denied.
	 */
	public function testCheckReadPermission_AnonymousRequestedMessageCommentById_ReturnsUnauthorized() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'GET', '/wp/v2/comments/' . $this->reply_id );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status(), 'Anonymous direct comment read must be denied.' );
	}

	/**
	 * A logged-in user who is not a participant must be denied a message's comments.
	 */
	public function testCheckReadPermission_NonParticipantRequestedMessageComments_ReturnsForbidden() {
		$other = $this->factory->user->create(
			array(
				'role'       => 'subscriber',
				'user_login' => 'stranger_login',
			)
		);
		wp_set_current_user( $other );
		$request = new WP_REST_Request( 'GET', '/wp/v2/comments' );
		$request->set_param( 'post', $this->message_id );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status(), 'A non-participant must be denied private message replies.' );
	}

	/**
	 * The message receiver reading a reply by comment id must get the reply.
	 */
	public function testCheckReadPermission_ReceiverRequestedMessageCommentById_ReturnsComment() {
		wp_set_current_user( $this->receiver_id );
		$request = new WP_REST_Request( 'GET', '/wp/v2/comments/' . $this->reply_id );

		$response = $this->server->dispatch( $request );

		$this->assertSame( $this->reply_id, $response->get_data()['id'], 'The receiver must be able to read a reply on their own message.' );
	}

	/**
	 * The message sender reading a reply by comment id must get the reply.
	 */
	public function testCheckReadPermission_SenderRequestedMessageCommentById_ReturnsComment() {
		wp_set_current_user( $this->sender_id );
		$request = new WP_REST_Request( 'GET', '/wp/v2/comments/' . $this->reply_id );

		$response = $this->server->dispatch( $request );

		$this->assertSame( $this->reply_id, $response->get_data()['id'], 'The sender must be able to read a reply on their own message.' );
	}

	/**
	 * An administrator reading a reply by comment id must get the reply.
	 */
	public function testCheckReadPermission_AdminRequestedMessageCommentById_ReturnsComment() {
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$request = new WP_REST_Request( 'GET', '/wp/v2/comments/' . $this->reply_id );

		$response = $this->server->dispatch( $request );

		$this->assertSame( $this->reply_id, $response->get_data()['id'], 'An administrator must still read the message reply.' );
	}

	/**
	 * A teacher who is not a participant of the message must be denied a reply by comment id.
	 */
	public function testCheckReadPermission_NonParticipantTeacherRequestedMessageCommentById_ReturnsForbidden() {
		$teacher = $this->factory->user->create(
			array(
				'role'       => 'teacher',
				'user_login' => 'other_teacher_login',
			)
		);
		wp_set_current_user( $teacher );
		$request = new WP_REST_Request( 'GET', '/wp/v2/comments/' . $this->reply_id );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status(), 'A teacher who is not a participant must be denied the message reply.' );
	}

	/**
	 * The message receiver querying their message's comments must get the reply.
	 */
	public function testCheckReadPermission_ReceiverRequestedMessageComments_ReturnsComment() {
		wp_set_current_user( $this->receiver_id );
		$request = new WP_REST_Request( 'GET', '/wp/v2/comments' );
		$request->set_param( 'post', $this->message_id );

		$response = $this->server->dispatch( $request );

		$this->assertCount( 1, $response->get_data(), 'A participant must be able to read replies on their own message.' );
	}

	/**
	 * An administrator querying a message's comments must get the reply.
	 */
	public function testCheckReadPermission_AdminRequestedMessageComments_ReturnsComment() {
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$request = new WP_REST_Request( 'GET', '/wp/v2/comments' );
		$request->set_param( 'post', $this->message_id );

		$response = $this->server->dispatch( $request );

		$this->assertCount( 1, $response->get_data(), 'An administrator must still see message replies.' );
	}

	/**
	 * The unscoped comments listing must exclude message replies for anonymous visitors while
	 * still returning ordinary public comments.
	 */
	public function testCheckReadPermission_AnonymousRequestedAllComments_ExcludesMessageComment() {
		$post_id    = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$comment_id = $this->factory->comment->create(
			array(
				'comment_post_ID'  => $post_id,
				'comment_approved' => 1,
			)
		);
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'GET', '/wp/v2/comments' );

		$response = $this->server->dispatch( $request );

		$ids = wp_list_pluck( $response->get_data(), 'id' );
		$this->assertContains( $comment_id, $ids, 'The ordinary post comment must appear in the unscoped listing.' );
		$this->assertNotContains( $this->reply_id, $ids, 'A message reply must not appear in the unscoped listing.' );
	}

	/**
	 * Regression: comments on ordinary posts must remain publicly readable.
	 */
	public function testCheckReadPermission_AnonymousRequestedRegularPostComments_ReturnsComment() {
		$post_id    = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$comment_id = $this->factory->comment->create(
			array(
				'comment_post_ID'  => $post_id,
				'comment_approved' => 1,
			)
		);
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'GET', '/wp/v2/comments' );
		$request->set_param( 'post', $post_id );

		$response = $this->server->dispatch( $request );

		$this->assertSame( $comment_id, $response->get_data()[0]['id'], 'Comments on ordinary posts must remain readable.' );
	}
}

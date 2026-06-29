<?php
/**
 * Sensei REST API: private message replies must not leak through the core comments endpoint.
 *
 * @package sensei-lms
 */

/**
 * Integration tests asserting that approved replies on a private sensei_message are not exposed
 * through WordPress core's public comments REST API to users who cannot moderate comments.
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

		$this->factory->user->create(
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
	 * Anonymous visitors must not see message replies in the comments collection.
	 */
	public function testAnonymousCannotListMessageCommentsViaRest() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', '/wp/v2/comments' );
		$request->set_param( 'post', $this->message_id );
		$response = $this->server->dispatch( $request );

		$this->assertCount( 0, $response->get_data(), 'Anonymous visitor must not receive private message replies.' );
	}

	/**
	 * Anonymous visitors must not read a message reply directly by comment id.
	 */
	public function testAnonymousCannotReadSingleMessageCommentViaRest() {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/comments/' . $this->reply_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status(), 'Anonymous direct comment read must be denied.' );
	}

	/**
	 * Comment moderators must still be able to read a message reply directly by comment id.
	 */
	public function testModeratorCanReadSingleMessageCommentViaRest() {
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/comments/' . $this->reply_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status(), 'A moderator must still read the message reply.' );
		$this->assertEquals( $this->reply_id, $response->get_data()['id'], 'The requested reply must be returned.' );
	}

	/**
	 * Regression: a comment on an ordinary post must remain readable directly by comment id.
	 */
	public function testRegularPostSingleCommentRemainsReadableViaRest() {
		$post_id    = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$comment_id = $this->factory->comment->create(
			array(
				'comment_post_ID'  => $post_id,
				'comment_approved' => 1,
			)
		);
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/comments/' . $comment_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status(), 'An ordinary post comment must stay readable by id.' );
		$this->assertEquals( $comment_id, $response->get_data()['id'], 'The ordinary post comment must be returned.' );
	}

	/**
	 * A logged-in user who is not a participant must not see the replies.
	 */
	public function testNonParticipantCannotListMessageCommentsViaRest() {
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

		$this->assertCount( 0, $response->get_data(), 'A non-participant must not receive private message replies.' );
	}

	/**
	 * A participant without comment-moderation rights does not get replies through the generic
	 * comments endpoint; the message thread is rendered server-side instead.
	 */
	public function testParticipantDoesNotListMessageCommentsViaRest() {
		wp_set_current_user( $this->receiver_id );

		$request = new WP_REST_Request( 'GET', '/wp/v2/comments' );
		$request->set_param( 'post', $this->message_id );
		$response = $this->server->dispatch( $request );

		$this->assertCount( 0, $response->get_data(), 'The generic comments endpoint must not expose message replies.' );
	}

	/**
	 * Comment moderators (administrators) must still be able to read the replies.
	 */
	public function testModeratorCanListMessageCommentsViaRest() {
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$request = new WP_REST_Request( 'GET', '/wp/v2/comments' );
		$request->set_param( 'post', $this->message_id );
		$response = $this->server->dispatch( $request );

		$this->assertCount( 1, $response->get_data(), 'A comment moderator must still see message replies.' );
	}

	/**
	 * The unscoped comments listing (no `post` argument) must exclude message replies for
	 * anonymous visitors while still returning ordinary public comments.
	 */
	public function testAnonymousUnscopedCommentsListExcludesMessageReplies() {
		$post_id    = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$comment_id = $this->factory->comment->create(
			array(
				'comment_post_ID'  => $post_id,
				'comment_approved' => 1,
			)
		);
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/comments' );
		$response = $this->server->dispatch( $request );

		$ids = wp_list_pluck( $response->get_data(), 'id' );
		$this->assertContains( $comment_id, $ids, 'The ordinary post comment must appear in the unscoped listing.' );
		$this->assertNotContains( $this->reply_id, $ids, 'A message reply must not appear in the unscoped listing.' );
	}

	/**
	 * A result cached while a moderator queried must not leak to an anonymous visitor
	 * issuing the same query (the comment-queries cache is keyed on query vars, not the user).
	 */
	public function testCachedModeratorResultDoesNotLeakToAnonymous() {
		// Warm the comment-query cache as a moderator, who is allowed to see the reply.
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$warm = new WP_REST_Request( 'GET', '/wp/v2/comments' );
		$warm->set_param( 'post', $this->message_id );
		$this->server->dispatch( $warm );

		// The same query as an anonymous visitor must not read the cached moderator result.
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'GET', '/wp/v2/comments' );
		$request->set_param( 'post', $this->message_id );
		$response = $this->server->dispatch( $request );

		$this->assertCount( 0, $response->get_data(), 'A cached moderator result must not leak to an anonymous visitor.' );
	}

	/**
	 * Regression: comments on ordinary posts must remain publicly readable.
	 */
	public function testRegularPostCommentsRemainReadableViaRest() {
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

		$data = $response->get_data();
		$this->assertCount( 1, $data, 'Comments on ordinary posts must remain readable.' );
		$this->assertEquals( $comment_id, $data[0]['id'], 'The ordinary post comment must be returned.' );
	}
}

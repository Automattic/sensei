<?php
/**
 * Sensei REST API: Sensei_REST_API_Messages_Controller tests
 *
 * @package sensei-lms
 * @since 2.3.0
 */

/**
 * Class Sensei_REST_API_Messages_Controller tests.
 */
class Sensei_REST_API_Messages_Controller_Tests extends WP_Test_REST_TestCase {

	/**
	 * A server instance that we use in tests to dispatch requests.
	 *
	 * @var WP_REST_Server $server
	 */
	protected $server;

	/**
	 * Test specific setup.
	 */
	public function setUp() {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init' );

		// We need to re-instansiate the controller on each tests to register any hooks.
		new Sensei_REST_API_Messages_Controller( 'sensei_message' );
	}

	/**
	 * Test specific teardown.
	 */
	public function tearDown() {
		parent::tearDown();

		global $wp_rest_server;
		$wp_rest_server = null;
	}

	/**
	 * Class wide setup.
	 *
	 * @param WP_UnitTest_Factory $factory Helper factory to create WP objects.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		$teacher_role = new Sensei_Teacher();
		$teacher_role->create_role();
	}


	/**
	 * Tests if privileged users can access all messages.
	 *
	 * @since 2.3.0
	 * @covers Sensei_REST_API_Messages_Controller::exclude_others_comments
	 * @group wip
	 */
	public function testPrivilegedUsersCanAccessMessages() {

		$this->factory->user->create(
			array(
				'role'       => 'subscriber',
				'user_login' => 'login',
			)
		);

		$this->create_sensei_message( 'Message title', 'login' );

		// Test that a teacher can see all messages when sender=all.
		$teacher_id = $this->factory->user->create( array( 'role' => 'teacher' ) );
		wp_set_current_user( $teacher_id );

		$request = new WP_REST_Request( 'GET', '/wp/v2/sensei-messages' );
		$request->set_param( 'sender', 'all' );
		$response = $this->server->dispatch( $request );

		$this->assertCount( 1, $response->get_data(), 'A teacher does not see exactly one message.' );
		$this->assertEquals( 'Message title', $response->get_data()[0]['displayed_title'] );

		// Test that an administrator can see all messages when sender=all.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$request = new WP_REST_Request( 'GET', '/wp/v2/sensei-messages' );
		$request->set_param( 'sender', 'all' );
		$response = $this->server->dispatch( $request );

		$this->assertCount( 1, $response->get_data(), 'An administrator does not see exactly one message.' );
		$this->assertEquals( 'Message title', $response->get_data()[0]['displayed_title'] );

		// Test that an administrator does not see all messages when no sender argument is supplied.
		$request  = new WP_REST_Request( 'GET', '/wp/v2/sensei-messages' );
		$response = $this->server->dispatch( $request );

		$this->assertCount( 0, $response->get_data(), 'Messages are returned for an administrator which didn\'t create any. ' );
	}

	/**
	 * Tests if normal users can access only their own messages.
	 *
	 * @since 2.3.0
	 * @covers Sensei_REST_API_Messages_Controller::exclude_others_comments
	 * @group wip
	 */
	public function testUsersCanAccessOwnMessagesOnly() {

		$first_user = $this->factory->user->create(
			array(
				'role'       => 'subscriber',
				'user_login' => 'first',
			)
		);

		$this->create_sensei_message( 'First message', 'first' );

		$second_user = $this->factory->user->create(
			array(
				'role'       => 'subscriber',
				'user_login' => 'second',
			)
		);

		$this->create_sensei_message( 'Second message', 'second' );

		// Test that the first user can see his own message only.
		wp_set_current_user( $first_user );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/sensei-messages' );
		$response = $this->server->dispatch( $request );

		$this->assertCount( 1, $response->get_data(), 'The first user does not see exactly one message.' );
		$this->assertEquals( 'First message', $response->get_data()[0]['displayed_title'], 'A user can see other than his own messages.' );

		// Test that the second user can see his own message only.
		wp_set_current_user( $second_user );

		$request = new WP_REST_Request( 'GET', '/wp/v2/sensei-messages' );
		$request->set_param( 'sender', 'current' );
		$response = $this->server->dispatch( $request );

		$this->assertCount( 1, $response->get_data(), 'The second user does not see exactly one message.' );
		$this->assertEquals( 'Second message', $response->get_data()[0]['displayed_title'], 'A user can see other than his own messages.' );

		// Test that the sender=all arguments makes no difference.
		$request->set_param( 'sender', 'all' );
		$response = $this->server->dispatch( $request );

		$this->assertCount( 1, $response->get_data(), 'Sender argument affects output for an unprivileged user.' );
	}

	/**
	 * Tests sender and displayed_date elements.
	 *
	 * @since 2.3.0
	 * @covers Sensei_REST_API_Messages_Controller::exclude_others_comments
	 * @group wip
	 */
	public function testSenderAndDateAreCorrect() {

		$first_user = $this->factory->user->create(
			array(
				'role'       => 'subscriber',
				'user_login' => 'first',
			)
		);

		$message_id = $this->create_sensei_message( 'Without course', 'first' );

		wp_set_current_user( $first_user );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/sensei-messages/' . $message_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 'first', $response->get_data()['sender'] );
		$this->assertEquals( get_the_date( '', $message_id ), $response->get_data()['displayed_date'] );
	}

	/**
	 * Tests the displayed_title element when there is no course linked.
	 *
	 * @since 2.3.0
	 * @covers Sensei_REST_API_Messages_Controller::exclude_others_comments
	 * @group wip
	 */
	public function testDisplayedTitleNoCourse() {

		$first_user = $this->factory->user->create(
			array(
				'role'       => 'subscriber',
				'user_login' => 'first',
			)
		);

		$message_without_course = $this->create_sensei_message( 'Without course', 'first' );

		wp_set_current_user( $first_user );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/sensei-messages/' . $message_without_course );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 'Without course', $response->get_data()['displayed_title'] );
	}

	/**
	 * Tests the displayed_title element when there is a course linked to the message.
	 *
	 * @since 2.3.0
	 * @covers Sensei_REST_API_Messages_Controller::exclude_others_comments
	 * @group wip
	 */
	public function testDisplayedTitleWithCourse() {

		$first_user = $this->factory->user->create(
			array(
				'role'       => 'subscriber',
				'user_login' => 'first',
			)
		);

		$message_args = array(
			'post_type'   => 'course',
			'post_status' => 'publish',
			'post_title'  => 'Course title',
		);

		$course = $this->factory->post->create( $message_args );

		$message_with_course = $this->create_sensei_message( 'Message title', 'first', $course );

		wp_set_current_user( $first_user );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/sensei-messages/' . $message_with_course );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 'Re: Course title', $response->get_data()['displayed_title'] );
	}

	/**
	 * Helper method to create a sensei message.
	 *
	 * @param string  $title The messager title.
	 * @param string  $sender The username of the sender.
	 * @param integer $course The course id.
	 *
	 * @return int The message id
	 */
	private function create_sensei_message( $title, $sender, $course = null ) {
		$message_args = array(
			'post_type'   => 'sensei_message',
			'post_status' => 'publish',
			'post_title'  => $title,
			'meta_input'  => array(
				'_sender' => $sender,
			),
		);

		if ( null !== $course ) {
			$message_args['meta_input']['_posttype'] = 'course';
			$message_args['meta_input']['_post']     = $course;
		}

		$message_id = $this->factory->post->create( $message_args );

		return $message_id;
	}
}

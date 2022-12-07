<?php
/**
 * Sensei REST API tests.
 *
 * @package sensei-lms
 * @since 3.8.1
 */

/**
 * Tests for core WP REST API endpoint customizations.
 */
class Sensei_REST_API_Tests extends WP_Test_REST_TestCase {

	/**
	 * A server instance that we use in tests to dispatch requests.
	 *
	 * @var WP_REST_Server $server
	 */
	protected $server;

	/**
	 * Test specific setup.
	 */
	public function setUp(): void {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init' );

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Makes sure lesson content is removed from REST API responses when also hidden on frontend (guests.
	 */
	public function testLessonContentHiddenForGuests() {
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create();
		add_post_meta( $lesson_id, '_lesson_course', $course_id );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/lessons/' . $lesson_id );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( '', $response->get_data()['content']['rendered'] );
	}

	/**
	 * Makes sure lesson content is removed from REST API responses when also hidden on frontend (non-enrolled).
	 */
	public function testLessonContentHiddenForNonEnrolledUsers() {
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create();
		add_post_meta( $lesson_id, '_lesson_course', $course_id );

		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/lessons/' . $lesson_id );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( '', $response->get_data()['content']['rendered'] );
	}

	/**
	 * Makes sure lesson content is shown from REST API responses when the teacher is accessing it.
	 */
	public function testLessonContentShownToTeacher() {
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );

		$course_id = $this->factory->course->create( [ 'post_author' => $user_id ] );
		$lesson_id = $this->factory->lesson->create();
		add_post_meta( $lesson_id, '_lesson_course', $course_id );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/lessons/' . $lesson_id );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertNotEmpty( $response->get_data()['content']['rendered'] );
	}
}

<?php

class Sensei_REST_API_Lesson_Actions_Controller_Tests extends WP_Test_REST_TestCase {
	use Sensei_Test_Login_Helpers;
	use Sensei_REST_API_Test_Helpers;

	/**
	 * A server instance that we use in tests to dispatch requests.
	 *
	 * @var WP_REST_Server $server
	 */
	protected $server;

	/**
	 * Sensei post factory.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Lesson actions route.
	 */
	const REST_ROUTE = '/sensei-internal/v1/lessons/';

	public function setUp(): void {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init' );

		$this->factory = new Sensei_Factory();
	}

	public function testPrepare_LessonsGiven_ReturnsPreparedLessons() {
		/* Arrange. */
		$this->login_as_teacher();

		$course_id = $this->factory->course->create(
			[
				'post_title' => 'Course 1',
				'post_type'  => 'course',
			]
		);

		$lesson_id = $this->factory->lesson->create(
			[
				'post_title' => 'Lesson 1',
				'post_type'  => 'lesson',
				'meta_input' => [
					'_lesson_course' => $course_id,
				],
			]
		);

		$courseless_lesson_id = $this->factory->lesson->create(
			[
				'post_title' => 'Lesson 2',
				'post_type'  => 'lesson',
			]
		);

		$course2_id = $this->factory->course->create(
			[
				'post_title' => 'Course 2',
				'post_type'  => 'course',
			]
		);

		$body = array(
			'lesson_ids' => array( $lesson_id, $courseless_lesson_id ),
			'course_id'  => $course2_id,
		);

		$request = new WP_REST_Request( 'POST', self::REST_ROUTE . 'prepare' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );

		/* Act. */
		$response = $this->server->dispatch( $request );

		/* Assert. */
		$this->assertEquals( $response->get_status(), 200 );
		$this->assertCount( 2, $response->get_data() );

		$ids    = wp_list_pluck( $response->get_data(), 'ID' );
		$titles = wp_list_pluck( $response->get_data(), 'post_title' );

		$this->assertNotContains( $lesson_id, $ids );
		$this->assertContains( $courseless_lesson_id, $ids );

		$this->assertContains( 'Lesson 1', $titles );
		$this->assertContains( 'Lesson 2', $titles );
	}
}

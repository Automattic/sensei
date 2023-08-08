<?php

/**
 * Sensei REST API Course Progress Controller Unit Tests.
 *
 * @covers Sensei_REST_API_Course_Progress_Controller
 */
class Sensei_REST_API_Course_Progress_Controller_Test extends WP_Test_REST_TestCase {
	use Sensei_Test_Login_Helpers;
	use Sensei_Course_Enrolment_Test_Helpers;
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
	 * Test specific setup.
	 */
	public function setUp(): void {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init' );

		$this->factory = new Sensei_Factory();

		self::resetEnrolmentProviders();
		$this->prepareEnrolmentManager();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testDeleteCourseProgress_RequestGiven_ReturnsSuccessfulResponse() {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$lesson1_id = $this->factory->lesson->create( [ 'meta_input' => [ '_lesson_course' => $course_id ] ] );
		$lesson2_id = $this->factory->lesson->create( [ 'meta_input' => [ '_lesson_course' => $course_id ] ] );
		$student_id = $this->factory->user->create();

		Sensei_Utils::user_start_course( $student_id, $course_id );
		Sensei_Utils::update_lesson_status( $student_id, $lesson1_id, 'complete' );
		Sensei_Utils::user_start_lesson( $student_id, $lesson2_id );

		$this->login_as_admin();

		/* Act. */
		$request = new WP_REST_Request( 'DELETE', '/sensei-internal/v1/course-progress/batch' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'student_ids' => [ $student_id ],
					'course_ids'  => [ $course_id ],
				]
			)
		);
		ob_start();
		$response = $this->server->dispatch( $request );
		ob_end_clean(); // suppress output from \Sensei_Quiz::reset_user_lesson_data().

		/* Assert. */
		self::assertEquals( 200, $response->get_status() );
	}

	public function testDeleteCourseProgress_RequestGiven_ProgressReseted() {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$lesson1_id = $this->factory->lesson->create( [ 'meta_input' => [ '_lesson_course' => $course_id ] ] );
		$lesson2_id = $this->factory->lesson->create( [ 'meta_input' => [ '_lesson_course' => $course_id ] ] );
		$student_id = $this->factory->user->create();

		Sensei_Utils::user_start_course( $student_id, $course_id );
		Sensei_Utils::update_lesson_status( $student_id, $lesson1_id, 'complete' );
		Sensei_Utils::user_start_lesson( $student_id, $lesson2_id );

		$this->login_as_admin();

		/* Act. */
		$request = new WP_REST_Request( 'DELETE', '/sensei-internal/v1/course-progress/batch' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'student_ids' => [ $student_id ],
					'course_ids'  => [ $course_id ],
				]
			)
		);
		ob_start();
		$this->server->dispatch( $request );
		ob_end_clean(); // suppress output from \Sensei_Quiz::reset_user_lesson_data().

		/* Assert. */
		self::assertFalse( false, Sensei_Course::is_user_enrolled( $student_id, $course_id ) );
	}

	public function testDeleteCourseProgress_RequestGiven_ReturnsMatchingResponseData() {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$lesson1_id = $this->factory->lesson->create( [ 'meta_input' => [ '_lesson_course' => $course_id ] ] );
		$lesson2_id = $this->factory->lesson->create( [ 'meta_input' => [ '_lesson_course' => $course_id ] ] );
		$student_id = $this->factory->user->create();

		Sensei_Utils::user_start_course( $student_id, $course_id );
		Sensei_Utils::update_lesson_status( $student_id, $lesson1_id, 'complete' );
		Sensei_Utils::user_start_lesson( $student_id, $lesson2_id );

		$this->login_as_admin();

		/* Act. */
		$request = new WP_REST_Request( 'DELETE', '/sensei-internal/v1/course-progress/batch' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'student_ids' => [ $student_id ],
					'course_ids'  => [ $course_id ],
				]
			)
		);
		ob_start();
		$response = $this->server->dispatch( $request );
		ob_end_clean(); // suppress output from \Sensei_Quiz::reset_user_lesson_data().

		/* Assert. */
		$expected = [
			$student_id => [
				$course_id => true,
			],
		];
		self::assertSame( $expected, $response->get_data() );
	}

	public function testDeleteCourseProgress_StudentHasntEnrolledToCourse_ReturnsMatchingResponseData() {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$student_id = $this->factory->user->create();

		$this->login_as_admin();

		/* Act. */
		$request = new WP_REST_Request( 'DELETE', '/sensei-internal/v1/course-progress/batch' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'student_ids' => [ $student_id ],
					'course_ids'  => [ $course_id ],
				]
			)
		);
		ob_start();
		$response = $this->server->dispatch( $request );
		ob_end_clean(); // suppress output from \Sensei_Quiz::reset_user_lesson_data().

		/* Assert. */
		$expected = [
			$student_id => [
				$course_id => false,
			],
		];
		self::assertSame( $expected, $response->get_data() );
	}

	public function testDeleteCourseProgress_UserWithInsufficientPermissions_ReturnsForbiddenResponse() {
		/* Arrange. */
		$this->login_as_student();
		$course_id = $this->factory->course->create();

		/* Act. */
		$request = new WP_REST_Request( 'DELETE', '/sensei-internal/v1/course-progress/batch' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'student_ids' => [ 1 ],
					'course_ids'  => [ $course_id ],
				]
			)
		);
		$response = $this->server->dispatch( $request );

		/* Assert. */
		$this->assertEquals( 403, $response->get_status() );
	}

	public function testDeleteCourseProgress_UserNotFound_ReturnsSuccessfulResponse() {
		/* Arrange. */
		$course_id = $this->factory->course->create();

		$this->login_as_admin();

		/* Act. */
		$request = new WP_REST_Request( 'DELETE', '/sensei-internal/v1/course-progress/batch' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'student_ids' => [ 999 ],
					'course_ids'  => [ $course_id ],
				]
			)
		);
		$response = $this->server->dispatch( $request );

		/* Assert. */
		self::assertSame( 200, $response->get_status() );
	}

	public function testDeleteCourseProgress_CourseNotFound_ReturnsCourseNotFoundResponse() {
		/* Arrange. */
		$student_id = $this->factory->user->create();

		$this->login_as_admin();

		/* Act. */
		$request = new WP_REST_Request( 'DELETE', '/sensei-internal/v1/course-progress/batch' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'student_ids' => [ $student_id ],
					'course_ids'  => [ 999 ],
				]
			)
		);
		$response = $this->server->dispatch( $request );

		/* Assert. */
		$expected = [
			'status_code' => 404,
			'error_code'  => 'sensei_course_student_batch_action_missing_course',
		];
		self::assertSame( $expected, $this->getResponseAndStatusCode( $response ) );
	}

	public function testDeleteCourseProgress_PostInsteadOfCourseGiven_ReturnsNotFoundResponse() {
		/* Arrange. */
		$post_id    = $this->factory->post->create();
		$student_id = $this->factory->user->create();

		$this->login_as_admin();

		/* Act. */
		$request = new WP_REST_Request( 'DELETE', '/sensei-internal/v1/course-progress/batch' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'student_ids' => [ $student_id ],
					'course_ids'  => [ $post_id ],
				]
			)
		);
		$response = $this->server->dispatch( $request );

		/* Assert. */
		$expected = [
			'status_code' => 404,
			'error_code'  => 'sensei_course_student_batch_action_missing_course',
		];
		self::assertSame( $expected, $this->getResponseAndStatusCode( $response ) );
	}
}

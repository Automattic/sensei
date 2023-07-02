<?php

/**
 * Test for Sensei_REST_API_Course_Students_Controller.
 *
 * @covers Sensei_REST_API_Course_Students_Controller
 */
class Sensei_REST_API_Course_Students_Controller_Test extends WP_Test_REST_TestCase {
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

	public function testAddUsersToCourses_RequestGiven_ReturnsSuccessfulResponse() {
		/* Arrange. */
		$student_id = $this->factory->user->create();
		$course_id  = $this->factory->course->create();

		$this->login_as_admin();

		/* Act. */
		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-students/batch' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'student_ids' => [ $student_id ],
					'course_ids'  => [ $course_id ],
				]
			)
		);
		$response = $this->server->dispatch( $request );

		/* Assert. */
		$expected = [
			'status_code' => 200,
			'data'        => [
				$student_id => [
					$course_id => true,
				],
			],
		];
		$this->assertSame( $expected, $this->getResponseStatusAndData( $response ) );
	}

	public function testAddUsersToCourses_RequestGiven_EnrolsUserForCourse() {
		/* Arrange. */
		$student_id = $this->factory->user->create();
		$course_id  = $this->factory->course->create();

		$this->login_as_admin();

		/* Act. */
		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-students/batch' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'student_ids' => [ $student_id ],
					'course_ids'  => [ $course_id ],
				]
			)
		);
		$this->server->dispatch( $request );

		/* Assert. */
		$enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$this->assertTrue( $enrolment->is_enrolled( $student_id, false ) );
	}

	public function testAddUsersToCourses_UserNotFoundGiven_ReturnsSuccessfulResponse() {
		/* Arrange. */
		$course_id = $this->factory->course->create();

		$this->login_as_admin();

		/* Act. */
		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-students/batch' );
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
		$expected = [
			'status_code' => 200,
			'data'        => [
				999 => false,
			],
		];
		$this->assertSame( $expected, $this->getResponseStatusAndData( $response ) );
	}

	public function testAddUsersToCourses_UserWithInsufficientPermissions_ReturnsForbiddenResponse() {
		/* Arrange. */
		$student_ids = $this->factory->user->create_many( 2 );
		$course_ids  = $this->factory->course->create_many( 2 );

		$this->login_as_student();

		/* Act. */
		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-students/batch' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'student_ids' => $student_ids,
					'course_ids'  => $course_ids,
				]
			)
		);
		$response = $this->server->dispatch( $request );

		/* Assert. */
		$this->assertSame( 403, $response->get_status() );
	}

	public function testAddUsersToCourses_CourseNotFoundGiven_ReturnsCourseNotFoundResponse() {
		/* Arrange. */
		$student_id = $this->factory->user->create();

		$this->login_as_admin();

		/* Act. */
		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-students/batch' );
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
		$this->assertSame( $expected, $this->getResponseAndStatusCode( $response ) );
	}

	public function testRemoveUsersFromCoursesApi_AfterApiExecution_StudentsAreActuallyRemoved() {
		/* Arrange. */
		$user_ids              = $this->factory->user->create_many( 2 );
		$course_ids            = $this->factory->course->create_many( 2 );
		$enrolled_course_count = 0;
		foreach ( $user_ids as $user_id ) {
			foreach ( $course_ids as $course_id ) {
				$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
				$course_enrolment->enrol( $user_id );
				if ( $course_enrolment->is_enrolled( $user_id, false ) ) {
					$enrolled_course_count++;
				}
			}
		}
		$this->login_as_admin();

		/* Act. */
		$request = new WP_REST_Request( 'DELETE', '/sensei-internal/v1/course-students/batch' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'student_ids' => $user_ids,
					'course_ids'  => $course_ids,
				]
			)
		);
		$this->server->dispatch( $request );

		/* Assert. */
		foreach ( $user_ids as $user_id ) {
			foreach ( $course_ids as $course_id ) {
				$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
				if ( ! $course_enrolment->is_enrolled( $user_id, false ) ) {
					$enrolled_course_count--;
				}
			}
		}
		$this->assertEquals( 0, $enrolled_course_count );
	}

	public function testRemoveUsersFromCoursesApi_IfPostFoundInsteadOfCourse_ReturnsCourseNotFound() {
		/* Arrange. */
		$this->login_as_admin();
		$post_id = $this->factory->post->create();

		/* Act. */
		$request = new WP_REST_Request( 'DELETE', '/sensei-internal/v1/course-students/batch' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'student_ids' => [ 1 ],
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
		$this->assertSame( $expected, $this->getResponseAndStatusCode( $response ) );
	}

	public function testRemoveUsersFromCoursesApi_IfAnyStudentDoesNotExist_ReturnsFalseForThatStudentAndTrueForOthers() {
		/* Arrange. */
		$user_id          = $this->factory->user->create();
		$course_id        = $this->factory->course->create();
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$course_enrolment->enrol( $user_id );

		$this->login_as_admin();

		/* Act. */
		$request = new WP_REST_Request( 'DELETE', '/sensei-internal/v1/course-students/batch' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'student_ids' => [ $user_id, 999 ],
					'course_ids'  => [ $course_id ],
				]
			)
		);
		$response = $this->server->dispatch( $request );

		/* Assert. */
		$expected = [
			'status_code' => 200,
			'data'        => [
				$user_id => [
					$course_id => true,
				],
				'999'    => false,
			],
		];
		$this->assertEquals( $expected, $this->getResponseStatusAndData( $response ) );
	}
}

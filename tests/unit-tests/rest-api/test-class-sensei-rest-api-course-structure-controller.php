<?php
/**
 * Sensei REST API: Sensei_REST_API_Course_Structure_Controller_Tests tests
 *
 * @package sensei-lms
 * @since 3.6.0
 * @group course-structure
 * @group rest-api
 */

/**
 * Class Sensei_REST_API_Course_Structure_Controller tests.
 */
class Sensei_REST_API_Course_Structure_Controller_Tests extends WP_Test_REST_TestCase {
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
	 * Test specific setup.
	 */
	public function setUp() {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init' );

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Tests a simple `GET /sensei-internal/v1/course-structure/{course_id}` request response matches the schema.
	 */
	public function testGetSimple() {
		$this->login_as_teacher();

		$course_response = $this->factory->get_course_with_lessons(
			[
				'module_count'   => 2,
				'lesson_count'   => 5,
				'question_count' => 0,
			]
		);

		$course_id = $course_response['course_id'];
		$request   = new WP_REST_Request( 'GET', '/sensei-internal/v1/course-structure/' . $course_id );
		$response  = $this->server->dispatch( $request );

		$this->assertEquals( $response->get_status(), 200 );

		$endpoint = new Sensei_REST_API_Course_Structure_Controller( '' );
		$this->assertMeetsSchema( $endpoint->get_schema(), $response->get_data() );
		$this->assertEquals( 2, count( $response->get_data() ), '2 modules should be on the root level' );

		$this->assertEquals( 5, count( $response->get_data()[0]['lessons'] ) + count( $response->get_data()[1]['lessons'] ), '5 lessons should be set to the 2 modules' );
	}

	/**
	 * Tests a `GET /sensei-internal/v1/course-structure/{course_id}` returns 404 when course doesn't exist.
	 */
	public function testGetMissingCourse() {
		$this->login_as_admin();

		$request  = new WP_REST_Request( 'GET', '/sensei-internal/v1/course-structure/1234' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( $response->get_status(), 404 );
	}

	/**
	 * Tests `GET /sensei-internal/v1/course-structure/{course_id}` for a teacher's draft course.
	 */
	public function testGetTeachersCourse() {
		$this->login_as_teacher();

		$course_id = $this->factory->course->create(
			[
				'post_status' => 'draft',
			]
		);

		$request  = new WP_REST_Request( 'GET', '/sensei-internal/v1/course-structure/' . $course_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( $response->get_status(), 200 );
	}

	/**
	 * Tests `GET /sensei-internal/v1/course-structure/{course_id}` for another teacher's draft course isn't possible.
	 */
	public function testGetAnotherTeachersCourse() {
		$this->login_as_teacher();

		$course_id = $this->factory->course->create(
			[
				'post_status' => 'draft',
			]
		);

		$this->login_as_teacher_b();

		$request  = new WP_REST_Request( 'GET', '/sensei-internal/v1/course-structure/' . $course_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( $response->get_status(), 403 );
	}

	/**
	 * Tests `GET /sensei-internal/v1/course-structure/{course_id}` as a student for an unpublished course.
	 */
	public function testGetStudentDraftCourse() {
		$this->login_as_teacher();

		$course_id = $this->factory->course->create(
			[
				'post_status' => 'draft',
			]
		);

		$this->login_as_student();

		$request  = new WP_REST_Request( 'GET', '/sensei-internal/v1/course-structure/' . $course_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( $response->get_status(), 403 );
	}

	/**
	 * Tests `GET /sensei-internal/v1/course-structure/{course_id}` as a guest for an unpublished course.
	 */
	public function testGetGuestDraftCourse() {
		$this->login_as_teacher();

		$course_id = $this->factory->course->create(
			[
				'post_status' => 'draft',
			]
		);

		$this->logout();

		$request  = new WP_REST_Request( 'GET', '/sensei-internal/v1/course-structure/' . $course_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( $response->get_status(), 401 );
	}

	/**
	 * Tests `GET /sensei-internal/v1/course-structure/{course_id}` as a guest for published course.
	 */
	public function testGetGuestPublishedCourse() {
		$this->login_as_teacher();

		$course_id = $this->factory->course->create(
			[
				'post_status' => 'publish',
			]
		);

		$this->logout();

		$request  = new WP_REST_Request( 'GET', '/sensei-internal/v1/course-structure/' . $course_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( $response->get_status(), 200 );
	}
}

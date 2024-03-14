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
	public function setUp(): void {
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

	/**
	 * Context data for test testGetContextParam.
	 *
	 * @return array
	 */
	public function contextData() {
		return [
			'guest_view'   => [ 'view', false, false ],
			'guest_edit'   => [ 'edit', false, false ],
			'teacher_view' => [ 'view', true, false ],
			'teacher_edit' => [ 'edit', true, true ],
		];
	}

	/**
	 * Tests `GET /sensei-internal/v1/course-structure/{course_id}` with various contexts and users.
	 *
	 * @dataProvider contextData
	 */
	public function testGetContextParam( string $context, bool $has_edit_access, bool $show_unpublished ) {
		$this->login_as_teacher();

		$course_id = $this->factory->course->create(
			[
				'post_status' => 'publish',
			]
		);

		$lesson_ids = $this->factory->lesson->create_many( 2 );

		$course_structure = Sensei_Course_Structure::instance( $course_id );
		$course_structure->save(
			[
				[
					'type'    => 'module',
					'title'   => 'Module with Draft Lesson',
					'lessons' => [
						[
							'type'  => 'lesson',
							'title' => 'Draft Lesson in Module',
						],
					],
				],
				[
					'type'    => 'module',
					'title'   => 'Module with Published Lesson',
					'lessons' => [
						[
							'type'  => 'lesson',
							'id'    => $lesson_ids[0],
							'title' => get_the_title( $lesson_ids[0] ),
						],
					],
				],
				[
					'type'  => 'lesson',
					'title' => 'Draft Lesson outside Module',
				],
				[
					'type'  => 'lesson',
					'id'    => $lesson_ids[1],
					'title' => get_the_title( $lesson_ids[1] ),
				],
			]
		);

		if ( ! $has_edit_access ) {
			$this->logout();
		}

		$request = new WP_REST_Request( 'GET', '/sensei-internal/v1/course-structure/' . $course_id );
		$request->set_param( 'context', $context );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( $response->get_status(), 200 );

		$data = $response->get_data();

		if ( $show_unpublished ) {
			$this->assertEquals( 4, count( $data ), 'Both modules and root lessons should be included' );
			$this->assertEquals( 'module', $data[0]['type'], 'First item should be module with unpublished lesson' );
			$this->assertEquals( 'Module with Draft Lesson', $data[0]['title'], 'First item should be module with unpublished lesson' );
			$this->assertEquals( 'module', $data[1]['type'], 'Second item should be module with published lesson' );
			$this->assertEquals( 'Module with Published Lesson', $data[1]['title'], 'Second item should be module with published lesson' );
			$this->assertEquals( 'lesson', $data[2]['type'], 'Third item should be unpublished lesson at root' );
			$this->assertFalse( in_array( $data[2]['id'], $lesson_ids, true ), 'Unpublished lesson at root should be included' );
			$this->assertEquals( 'lesson', $data[3]['type'], 'Forth item should be published lesson at root' );
			$this->assertEquals( $lesson_ids[1], $data[3]['id'], 'Published lesson at root should be included' );
		} else {
			$this->assertEquals( 2, count( $data ), 'Module with published lesson and published root lesson should be included' );
			$this->assertEquals( 'module', $data[0]['type'], 'First item should be module with published lesson' );
			$this->assertEquals( 'Module with Published Lesson', $data[0]['title'], 'First item should be module with published lesson' );
			$this->assertEquals( 'lesson', $data[1]['type'], 'Second item should be published lesson at root' );
			$this->assertEquals( $lesson_ids[1], $data[1]['id'], 'Published lesson at root should be included' );
		}
	}

	/**
	 * Tests a simple `POST /sensei-internal/v1/course-structure/{course_id}` request response matches the schema.
	 */
	public function testPostSimple() {
		$this->login_as_admin();

		$course_response = $this->factory->get_course_with_lessons(
			[
				'module_count'   => 1,
				'lesson_count'   => 3,
				'question_count' => 0,
			]
		);

		$course_id        = $course_response['course_id'];
		$course_structure = Sensei_Course_Structure::instance( $course_id );
		$structure        = $course_structure->get( 'edit' );

		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-structure/' . $course_id );
		$request->set_body( wp_json_encode( [ 'structure' => $structure ] ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( $response->get_status(), 200 );

		$endpoint = new Sensei_REST_API_Course_Structure_Controller( '' );
		$this->assertMeetsSchema( $endpoint->get_schema(), $response->get_data() );
		$this->assertEquals( $structure, $response->get_data(), 'Unchanged structure should be returned' );
	}

	/**
	 * Tests to make sure a teacher cannot modify another teacher's course structure with `POST /sensei-internal/v1/course-structure/{course_id}`.
	 */
	public function testPostDifferentTeacher() {
		$this->login_as_teacher();
		$course_id = $this->factory->course->create();
		$structure = [
			[
				'type'  => 'lesson',
				'title' => 'Test',
			],
		];

		$this->login_as_teacher_b();

		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-structure/' . $course_id );
		$request->set_body( wp_json_encode( [ 'structure' => $structure ] ) );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( $response->get_status(), 403 );
	}

	/**
	 * Tests to make sure an admin can modify another teacher's course structure with `POST /sensei-internal/v1/course-structure/{course_id}`.
	 */
	public function testPostDifferentTeacherAdmin() {
		$this->login_as_teacher();
		$course_id = $this->factory->course->create();
		$structure = [
			[
				'type'  => 'lesson',
				'title' => 'Test',
			],
		];

		$this->login_as_admin();

		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-structure/' . $course_id );
		$request->set_body( wp_json_encode( [ 'structure' => $structure ] ) );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( $response->get_status(), 200 );
	}

	/**
	 * Tests to make sure an guest cannot edit course structure with `POST /sensei-internal/v1/course-structure/{course_id}`.
	 */
	public function testPostGuest() {
		$this->login_as_teacher();
		$course_id = $this->factory->course->create();
		$structure = [
			[
				'type'  => 'lesson',
				'title' => 'Test',
			],
		];

		$this->logout();

		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-structure/' . $course_id );
		$request->set_body( wp_json_encode( [ 'structure' => $structure ] ) );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( $response->get_status(), 401 );
	}

	public function testCourseStructure_whenCustomSlugIsAddedToModule_isProperlySavedAndServed() {
		$this->login_as_teacher();

		$course_response = $this->factory->get_course_with_lessons(
			[
				'module_count'   => 1,
				'lesson_count'   => 1,
				'question_count' => 0,
			]
		);

		$course_id        = $course_response['course_id'];
		$course_structure = Sensei_Course_Structure::instance( $course_id );
		$structure        = $course_structure->get( 'edit' );

		$structure[0]['slug'] = 'custom-slug';

		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-structure/' . $course_id );
		$request->set_body( wp_json_encode( [ 'structure' => $structure ] ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$endpoint = new Sensei_REST_API_Course_Structure_Controller( '' );
		$this->assertMeetsSchema( $endpoint->get_schema(), $response->get_data() );
		$this->assertEquals( $structure[0]['slug'], $response->get_data()[0]['slug'], 'Returned structure should have custom slug' );
		$this->assertEquals( wp_get_current_user()->ID, $response->get_data()[0]['teacherId'], 'Returned structure should have module teacher\'s id' );
	}

	public function testCourseStructure_whenOtherCustomSlugModuleBelongsToSameTeacher_GetsMergedAndSaved() {
		/* Arrange */
		$this->login_as_teacher();

		$course_a = $this->factory->get_course_with_lessons(
			[
				'module_count'   => 1,
				'lesson_count'   => 1,
				'question_count' => 0,
			]
		);
		$course_b = $this->factory->get_course_with_lessons(
			[
				'module_count'   => 1,
				'lesson_count'   => 1,
				'question_count' => 0,
			]
		);

		$course_structure_a = Sensei_Course_Structure::instance( $course_a['course_id'] );
		$course_structure_b = Sensei_Course_Structure::instance( $course_b['course_id'] );

		$structure_a = $course_structure_a->get( 'edit' );
		$structure_b = $course_structure_b->get( 'edit' );

		$structure_a[0]['slug'] = 'custom-slug';
		$structure_b[0]['slug'] = 'custom-slug';

		// Save a course structure containing one module with a custom slug.
		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-structure/' . $course_a['course_id'] );
		$request->set_body( wp_json_encode( [ 'structure' => $structure_a ] ) );
		$this->server->dispatch( $request );

		/* Act */

		// Save another course structure containing a different module with the same custom slug.
		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-structure/' . $course_b['course_id'] );
		$request->set_body( wp_json_encode( [ 'structure' => $structure_b ] ) );
		$response_b = $this->server->dispatch( $request );

		// Get the structure for the first course again to make sure that the data was merged not overwritten.
		$request    = new WP_REST_Request( 'GET', '/sensei-internal/v1/course-structure/' . $course_a['course_id'] );
		$response_a = $this->server->dispatch( $request );

		/* Assert */

		// The first and second structure should have different modules.
		$this->assertFalse( $structure_a[0]['id'] === $structure_b[0]['id'] );

		// The returned structure from the api should contain the custom slug.
		$this->assertEquals( $structure_b[0]['slug'], $response_b->get_data()[0]['slug'], 'Returned structure should have custom slug' );

		// The second course module reused the module from the first course.
		$this->assertEquals( $response_a->get_data()[0]['id'], $response_b->get_data()[0]['id'], 'Module did not reuse existing module with same custom slug' );

		// Though the module will be same, lessons it contains will be different for different course denoting a lossless module merge.
		$this->assertFalse( $response_a->get_data()[0]['lessons'][0]['id'] === $response_b->get_data()[0]['lessons'][0]['id'], 'Module lesson got overwritten' );
	}

	public function testCourseStructure_whenAnotherUserExistingSlugUsedInCourse_IsRestrictedFromDoingIt() {
		/* Arrange */

		// Save a course and update a module with custom slug for a teacher.
		$this->login_as_teacher();

		$course_a     = $this->factory->get_course_with_lessons(
			[
				'module_count'   => 1,
				'lesson_count'   => 1,
				'question_count' => 0,
			]
		);
		$course_title = get_post( $course_a['course_id'] )->post_title;

		$course_structure_a = Sensei_Course_Structure::instance( $course_a['course_id'] );

		$structure_a = $course_structure_a->get( 'edit' );

		$structure_a[0]['slug'] = 'custom-slug';

		// Save a course structure containing one module with a custom slug.
		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-structure/' . $course_a['course_id'] );
		$request->set_body( wp_json_encode( [ 'structure' => $structure_a ] ) );
		$this->server->dispatch( $request );

		// Save another course and try to update a module with the same custom slug for a different teacher.
		$this->login_as_teacher_b();

		$course_b = $this->factory->get_course_with_lessons(
			[
				'module_count'   => 1,
				'lesson_count'   => 1,
				'question_count' => 0,
			]
		);

		$course_structure_b     = Sensei_Course_Structure::instance( $course_b['course_id'] );
		$structure_b            = $course_structure_b->get( 'edit' );
		$structure_b[0]['slug'] = 'custom-slug';

		/* Act */
		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-structure/' . $course_b['course_id'] );
		$request->set_body( wp_json_encode( [ 'structure' => $structure_b ] ) );
		$response_teacher_b = $this->server->dispatch( $request );

		$this->login_as_admin();
		$response_admin = $this->server->dispatch( $request );

		/* Assert */

		// Teacher gets restricted.
		$this->assertEquals( $response_teacher_b->get_status(), 400 );
		$this->assertEquals( "Slug custom-slug exists and is being used in $course_title course", $response_teacher_b->get_data()['message'] );

		// Admin gets restricted.
		$this->assertEquals( $response_admin->get_status(), 400 );
		$this->assertEquals( "Slug custom-slug exists and is being used in $course_title course", $response_admin->get_data()['message'] );
	}

	public function testCourseStructure_whenAnotherUserTriesUsingExistingSlugNotUsedInCourse_IsAllowedOnlyForAdmin() {
		/* Arrange */

		// Save a course and update a module with custom slug for a teacher.
		$this->login_as_teacher();

		$course = $this->factory->get_course_with_lessons(
			[
				'module_count'   => 1,
				'lesson_count'   => 1,
				'question_count' => 0,
			]
		);

		// Insert a module that is not used in any course.
		wp_insert_term(
			'Cats will take over',
			'module',
			array(
				'description' => 'Test.',
				'slug'        => 'custom-slug',
			)
		);
		$course_structure_a = Sensei_Course_Structure::instance( $course['course_id'] );

		$structure = $course_structure_a->get( 'edit' );

		$structure[0]['slug'] = 'custom-slug';

		/* Act */

		// Try assigning the custom slug owned by another teacher to a module, as a teacher.
		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-structure/' . $course['course_id'] );
		$request->set_body( wp_json_encode( [ 'structure' => $structure ] ) );
		$response_teacher = $this->server->dispatch( $request );

		// Try assigning the custom slug owned by another teacher to a module, as an admin.
		$this->login_as_admin();
		$response_admin = $this->server->dispatch( $request );

		/* Assert */

		// Another teacher will not be able to use that custom slug owned by another user or teacher.
		$this->assertEquals( $response_teacher->get_status(), 400 );
		$this->assertEquals( 'A module with the slug custom-slug is already owned by another teacher', $response_teacher->get_data()['message'] );

		// Admin should be able to use that custom slug as the module is not being by any course.
		$endpoint = new Sensei_REST_API_Course_Structure_Controller( '' );
		$this->assertEquals( $response_admin->get_status(), 200 );
		$this->assertMeetsSchema( $endpoint->get_schema(), $response_admin->get_data() );
	}

	public function testCourseStructure_whenCustomSlugModuleInUseByAnotherCourseBySameTeacher_DoesNotLetChangeTheTeacherOfOneCourse() {
		/* Arrange */
		$this->login_as_teacher();

		$course_params = [
			'module_count'   => 1,
			'lesson_count'   => 1,
			'question_count' => 0,
		];

		$course_a = $this->factory->get_course_with_lessons( $course_params );
		$course_b = $this->factory->get_course_with_lessons( $course_params );
		$course_c = $this->factory->get_course_with_lessons( $course_params );

		$course_structure_a = Sensei_Course_Structure::instance( $course_a['course_id'] );
		$course_structure_b = Sensei_Course_Structure::instance( $course_b['course_id'] );
		$course_structure_c = Sensei_Course_Structure::instance( $course_c['course_id'] );

		$structure_a = $course_structure_a->get( 'edit' );
		$structure_b = $course_structure_b->get( 'edit' );
		$structure_c = $course_structure_c->get( 'edit' );

		$structure_a[0]['slug'] = 'custom-slug';
		$structure_b[0]['slug'] = 'custom-slug';
		$structure_c[0]['slug'] = 'custom-slug-uncommon';

		// Save a course structure containing one module with a custom slug.
		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-structure/' . $course_a['course_id'] );
		$request->set_body( wp_json_encode( [ 'structure' => $structure_a ] ) );
		$this->server->dispatch( $request );

		// Save another course structure containing a different module with the same custom slug.
		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-structure/' . $course_b['course_id'] );
		$request->set_body( wp_json_encode( [ 'structure' => $structure_b ] ) );
		$this->server->dispatch( $request );

		// Save a third course structure containing a different module with a different custom slug.
		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-structure/' . $course_c['course_id'] );
		$request->set_body( wp_json_encode( [ 'structure' => $structure_c ] ) );
		$this->server->dispatch( $request );

		$this->login_as_teacher_b();
		$teacher_id = wp_get_current_user()->ID;
		$this->login_as_admin();

		/* Act */
		$_POST['sensei_meta_nonce']            = wp_create_nonce( 'sensei_save_data' );
		$_POST['sensei-course-teacher-author'] = $teacher_id;

		// Try updating the teacher for the course with common custom module slug.
		$_POST['course_module_custom_slugs'] = wp_json_encode( [ 'custom-slug' ] );
		$_POST['post_ID']                    = $course_b['course_id'];
		Sensei()->teacher->save_teacher_meta_box( $course_b['course_id'] );

		// Try updating the teacher for the course without an uncommon custom module slug.
		$_POST['course_module_custom_slugs'] = wp_json_encode( [ 'custom-slug-uncommon' ] );
		$_POST['post_ID']                    = $course_c['course_id'];
		Sensei()->teacher->save_teacher_meta_box( $course_c['course_id'] );

		/* Assert */

		// The teacher of the course with a common module using custom slug should not get updated.
		$this->assertEquals( get_post( $course_b['course_id'] )->post_author, get_post( $course_a['course_id'] )->post_author );

		// But the teacher for another course without that common module will be updated.
		$this->assertEquals( get_post( $course_c['course_id'] )->post_author, $teacher_id );
	}

	public function testSaveCourseStructure_WhenCalledWithInitialContent_SavesInitialContentInLessonWhenCreating() {
		/* Arrange */
		$this->login_as_teacher();
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		$structure = array(
			array(
				'type'    => 'module',       // Tests that it works when lesson is in a module.
				'title'   => 'Module with Some Lessons',
				'lessons' => [
					[
						'type'           => 'lesson',
						'title'          => 'Lesson in Module',
						'draft'          => true,
						'preview'        => false,
						'initialContent' => 'Test Content M1',
					],
				],
			),
			array(
				'type'           => 'lesson', // Tests that the initial content is only saved.
				'title'          => 'Lesson 1',
				'draft'          => true,
				'preview'        => false,
				'initialContent' => 'Test Content 1',
			),
			array(
				'type'           => 'lesson',
				'title'          => 'Lesson 2',
				'draft'          => true,
				'preview'        => false,
				'initialContent' => '', // Tests that it doesn't cause any problem if initialContent is empty.
			),
			array(
				'type'    => 'lesson',
				'title'   => 'Lesson 3',
				'draft'   => true,
				'preview' => false, // Tests that it doesn't cause any problem if initialContent is not set.
			),
			array(
				'type'           => 'lesson',
				'title'          => 'Lesson 4',
				'draft'          => true,
				'preview'        => false,
				'id'             => $lesson_id,
				'initialContent' => 'Test Content 4', // Tests that it doesn't save initialContent for existing lesson.
			),
		);

		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-structure/' . $course_id );
		$request->set_body( wp_json_encode( [ 'structure' => $structure ] ) );

		/* Act */
		$response = $this->server->dispatch( $request );

		/* Assert */
		$response_data = $response->get_data();

		$this->assertEquals( $response->get_status(), 200 );
		$this->assertEquals( 'Test Content M1', $response_data[0]['lessons'][0]['initialContent'] );
		$this->assertEquals( 'Test Content 1', $response_data[1]['initialContent'] );
		$this->assertEmpty( $response_data[2]['initialContent'] );
		$this->assertEmpty( $response_data[3]['initialContent'] );
		$this->assertEmpty( $response_data[4]['initialContent'] );
		$this->assertEquals(
			'<!-- wp:paragraph --><p>Test Content 1</p>
<!-- /wp:paragraph -->',
			get_post( $response_data[1]['id'] )->post_content
		);
		$this->assertEquals(
			'<!-- wp:paragraph --><p>Test Content M1</p>
<!-- /wp:paragraph -->',
			get_post( $response_data[0]['lessons'][0]['id'] )->post_content
		);
	}
}

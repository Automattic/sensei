<?php

class Sensei_Class_Course_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Test_Helpers;
	use Sensei_Course_Enrolment_Manual_Test_Helpers;
	use Sensei_Test_Login_Helpers;
	use Sensei_Test_Redirect_Helpers;

	/**
	 * Helper class to create testing data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Keep initial state of Sensei()->notices.
	 *
	 * @var Sensei_Notices|null
	 */
	private $initial_notices;

	/**
	 * Setup function.
	 *
	 * This function sets up the lessons, quizes and their questions. This function runs before
	 * every single test in this class
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
		Sensei_Test_Events::reset();

		$this->initial_notices = Sensei()->notices;
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();

		Sensei()->notices = $this->initial_notices;
	}

	/**
	 * Testing the quiz class to make sure it is loaded
	 *
	 * @since 1.8.0
	 */
	public function testClassInstance() {

		// test if the class exists
		$this->assertTrue( class_exists( 'WooThemes_Sensei_Course' ), 'Sensei course class does not exist' );

		// test if the global sensei quiz class is loaded
		$this->assertTrue( isset( Sensei()->course ), 'Sensei Course class is not loaded' );

	}

	/**
	 * This tests Sensei_Courses::get_all_courses
	 *
	 * @since 1.8.0
	 */
	public function testGetAllCourses() {
		// check if the function is there
		$this->assertTrue( method_exists( 'WooThemes_Sensei_Course', 'get_all_courses' ), 'The course class get_all_courses function does not exist.' );

		// setup the assertion
		$retrieved_courses = get_posts(
			array(
				'post_type'      => 'course',
				'posts_per_page' => 10000,
			)
		);

		// make sure the same course were retrieved as what we just created
		$this->assertEquals(
			count( $retrieved_courses ),
			count( WooThemes_Sensei_Course::get_all_courses() ),
			'The number of course returned is not equal to what is actually available'
		);

	}

	/**
	 *
	 * This tests Sensei_Courses::get_completed_lesson_ids
	 *
	 * @since 1.8.0
	 */
	public function testGetCompletedLessonIds() {
		$lesson_progress_repository = Sensei()->lesson_progress_repository;

		// does the function exist?
		$this->assertTrue( method_exists( 'WooThemes_Sensei_Course', 'get_completed_lesson_ids' ), 'The course class get_completed_lesson_ids function does not exist.' );

		// setup the test
		$test_user_id   = wp_create_user( 'getCompletedLessonIds', 'getCompletedLessonIds', 'getCompletedLessonIds@tes.co' );
		$test_lessons   = $this->factory->get_lessons();
		$test_course_id = $this->factory->get_random_course_id();
		remove_all_actions( 'sensei_user_course_start' );
		WooThemes_Sensei_Utils::user_start_course( $test_user_id, $test_course_id );

		// add lessons to the course
		foreach ( $test_lessons as $lesson_id ) {
			add_post_meta( $lesson_id, '_lesson_course', intval( $test_course_id ) );
		}

		// complete 3 lessons
		$i = 0;
		for ( $i = 0; $i < 3; $i++ ) {
			$progress = $lesson_progress_repository->get( $test_lessons[ $i ], $test_user_id );
			if ( ! $progress ) {
				$progress = $lesson_progress_repository->create( $test_lessons[ $i ], $test_user_id );
			}
			$progress->complete();
			$lesson_progress_repository->save( $progress );
		}

		$this->assertEquals( 3, count( Sensei()->course->get_completed_lesson_ids( $test_course_id, $test_user_id ) ), 'Course completed lesson count not accurate' );

		// complete all lessons
		foreach ( $test_lessons as $lesson_id ) {
			$progress = $lesson_progress_repository->get( $lesson_id, $test_user_id );
			if ( ! $progress ) {
				$progress = $lesson_progress_repository->create( $lesson_id, $test_user_id );
			}
			$progress->complete();
			$lesson_progress_repository->save( $progress );
		}

		// does it return all lessons
		$this->assertEquals( count( $test_lessons ), count( Sensei()->course->get_completed_lesson_ids( $test_course_id, $test_user_id ) ), 'Course completed lesson count not accurate' );

	}

	/**
	 * This tests Sensei_Courses::get_completion_percentage
	 *
	 * @since 1.8.0
	 */
	public function testGetCompletionPercentage() {
		$lesson_progress_repository = Sensei()->lesson_progress_repository;

		// does the function exist?
		$this->assertTrue( method_exists( 'WooThemes_Sensei_Course', 'get_completion_percentage' ), 'The course class get_completion_percentage function does not exist.' );

		// setup the test
		$test_user_id   = wp_create_user( 'testGetCompletionPercentage', 'testGetCompletionPercentage', 'testGetCompletionPercentage@tes.co' );
		$test_lessons   = $this->factory->get_lessons();
		$test_course_id = $this->factory->get_random_course_id();
		remove_all_actions( 'sensei_user_course_start' );
		WooThemes_Sensei_Utils::user_start_course( $test_user_id, $test_course_id );

		// add lessons to the course
		foreach ( $test_lessons as $lesson_id ) {
			add_post_meta( $lesson_id, '_lesson_course', intval( $test_course_id ) );
		}

		// complete 3 lessons and check if the correct percentage returns
		$i = 0;
		for ( $i = 0; $i < 3; $i++ ) {
			$progress = $lesson_progress_repository->get( $test_lessons[ $i ], $test_user_id );
			if ( ! $progress ) {
				$progress = $lesson_progress_repository->create( $test_lessons[ $i ], $test_user_id );
			}
			$progress->complete();
			$lesson_progress_repository->save( $progress );
		}
		$expected_percentage = round( 3 / count( $test_lessons ) * 100, 2 );
		$this->assertEquals( $expected_percentage, Sensei()->course->get_completion_percentage( $test_course_id, $test_user_id ), 'Course completed percentage is not accurate' );

		// complete all lessons
		foreach ( $test_lessons as $lesson_id ) {
			$progress = $lesson_progress_repository->get( $lesson_id, $test_user_id );
			if ( ! $progress ) {
				$progress = $lesson_progress_repository->create( $lesson_id, $test_user_id );
			}
			$progress->complete();
			$lesson_progress_repository->save( $progress );
		}
		// all lessons should no be completed
		$this->assertEquals( 100, Sensei()->course->get_completion_percentage( $test_course_id, $test_user_id ), 'Course completed percentage is not accurate' );

	}

	/**
	 * Test initial publish logging default property values.
	 *
	 * @covers Sensei_Course::log_initial_publish_event
	 */
	public function testLogInitialPublishDefaultPropertyValues() {
		$course_id = $this->factory->course->create(
			[
				'post_status' => 'draft',
			]
		);

		// Set product meta to "-", which simulates actual behaviour.
		add_post_meta( $course_id, '_course_woocommerce_product', '-', true );

		// Publish course.
		wp_update_post(
			[
				'ID'          => $course_id,
				'post_status' => 'publish',
			]
		);

		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$events = Sensei_Test_Events::get_logged_events( 'sensei_course_publish' );
		$this->assertCount( 1, $events );

		// Ensure default values are correct.
		$event = $events[0];
		$this->assertEquals( 0, $event['url_args']['module_count'] );
		$this->assertEquals( 0, $event['url_args']['lesson_count'] );
		$this->assertEquals( 0, $event['url_args']['product_count'] );
	}

	/**
	 * Test initial publish logging module count.
	 *
	 * @covers Sensei_Course::log_initial_publish_event
	 */
	public function testLogInitialPublishModuleCount() {
		$course_id = $this->factory->course->create(
			[
				'post_status' => 'draft',
			]
		);

		// Add some modules.
		wp_set_object_terms( $course_id, [ 'module-a', 'module-b' ], 'module' );

		// Publish course.
		wp_update_post(
			[
				'ID'          => $course_id,
				'post_status' => 'publish',
			]
		);

		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$events = Sensei_Test_Events::get_logged_events( 'sensei_course_publish' );
		$this->assertCount( 1, $events );

		// Ensure module count is correct.
		$event = $events[0];
		$this->assertEquals( 2, $event['url_args']['module_count'] );
	}

	/**
	 * Test initial publish logging lesson count.
	 *
	 * @covers Sensei_Course::log_initial_publish_event
	 */
	public function testLogInitialPublishLessonCount() {
		$course_id = $this->factory->course->create(
			[
				'post_status' => 'draft',
			]
		);

		// Add some lessons to the course.
		$lesson_ids = $this->factory->lesson->create_many( 2 );
		foreach ( $lesson_ids as $lesson_id ) {
			add_post_meta( $lesson_id, '_lesson_course', $course_id );
		}

		// Publish course.
		wp_update_post(
			[
				'ID'          => $course_id,
				'post_status' => 'publish',
			]
		);

		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$events = Sensei_Test_Events::get_logged_events( 'sensei_course_publish' );
		$this->assertCount( 1, $events );

		// Ensure lesson count is correct.
		$event = $events[0];
		$this->assertEquals( 2, $event['url_args']['lesson_count'] );
	}

	/**
	 * Test initial publish logging product count.
	 *
	 * @covers Sensei_Course::log_initial_publish_event
	 */
	public function testLogInitialPublishProductCount() {
		$course_id = $this->factory->course->create(
			[
				'post_status' => 'draft',
			]
		);

		// Add product ID.
		add_post_meta( $course_id, '_course_woocommerce_product', 5 );

		// Publish without product ID.
		wp_update_post(
			[
				'ID'          => $course_id,
				'post_status' => 'publish',
			]
		);

		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$events = Sensei_Test_Events::get_logged_events( 'sensei_course_publish' );
		$this->assertCount( 1, $events );

		// Ensure product ID is correct.
		$event = $events[0];
		$this->assertEquals( 1, $event['url_args']['product_count'] );
	}

	/**
	 * Test initial publish logging without product ID.
	 *
	 * @covers Sensei_Course::log_initial_publish_event
	 */
	public function testLogNoEventProduct() {
		$course_id = $this->factory->course->create(
			[
				'post_status' => 'draft',
			]
		);

		// Publish without product ID.
		wp_update_post(
			[
				'ID'          => $course_id,
				'post_status' => 'publish',
			]
		);

		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$events = Sensei_Test_Events::get_logged_events( 'sensei_course_publish' );
		$this->assertCount( 1, $events );

		// Ensure product ID is correct.
		$event = $events[0];
		$this->assertEquals( 0, $event['url_args']['product_count'] );
	}

	/**
	 * Test initial publish logging product count with multiple product IDs.
	 *
	 * @covers Sensei_Course::log_initial_publish_event
	 */
	public function testLogEventProductCountMultiProduct() {
		$course_id = $this->factory->course->create(
			[
				'post_status' => 'draft',
			]
		);
		add_post_meta( $course_id, '_course_woocommerce_product', 5 );
		add_post_meta( $course_id, '_course_woocommerce_product', 6 );

		// Publish.
		wp_update_post(
			[
				'ID'          => $course_id,
				'post_status' => 'publish',
			]
		);

		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$events = Sensei_Test_Events::get_logged_events( 'sensei_course_publish' );
		$this->assertCount( 1, $events, 'One event for sensei_course_publish should be recorded' );

		// Ensure product count is correct.
		$event = $events[0];
		$this->assertEquals( 2, $event['url_args']['product_count'], 'Event should have 2 products attached to the course' );
	}

	/**
	 * Checks to make sure standard users can view course content when the access permissions setting is disabled.
	 */
	public function testCanAccessCourseContentDisableAccessPermissionCan() {
		$course_instance = Sensei()->course;
		$user_id         = $this->factory->user->create();
		$course_id       = $this->factory->course->create();

		Sensei()->settings->set( 'access_permission', false );
		$result = $course_instance->can_access_course_content( $course_id, $user_id );
		Sensei()->settings->set( 'access_permission', true );

		$this->assertTrue( $result, 'Standard users should have access to course content when access permissions are disabled' );
	}

	/**
	 * Checks to make sure admins always have access to course content.
	 */
	public function testCanAccessCourseContentAdminCan() {
		$course_instance = Sensei()->course;
		$user_id         = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$course_id       = $this->factory->course->create();

		$user = get_user_by( 'id', $user_id );
		$user->add_cap( 'manage_sensei' );

		$this->assertTrue( $course_instance->can_access_course_content( $course_id, $user_id ), 'Admins should have access to course content' );
	}

	/**
	 * Checks to make sure standard users who aren't enrolled can't view course content.
	 */
	public function testCanAccessCourseContentStandardUserCanNot() {
		$course_instance = Sensei()->course;
		$user_id         = $this->factory->user->create();
		$course_id       = $this->factory->course->create();

		$this->assertFalse( $course_instance->can_access_course_content( $course_id, $user_id ), 'Standard users who are not enrolled should not have access to course content' );
	}

	/**
	 * Checks to make sure standard users who are enrolled can view course content.
	 */
	public function testCanAccessCourseContentEnrolledStandardCan() {
		$this->prepareEnrolmentManager();

		$course_instance = Sensei()->course;
		$user_id         = $this->factory->user->create();
		$course_id       = $this->factory->course->create();

		$this->manuallyEnrolStudentInCourse( $user_id, $course_id );

		$this->assertTrue( $course_instance->can_access_course_content( $course_id, $user_id ), 'Standard users who are enrolled should have access to course content' );
	}

	/**
	 * Test that the course completed page URL is returned.
	 *
	 * @covers Sensei_Course::get_view_results_link
	 * @covers Sensei_Course::get_course_completed_page_url
	 */
	public function testGetViewResultsLinkCourseCompletedPage() {
		$course_id = $this->factory->course->create();
		$page_id   = $this->factory->post->create(
			[
				'post_type'  => 'page',
				'post_title' => 'Course Completed',
			]
		);
		Sensei()->settings->set( 'course_completed_page', $page_id );

		$expected = "http://example.org/?page_id={$page_id}&course_id={$course_id}";
		$actual   = Sensei_Course::get_view_results_link( $course_id );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test that the course results page URL is returned.
	 *
	 * @covers Sensei_Course::get_view_results_link
	 * @covers Sensei_Course::get_course_completed_page_url
	 */
	public function testGetViewResultsLinkCourseResultsPage() {
		$course_id = $this->factory->course->create(
			[
				'post_name' => 'a-course',
			]
		);

		$expected = 'http://example.org/?course_results=a-course';
		$actual   = Sensei_Course::get_view_results_link( $course_id );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test that the parameters are not added when hook function called out of the course archive page.
	 *
	 * @param bool $is_post_type_archive Returns true if the WP_Query is from an archive page.
	 * @param bool $is_main_query Returns true if the WP_Query is the main query.
	 * @param int $is_main_query_call_count Indicates the expectation for how many times $is_main_query will be called.
	 * @param bool $set_current_screen_to_admin_panel Setting this to true will make the is_admin check return true, and otherwise it will return false.
	 *
	 * @dataProvider data_testCourseArchiveOrderSetOrderByDoesNotAddParametersOutsideOfArchivePage
	 */
	public function testCourseArchiveOrderSetOrderByDoesNotAddParametersOutsideOfArchivePage( $is_post_type_archive, $is_main_query, $is_main_query_call_count, $set_current_screen_to_admin_panel ) {
		global $current_screen;
		$initial_current_screen = $current_screen;

		$wp_query = $this->createMock( 'WP_Query' );
		// Returns true for the course archive page, but also returns true when the query is from 'wp-admin -> Sensei LMS -> Courses' or 'shortcode'
		$wp_query->expects( $this->once() )
			->method( 'is_post_type_archive' )
			->with( 'course' )
			->willReturn( $is_post_type_archive );
		// We dont expect any parameter to be set the query.
		$wp_query->expects( $this->never() )
			->method( 'set' );
		// Returns 'true' for actual course archive page query, returns 'false' for queries generated by other sources, like shortcodes
		$wp_query->expects( $this->exactly( $is_main_query_call_count ) )
			->method( 'is_main_query' )
			->willReturn( $is_main_query );

		if ( $set_current_screen_to_admin_panel ) {
			// This will make the is_admin method return true.
			set_current_screen( 'edit-post' );
		}
		Sensei_Course::course_archive_set_order_by( $wp_query );

		// Reset $current_screen. This is needed for WordPress <= 5.8.
		// @see https://core.trac.wordpress.org/ticket/53431
		$current_screen = $initial_current_screen;
	}

	/**
	 * Data source for ::testCourseArchiveOrderSetOrderByDoesNotAddParametersOutsideOfArchivePage
	 *
	 * @return array
	 */
	public function data_testCourseArchiveOrderSetOrderByDoesNotAddParametersOutsideOfArchivePage() {
		return array(
			'Not an archive page query'              => array( false, true, 0, false ),
			'In case of shortcode'                   => array( true, false, 1, false ),
			'In case of admin dashboard for courses' => array( true, true, 1, true ),
		);
	}

	/**
	 * Test that the correct order parameter values are set for the WP_Query used in the course archive page.
	 *
	 * @param array  $request_parameters  $_REQUEST contents for the test. After the test the original values are restored.
	 * @param string $course_order_option Value for the `sensei_course_order` option. Empty means it is not specified and any other value is understood as enabled.
	 * @param string $expected_order_by   Expected ORDER BY value.
	 * @param string $expected_order      Expected ORDER value (ASC or DESC).
	 *
	 * @dataProvider data_testCourseArchiveOrderSetOrderBy
	 */
	public function testCourseArchiveOrderSetOrderBy( $request_parameters, $course_order_option, $expected_order_by, $expected_order ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$original_request_object = $_REQUEST;
		$_REQUEST                = $request_parameters;
		$wp_query                = $this->createMock( 'WP_Query' );

		// Mock request is for the course archive page.
		$wp_query->expects( $this->once() )
			->method( 'is_post_type_archive' )
			->with( 'course' )
			->willReturn( true );

		$wp_query->expects( $this->once() )
			->method( 'is_main_query' )
			->willReturn( true );

		// Set whether custom course order has been set or not.
		update_option( 'sensei_course_order', $course_order_option );
		// Create expectations for the correct 'orderby' and 'order' attributes.
		$wp_query->expects( $this->exactly( 2 ) )
			->method( 'set' )
			->withConsecutive(
				array( $this->equalTo( 'orderby' ), $this->equalTo( $expected_order_by ) ),
				array( $this->equalTo( 'order' ), $this->equalTo( $expected_order ) )
			);

		Sensei_Course::course_archive_set_order_by( $wp_query );

		// Restore original requests.
		$_REQUEST = $original_request_object;
	}

	/**
	 * Data source for ::testCourseArchiveOrderSetOrderBy
	 *
	 * @return array
	 */
	public function data_testCourseArchiveOrderSetOrderBy() {
		return array(
			'Default when no order set'                  => array( array(), '', 'date', 'DESC' ),
			'No order set and newness option selected'   => array( array( 'course-orderby' => 'newness' ), '', 'date', 'DESC' ),
			'No order set and title option selected'     => array( array( 'course-orderby' => 'title' ), '', 'title', 'ASC' ),
			'No order set and default option selected'   => array( array( 'course-orderby' => 'default' ), '', 'date', 'DESC' ),
			'Default when courses order is set'          => array( array(), 'anything', 'menu_order', 'ASC' ),
			'Order set and newness option selected'      => array( array( 'course-orderby' => 'newness' ), 'anything', 'date', 'DESC' ),
			'Order set and alphabetical option selected' => array( array( 'course-orderby' => 'title' ), 'anything', 'title', 'ASC' ),
			'Order set and default option selected'      => array( array( 'course-orderby' => 'default' ), 'anything', 'menu_order', 'ASC' ),
		);
	}

	public function testCourseClass_WhenInitialized_AddsHookToCompletionRedirect() {
		/* Assert. */
		$priority = has_action( 'template_redirect', [ Sensei()->course, 'maybe_redirect_to_login_from_course_completion' ] );
		$this->assertSame( 10, $priority );
	}

	public function testCompletionRedirect_WhenCalled_DoesNotRedirectIfLoggedIn() {
		/* Arrange. */
		$this->login_as_student();
		$this->prevent_wp_redirect();

		$page_id = $this->factory->post->create(
			[
				'post_type'  => 'page',
				'post_title' => 'Course Completed',
			]
		);
		Sensei()->settings->set( 'course_completed_page', $page_id );

		$this->go_to( get_permalink( Sensei()->settings->get( 'course_completed_page' ) ) );

		/* Act. */
		try {
			Sensei()->course->maybe_redirect_to_login_from_course_completion();
		} catch ( \Sensei_WP_Redirect_Exception $e ) {
			$redirect_status = $e->getCode();
		}

		/* Assert. */
		$this->assertFalse( isset( $redirect_status ) );
	}

	public function testCompletionRedirect_WhenCalled_DoesNotRedirectIfCompletionPageIsNotThere() {
		/* Arrange. */
		$this->prevent_wp_redirect();

		Sensei()->settings->set( 'course_completed_page', null );

		$normal_page_id = $this->factory->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => 'Random Page',
			)
		);

		$this->go_to( get_permalink( $normal_page_id ) );

		/* Act. */
		try {
			Sensei()->course->maybe_redirect_to_login_from_course_completion();
		} catch ( \Sensei_WP_Redirect_Exception $e ) {
			$redirect_status = $e->getCode();
		}

		/* Assert. */
		$this->assertFalse( isset( $redirect_status ) );
	}

	public function testCompletionRedirect_WhenCalledForAnotherPage_DoesNotRedirectEvenIfCompletionPageExists() {
		/* Arrange. */
		$this->prevent_wp_redirect();

		$page_id = $this->factory->post->create(
			[
				'post_type'  => 'page',
				'post_title' => 'Course Completed',
			]
		);
		Sensei()->settings->set( 'course_completed_page', $page_id );

		$normal_page_id = $this->factory->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => 'Random Page',
			)
		);

		$this->go_to( get_permalink( $normal_page_id ) );

		/* Act. */
		try {
			Sensei()->course->maybe_redirect_to_login_from_course_completion();
		} catch ( \Sensei_WP_Redirect_Exception $e ) {
			$redirect_status = $e->getCode();
		}

		/* Assert. */
		$this->assertFalse( isset( $redirect_status ) );
	}

	public function testCompletionRedirect_WhenCompletionPageExistsLoggedOut_PerformsRedirection() {
		/* Arrange. */
		$this->prevent_wp_redirect();
		$page_id = $this->factory->post->create(
			[
				'post_type'  => 'page',
				'post_title' => 'Course Completed',
			]
		);
		Sensei()->settings->set( 'course_completed_page', $page_id );

		$this->go_to( get_permalink( Sensei()->settings->get( 'course_completed_page' ) ) );

		/* Act. */
		try {
			Sensei()->course->maybe_redirect_to_login_from_course_completion();
		} catch ( \Sensei_WP_Redirect_Exception $e ) {
			$redirect_status = $e->getCode();
		}

		/* Assert. */
		$this->assertTrue( isset( $redirect_status ) );
	}

	public function testCompletionRedirect_WhenMyCoursesIsThere_PerformsRedirectionToMyCoursesPage() {
		/* Arrange. */
		$this->prevent_wp_redirect();
		$completion_page_id = $this->factory->post->create(
			[
				'post_type'  => 'page',
				'post_title' => 'Course Completed',
			]
		);

		$my_courses_page_id = $this->factory->post->create(
			[
				'post_type'  => 'page',
				'post_title' => 'My Courses',
			]
		);
		Sensei()->settings->set( 'course_completed_page', $completion_page_id );
		Sensei()->settings->set( 'my_course_page', $my_courses_page_id );

		$this->go_to( get_permalink( Sensei()->settings->get( 'course_completed_page' ) ) );

		/* Act. */
		try {
			Sensei()->course->maybe_redirect_to_login_from_course_completion();
		} catch ( \Sensei_WP_Redirect_Exception $e ) {
			$redirect_status   = $e->getCode();
			$redirect_location = $e->getMessage();
		}

		/* Assert. */
		$this->assertTrue( isset( $redirect_status ) );
		$this->assertStringContainsString( get_permalink( $my_courses_page_id ), $redirect_location );
	}

	public function testCompletionRedirect_WhenMyCoursesPageNotThere_PerformsRedirectionToWpLoginPage() {
		/* Arrange. */
		$this->prevent_wp_redirect();
		$page_id = $this->factory->post->create(
			[
				'post_type'  => 'page',
				'post_title' => 'Course Completed',
			]
		);
		Sensei()->settings->set( 'course_completed_page', $page_id );
		Sensei()->settings->set( 'my_course_page', null );

		$this->go_to( get_permalink( Sensei()->settings->get( 'course_completed_page' ) ) );

		/* Act. */
		try {
			Sensei()->course->maybe_redirect_to_login_from_course_completion();
		} catch ( \Sensei_WP_Redirect_Exception $e ) {
			$redirect_status   = $e->getCode();
			$redirect_location = $e->getMessage();
		}

		/* Assert. */
		$this->assertTrue( isset( $redirect_status ) );
		$this->assertStringContainsString( home_url( '/wp-login.php' ), $redirect_location );
	}

	public function testSelfEnrollmentNotAllowedMessage_WhenCourseDoesntAllowSelfEnrollment_AddsNotice(): void {
		/* Arrange */
		global $post;

		$course_id        = $this->factory->course->create();
		$post             = get_post( $course_id );
		$notices          = $this->createMock( Sensei_Notices::class );
		Sensei()->notices = $notices;

		update_post_meta( $course_id, '_sensei_self_enrollment_not_allowed', true );

		/* Expect & Act */
		$notices->expects( self::once() )
			->method( 'add_notice' )
			->with( $this->stringContains( 'Please contact the course administrator to sign up for this course.' ) );
		Sensei_Course::self_enrollment_not_allowed_message();
	}

	public function testSelfEnrollmentNotAllowedMessage_WhenCourseAllowsSelfEnrollment_DoesNotAddNotice(): void {
		/* Arrange */
		global $post;

		$course_id        = $this->factory->course->create();
		$post             = get_post( $course_id );
		$notices          = $this->createMock( Sensei_Notices::class );
		Sensei()->notices = $notices;

		/* Expect & Act */
		$notices->expects( self::never() )
			->method( 'add_notice' );
		Sensei_Course::self_enrollment_not_allowed_message();
	}

	public function testSelfEnrollmentNotAllowedMessage_WhenCourseDoesntAllowSelfEnrollmentAndUserIsEnrolled_DoesNotAddNotice(): void {
		/* Arrange */
		$this->login_as_student();
		global $post;

		$course_id        = $this->factory->course->create();
		$post             = get_post( $course_id );
		$notices          = $this->createMock( Sensei_Notices::class );
		Sensei()->notices = $notices;

		update_post_meta( $course_id, '_sensei_self_enrollment_not_allowed', true );

		$this->manuallyEnrolStudentInCourse( get_current_user_id(), $course_id );

		/* Expect & Act */
		$notices->expects( self::never() )
			->method( 'add_notice' );
		Sensei_Course::self_enrollment_not_allowed_message();
	}
}

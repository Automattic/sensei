<?php

namespace SenseiTest\RestApi;

use Sensei_Factory;
use Sensei_REST_API_Test_Helpers;
use Sensei_Test_Login_Helpers;
use WP_REST_Request;

/**
 * Class Sensei_REST_API_Lessons_Controller_Test.
 *
* @covers \Sensei_REST_API_Lessons_Controller
*/
class Sensei_REST_API_Lessons_Controller_Test extends \WP_UnitTestCase {

	use Sensei_Test_Login_Helpers;
	use Sensei_REST_API_Test_Helpers;

	/**
	 * A server instance that we use in tests to dispatch requests.
	 *
	 * @var \WP_REST_Server $server
	 */
	protected $server;

	/**
	 * Sensei post factory.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public function setUp(): void {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new \WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init' );

		// The lesson meta is unregistered after each test, and the controller registers it.
		new \Sensei_REST_API_Lessons_Controller( 'lesson' );

		$this->factory = new Sensei_Factory();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testGet_MetaQueryProvided_AppliesMetaQuery() {
		/* Arrange. */
		$this->login_as_teacher();

		$has_meta_clause          = false;
		$rest_lesson_query_filter = function ( $args ) use ( &$has_meta_clause ) {
			$meta_query = $args['meta_query'] ?? array();
			if ( ! empty( $meta_query ) ) {
				foreach ( $meta_query as $meta_query_item ) {
					if ( isset( $meta_query_item['key'] )
						&& '_lesson_complexity' === $meta_query_item['key']
						&& isset( $meta_query_item['value'] )
						&& 'easy' === $meta_query_item['value']
					) {
						$has_meta_clause = true;
					}
				}
			}
			return $args;
		};
		add_filter( 'rest_lesson_query', $rest_lesson_query_filter, 11, 1 );

		new \Sensei_REST_API_Lessons_Controller( 'lesson' );

		/* Act. */
		$request = new WP_REST_Request( 'GET', '/wp/v2/lessons' );
		$request->set_query_params(
			array(
				'metaKey'   => '_lesson_complexity',
				'metaValue' => 'easy',
			)
		);
		$this->server->dispatch( $request );
		remove_filter( 'rest_lesson_query', $rest_lesson_query_filter, 11 );

		/* Assert. */
		$this->assertTrue( $has_meta_clause );
	}

	/**
	 * Filtering the lessons route by course returns the lessons of that course only.
	 *
	 * @covers Sensei_REST_API_Lessons_Controller::add_meta_query_args
	 */
	public function testAddMetaQueryArgs_LessonCourseMetaProvided_ReturnsTheLessonsOfThatCourse() {
		/* Arrange. */
		$this->login_as_teacher();
		$course_lessons = $this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 2,
				'question_count' => 0,
			)
		);
		$this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 1,
				'question_count' => 0,
			)
		);

		new \Sensei_REST_API_Lessons_Controller( 'lesson' );

		/* Act. */
		$request = new WP_REST_Request( 'GET', '/wp/v2/lessons' );
		$request->set_query_params(
			array(
				'metaKey'   => '_lesson_course',
				'metaValue' => $course_lessons['course_id'],
			)
		);
		$response = $this->server->dispatch( $request );
		$actual   = wp_list_pluck( $response->get_data(), 'id' );
		sort( $actual );

		/* Assert. */
		$expected = $course_lessons['lesson_ids'];
		sort( $expected );
		$this->assertSame( $expected, $actual );
	}

	public function testGet_MetaQueryWithEmptyCourseProvided_AppliesMetaQuery() {
		/* Arrange. */
		$this->login_as_teacher();

		$has_compare_not_exists   = false;
		$rest_lesson_query_filter = function ( $args ) use ( &$has_compare_not_exists ) {
			$meta_query = $args['meta_query'] ?? array();
			if ( ! empty( $meta_query ) ) {
				foreach ( $meta_query as $meta_query_item ) {
					foreach ( $meta_query_item as $sub_item ) {
						if ( ! is_array( $sub_item ) ) {
							continue;
						}

						if ( isset( $sub_item['key'] )
							&& '_lesson_course' === $sub_item['key']
							&& isset( $sub_item['compare'] )
							&& 'NOT EXISTS' === $sub_item['compare']
						) {
							$has_compare_not_exists = true;
						}
					}
				}
			}
			return $args;
		};
		add_filter( 'rest_lesson_query', $rest_lesson_query_filter, 11, 1 );

		new \Sensei_REST_API_Lessons_Controller( 'lesson' );

		/* Act. */
		$request = new WP_REST_Request( 'GET', '/wp/v2/lessons' );
		$request->set_query_params(
			array(
				'metaKey'   => '_lesson_course',
				'metaValue' => 0,
			)
		);
		$this->server->dispatch( $request );
		remove_filter( 'rest_lesson_query', $rest_lesson_query_filter, 11 );

		/* Assert. */
		$this->assertTrue( $has_compare_not_exists );
	}

	public function testUpdateItemPermissionsCheck_UneditableCourseGiven_ReturnsForbidden() {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$this->login_as_teacher();
		$lesson_id = $this->factory->lesson->create( array( 'post_author' => get_current_user_id() ) );

		$request = new WP_REST_Request( 'POST', '/wp/v2/lessons/' . $lesson_id );
		$request->set_body_params( array( 'meta' => array( '_lesson_course' => $course_id ) ) );

		/* Act. */
		$response = $this->server->dispatch( $request );

		/* Assert. */
		$this->assertSame( 403, $response->get_status() );
	}

	public function testUpdateItemPermissionsCheck_EditableNonCoursePostGiven_ReturnsForbidden() {
		/* Arrange. */
		$this->login_as_teacher();
		$other_lesson_id = $this->factory->lesson->create( array( 'post_author' => get_current_user_id() ) );
		$lesson_id       = $this->factory->lesson->create( array( 'post_author' => get_current_user_id() ) );

		$request = new WP_REST_Request( 'POST', '/wp/v2/lessons/' . $lesson_id );
		$request->set_body_params( array( 'meta' => array( '_lesson_course' => $other_lesson_id ) ) );

		/* Act. */
		$response = $this->server->dispatch( $request );

		/* Assert. */
		$this->assertSame( 403, $response->get_status() );
	}

	public function testUpdateItemPermissionsCheck_EditableCourseGiven_UpdatesTheLessonCourse() {
		/* Arrange. */
		$this->login_as_teacher();
		$course_id = $this->factory->course->create( array( 'post_author' => get_current_user_id() ) );
		$lesson_id = $this->factory->lesson->create( array( 'post_author' => get_current_user_id() ) );

		$request = new WP_REST_Request( 'POST', '/wp/v2/lessons/' . $lesson_id );
		$request->set_body_params( array( 'meta' => array( '_lesson_course' => $course_id ) ) );

		/* Act. */
		$this->server->dispatch( $request );

		/* Assert. */
		$this->assertSame( $course_id, (int) get_post_meta( $lesson_id, '_lesson_course', true ) );
	}

	public function testUpdateItemPermissionsCheck_CurrentUneditableCourseGiven_ReturnsSuccess() {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$this->login_as_teacher();
		$lesson_id = $this->factory->lesson->create(
			array(
				'post_author' => get_current_user_id(),
				'meta_input'  => array(
					'_lesson_course' => $course_id,
				),
			)
		);

		$request = new WP_REST_Request( 'POST', '/wp/v2/lessons/' . $lesson_id );
		$request->set_body_params( array( 'meta' => array( '_lesson_course' => $course_id ) ) );

		/* Act. */
		$response = $this->server->dispatch( $request );

		/* Assert. */
		$this->assertSame( 200, $response->get_status() );
	}

	public function testCreateItemPermissionsCheck_UneditableCourseGiven_ReturnsForbidden() {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$this->login_as_teacher();

		$request = new WP_REST_Request( 'POST', '/wp/v2/lessons' );
		$request->set_body_params(
			array(
				'title' => 'A lesson',
				'meta'  => array( '_lesson_course' => $course_id ),
			)
		);

		/* Act. */
		$response = $this->server->dispatch( $request );

		/* Assert. */
		$this->assertSame( 403, $response->get_status() );
	}
}

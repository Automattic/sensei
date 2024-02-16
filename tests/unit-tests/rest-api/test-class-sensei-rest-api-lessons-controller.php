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
	 * @var WP_REST_Server $server
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

		$this->factory = new Sensei_Factory();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testGet_MetaQueryProvided_AppliesMetaQuery() {
		/* Arrange. */
		$this->login_as_teacher();

		$has_course_id            = false;
		$rest_lesson_query_filter = function ( $args ) use ( &$has_course_id ) {
			$meta_query = $args['meta_query'] ?? array();
			if ( ! empty( $meta_query ) ) {
				foreach ( $meta_query as $meta_query_item ) {
					if ( isset( $meta_query_item['key'] )
						&& '_lesson_course' === $meta_query_item['key']
						&& isset( $meta_query_item['value'] )
						&& 1 === (int) $meta_query_item['value']
					) {
						$has_course_id = true;
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
				'metaValue' => 1,
			)
		);
		$this->server->dispatch( $request );
		remove_filter( 'rest_lesson_query', $rest_lesson_query_filter, 11 );

		/* Assert. */
		$this->assertTrue( $has_course_id );
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
}

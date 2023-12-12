<?php
/**
 * File containing the class Sensei_REST_API_Lesson_Actions_Controller.
 *
 * @package sensei
 */

use Sensei\Admin\Content_Duplicators\Lesson_Quiz_Duplicator;
use Sensei\Admin\Content_Duplicators\Post_Duplicator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Lesson Actions REST API endpoints.
 *
 * @since $$next-version$$
 */
class Sensei_REST_API_Lesson_Actions_Controller extends \WP_REST_Controller {

	/**
	 * Routes namespace.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * Routes prefix.
	 *
	 * @var string
	 */
	protected $rest_base = 'lessons';

	/**
	 * Sensei_REST_API_Lesson_Actions_Controller constructor.
	 *
	 * @param string $namespace Routes namespace.
	 */
	public function __construct( $namespace ) {
		$this->namespace = $namespace;
	}

	/**
	 * Register the routes for the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/prepare',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'prepare_lessons' ],
				'permission_callback' => [ $this, 'prepare_lessons_permissions_check' ],
				'args'                => [
					'lesson_ids' => [
						'description' => __( 'The IDs of lessons to duplicate.', 'sensei-lms' ),
						'type'        => 'array',
						'items'       => [
							'type' => 'integer',
						],
					],
					'course_id'  => [
						'description' => __( 'The ID of the course to attach the duplicated lesson to.', 'sensei-lms' ),
						'type'        => 'integer',
					],
				],
			]
		);
	}

	/**
	 * Prepare lessons to attach to a course. This duplicates lessons if they are already related to a course.
	 *
	 * @since $$next-version$$
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function prepare_lessons( $request ) {
		$lesson_ids = $request->get_param( 'lesson_ids' );
		$course_id  = $request->get_param( 'course_id' );

		$response = new WP_REST_Response();
		if ( empty( $lesson_ids ) ) {
			$response->set_data( array() );

			return $response;
		}

		$lessons = get_posts(
			array(
				'post__in'       => (array) $lesson_ids,
				'post_type'      => 'lesson',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
			)
		);
		$course  = get_post( $course_id );

		if ( empty( $lessons ) || ! $course ) {
			return new WP_Error( 'sensei_rest_invalid_id', __( 'Invalid ID(s) provided.', 'sensei-lms' ), [ 'status' => 404 ] );
		}

		$post_duplicator        = new Post_Duplicator();
		$lesson_quiz_duplicator = new Lesson_Quiz_Duplicator();

		$duplicated_lessons = array();
		foreach ( $lessons as $lesson ) {
			$attached_course_id = get_post_meta( $lesson->ID, '_lesson_course', true );
			if ( $attached_course_id && $attached_course_id === $course_id ) {
				// lesson is already attached to the course.
				continue;
			}

			if ( $attached_course_id ) {
				// duplicate the lesson if it is already attached to another course.
				$add_lesson = $post_duplicator->duplicate( $lesson, null, true );

				$lesson_quiz_duplicator->duplicate( $lesson->ID, $add_lesson->ID );
			} else {
				$add_lesson = $lesson;
			}

			$duplicated_lessons[] = $add_lesson;
		}

		$response->set_data( $duplicated_lessons );

		return $response;
	}

	/**
	 * Check if the current user can prepare lessons to attach to a course.
	 *
	 * @since $$next-version$$
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool Whether the user can duplicate the lesson.
	 */
	public function prepare_lessons_permissions_check( $request ): bool {
		$lesson_ids = $request->get_param( 'lesson_ids' );
		$course_id  = $request->get_param( 'course_id' );

		if ( ! $lesson_ids || ! $course_id ) {
			return false;
		}

		$lessons = get_posts(
			array(
				'post__in'       => (array) $lesson_ids,
				'post_type'      => 'lesson',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
			)
		);
		$course  = get_post( $course_id );

		if ( ! $lessons || ! $course ) {
			return false;
		}

		foreach ( $lessons as $lesson ) {
			if ( ! current_user_can( 'edit_post', $lesson->ID ) ) {
				return false;
			}
		}

		if ( ! current_user_can( 'edit_post', $course_id ) ) {
			return false;
		}

		return true;
	}
}

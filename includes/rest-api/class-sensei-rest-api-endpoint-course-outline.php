<?php
/**
 * The course outline endpoints.
 *
 * @package SenseiRestAPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class Sensei_REST_API_Endpoint_Course_Outline
 */
class Sensei_REST_API_Endpoint_Course_Outline  extends WP_REST_Controller {

	const HTTP_CREATED   = 201;
	const HTTP_SUCCESS   = 200;
	const BAD_REQUEST    = 400;
	const HTTP_NOT_FOUND = 404;

	/**
	 * The api namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'sensei-lms-admin/v1';

	/**
	 * Registers the rest routes for course outline.
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/course-outline/(?P<course_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_course' ),
					'permission_callback' => array( $this, 'get_course_outline_permissions_check' ),
				),

			)
		);
		register_rest_route(
			$this->namespace,
			'/course-outline/(?P<course_id>\d+)/lessons',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_lessons' ),
					'permission_callback' => array( $this, 'get_course_outline_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_lesson' ),
					'permission_callback' => array( $this, 'get_course_outline_permissions_check' ),
					'args'                => $this->get_create_lesson_args(),
				),
			)
		);
	}

	/**
	 * A user must be able to create courses to create outlines.
	 *
	 * @param mixed $request The request.
	 *
	 * @return bool
	 */
	public function get_course_outline_permissions_check( $request ) {
		return current_user_can( 'create_courses' );
	}

	/**
	 * Get the args required for creating a new course lesson.
	 *
	 * @return array
	 */
	private function get_create_lesson_args() {
		$args              = [];
		$args['course_id'] = [
			'description' => __( 'the course id we want to add the lesson to', 'sensei-lms' ),
			'type'        => 'integer',
			'required'    => true,
		];

		$args['title'] = [
			'description' => __( 'the title of the lesson', 'sensei-lms' ),
			'type'        => 'string',
			'required'    => true,
		];

		return $args;
	}

	/**
	 * Gets the course outline we will be working with.
	 *
	 * @param mixed $request The request.
	 *
	 * @return mixed|WP_REST_Response
	 */
	public function get_course( $request ) {
		$course_id = isset( $request['course_id'] ) ? absint( $request['course_id'] ) : null;

		if ( null === $course_id ) {
			return $this->not_found( __( 'Course not found', 'sensei-lms' ) );
		}

		$dto = $this->get_course_outline_dto( $course_id );
		if ( empty( $dto ) ) {
			return $this->not_found( __( 'Course not found', 'sensei-lms' ) );
		}

		return $this->succeed( $dto );
	}

	/**
	 * Gets the course lesson order.
	 *
	 * @param int $course_id The course id.
	 *
	 * @return array
	 */
	private function get_lesson_order( $course_id ) {
		$course_lesson_order_meta = get_post_meta( $course_id, '_lesson_order', true );
		$course_lesson_order      = [ 0 ];
		if ( ! empty( $course_lesson_order_meta ) ) {
			$course_lesson_order = array_map( 'absint', explode( ',', $course_lesson_order_meta ) );
		}
		return $course_lesson_order;
	}

	/**
	 * Provides a course outline Data Transfer Object (dto).
	 *
	 * @param int $course_id The course id.
	 *
	 * @return array|null
	 */
	private function get_course_outline_dto( $course_id ) {
		$course = get_post( absint( $course_id ) );
		if ( empty( $course ) || 'course' !== $course->post_type ) {
			return null;
		}
		$lessons             = Sensei()->course->course_lessons( $course_id, array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit' ) );
		$course_lesson_order = $this->get_lesson_order( $course_id );
		$lesson_dtos         = [];

		foreach ( $lessons as $lesson ) {
			$order = array_search( $lesson->ID, $course_lesson_order, true );
			if ( false === $order ) {
				$order                  = count( $course_lesson_order );
				$course_lesson_order [] = $lesson->ID;
			}
			$edit_link      = htmlspecialchars_decode( get_edit_post_link( $lesson->ID ) );
			$lesson_dtos [] = [
				'lesson_id' => $lesson->ID,
				'title'     => $lesson->post_title,
				'status'    => $lesson->post_status,
				'order'     => $order,
				'edit_link' => $edit_link,
			];
		}

		update_post_meta( $course_id, '_lesson_order', implode( ',', $course_lesson_order ) );

		$data = $course->to_array();
		$dto  = array(
			'id'      => $course_id,
			'title'   => $data['post_title'],
			'status'  => $data['post_status'],
			'lessons' => $lesson_dtos,
		);
		return $dto;
	}

	/**
	 * Get the course lessons.
	 *
	 * @param mixed $request The request.
	 *
	 * @return mixed|WP_REST_Response
	 */
	public function get_lessons( $request ) {
		$course_id = isset( $request['course_id'] ) ? absint( $request['course_id'] ) : null;

		if ( null === $course_id ) {
			return $this->not_found( __( 'Course not found', 'sensei-lms' ) );
		}

		$dto = $this->get_course_outline_dto( $course_id );
		if ( empty( $dto ) ) {
			return $this->not_found( __( 'Course not found', 'sensei-lms' ) );
		}

		return $this->succeed( $dto['lessons'] );
	}

	/**
	 * Create a lesson for a given course.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response
	 */
	public function create_lesson( $request ) {
		$course_id = isset( $request['course_id'] ) ? absint( $request['course_id'] ) : null;
		$title     = isset( $request['title'] ) ? esc_html( $request['title'] ) : null;
		if ( null === $title ) {
			return $this->fail_with( [ 'error' => __( 'Title is required', 'sensei-lms' ) ] );
		}

		if ( null === $course_id ) {
			return $this->not_found( __( 'Course not found', 'sensei-lms' ) );
		}

		$dto = $this->get_course_outline_dto( $course_id );
		if ( empty( $dto ) ) {
			return $this->not_found( __( 'Course not found', 'sensei-lms' ) );
		}

		$fields = [
			'post_title' => $title,
			'post_type'  => 'lesson',
			'meta_input' => [
				'_lesson_course' => $course_id,
			],
		];

		$lesson_id = wp_insert_post( $fields, true );
		if ( is_wp_error( $lesson_id ) ) {
				return $this->fail_with( [ 'error' => $lesson_id->get_error_code() ] );
		}

		$course_lesson_order    = $this->get_lesson_order( $course_id );
		$course_lesson_order [] = $lesson_id;
		update_post_meta( $course_id, '_lesson_order', implode( ',', $course_lesson_order ) );

		return $this->succeed( [ 'lesson_id' => $lesson_id ] );
	}

	/**
	 * Http 200.
	 *
	 * @param array $data The data.
	 *
	 * @return WP_REST_Response
	 */
	protected function succeed( $data ) {
		return new WP_REST_Response( $data, self::HTTP_SUCCESS );
	}

	/**
	 * Http Created.
	 *
	 * @param array $data The data.
	 *
	 * @return WP_REST_Response
	 */
	protected function created( $data ) {
		return new WP_REST_Response( $data, self::HTTP_CREATED );
	}

	/**
	 * Http fail.
	 *
	 * @param array $data The data.
	 *
	 * @return WP_REST_Response
	 */
	protected function fail_with( $data ) {
		return new WP_REST_Response( $data, self::BAD_REQUEST );
	}

	/**
	 * Http not found.
	 *
	 * @param mixed $message The message.
	 *
	 * @return WP_REST_Response
	 */
	protected function not_found( $message ) {
		return $this->respond( new WP_REST_Response( array( 'message' => $message ), self::HTTP_NOT_FOUND ) );
	}

	/**
	 * Http Respond with JSON.
	 *
	 * @param mixed $thing The data.
	 *
	 * @return WP_REST_Response
	 */
	public function respond( $thing ) {
		return rest_ensure_response( $thing );
	}
}

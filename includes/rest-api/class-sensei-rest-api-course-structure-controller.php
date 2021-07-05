<?php
/**
 * File containing the class Sensei_REST_API_Course_Structure_Controller.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Course Structure REST API endpoints.
 *
 * @package Sensei
 * @author  Automattic
 * @since   3.6.0
 */
class Sensei_REST_API_Course_Structure_Controller extends \WP_REST_Controller {

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
	protected $rest_base = 'course-structure';

	/**
	 * Sensei_REST_API_Course_Structure_Controller constructor.
	 *
	 * @param string $namespace Routes namespace.
	 */
	public function __construct( $namespace ) {
		$this->namespace = $namespace;
	}

	/**
	 * Register the REST API endpoints for Course Structure.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<course_id>[0-9]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_course_structure' ],
					'permission_callback' => [ $this, 'can_user_get_structure' ],
					'args'                => [
						'context' => [
							'type'              => 'string',
							'default'           => 'view',
							'enum'              => [ 'view', 'edit' ],
							'sanitize_callback' => 'sanitize_key',
							'validate_callback' => 'rest_validate_request_arg',
						],
					],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'save_course_structure' ],
					'permission_callback' => [ $this, 'can_user_save_structure' ],
				],
			]
		);
	}

	/**
	 * Check user permission for reading course structure.
	 *
	 * @param WP_REST_Request $request WordPress request object.
	 *
	 * @return bool|WP_Error Whether the user can read course structure data. Error if not found.
	 */
	public function can_user_get_structure( WP_REST_Request $request ) {
		$course = $this->get_course( intval( $request->get_param( 'course_id' ) ) );
		if ( ! $course ) {
			return new WP_Error(
				'sensei_course_structure_missing_course',
				__( 'Course not found.', 'sensei-lms' ),
				[ 'status' => 404 ]
			);
		}

		if ( ! is_user_logged_in() ) {
			return 'publish' === get_post_status( $course );
		}

		return current_user_can( get_post_type_object( 'course' )->cap->read_post, $course->ID );
	}

	/**
	 * Check user permission for saving course structure.
	 *
	 * @param WP_REST_Request $request WordPress request object.
	 *
	 * @return bool|WP_Error Whether the user can save course structure data. Error if not found.
	 */
	public function can_user_save_structure( WP_REST_Request $request ) {
		$course = $this->get_course( intval( $request->get_param( 'course_id' ) ) );
		if ( ! $course ) {
			return new WP_Error(
				'sensei_course_structure_missing_course',
				__( 'Course not found.', 'sensei-lms' ),
				[ 'status' => 404 ]
			);
		}

		return $this->can_current_user_edit_course( $course->ID );
	}

	/**
	 * Check user permission for editing a course.
	 *
	 * @param int $course_id Course post ID.
	 *
	 * @return bool Whether the user can edit the course.
	 */
	private function can_current_user_edit_course( $course_id ) {
		return is_user_logged_in() && current_user_can( get_post_type_object( 'course' )->cap->edit_post, $course_id );
	}

	/**
	 * Get the course structure.
	 *
	 * @param WP_REST_Request $request WordPress request object.
	 *
	 * @return WP_REST_Response
	 */
	public function get_course_structure( WP_REST_Request $request ) {
		$course           = $this->get_course( intval( $request->get_param( 'course_id' ) ) );
		$course_structure = Sensei_Course_Structure::instance( $course->ID );

		$context = 'view';
		if ( 'edit' === $request['context'] && $this->can_current_user_edit_course( $course->ID ) ) {
			$context = 'edit';
		}

		$response = new WP_REST_Response();
		$response->set_data( $course_structure->get( $context ) );

		return $response;
	}

	/**
	 * Get the course structure.
	 *
	 * @param WP_REST_Request $request WordPress request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_course_structure( WP_REST_Request $request ) {
		$course           = $this->get_course( intval( $request->get_param( 'course_id' ) ) );
		$course_structure = Sensei_Course_Structure::instance( $course->ID );

		$input = json_decode( $request->get_body(), true );
		if ( ! is_array( $input ) || ! isset( $input['structure'] ) || ! is_array( $input['structure'] ) ) {
			return new WP_Error(
				'sensei_course_structure_invalid_input',
				__( 'Input for course structure was invalid.', 'sensei-lms' ),
				[ 'status' => 400 ]
			);
		}

		$raw_structure = $input['structure'];

		$result = $course_structure->save( $raw_structure );
		if ( is_wp_error( $result ) ) {
			return new WP_Error(
				$result->get_error_code(),
				$result->get_error_message(),
				[ 'status' => 400 ]
			);
		}

		if ( false === $result ) {
			return new WP_Error(
				'sensei_course_structure_unknown_error',
				__( 'An error occurred while saving the course structure.', 'sensei-lms' ),
				[ 'status' => 500 ]
			);
		}

		$response = new WP_REST_Response();
		$response->set_data( $course_structure->get( 'edit', wp_using_ext_object_cache() ) );

		return $response;
	}

	/**
	 * Get the course object.
	 *
	 * @param int $course_id
	 *
	 * @return WP_Post|null
	 */
	private function get_course( int $course_id ) {
		$course = get_post( $course_id );

		return $course ? $course : null;
	}

	/**
	 * Schema for the endpoint.
	 *
	 * @return array Schema object.
	 */
	public function get_schema() {
		if ( ! is_wp_version_compatible( '5.6.0' ) ) {
			// This is only used for tests right now so this is safe.
			return [
				'type'  => 'array',
				'items' => [
					'type' => 'object',
				],
			];
		}

		return [
			'type'  => 'array',
			'items' => [
				'oneOf' => [ $this->get_schema_lessons(), $this->get_schema_modules() ],
			],
		];
	}

	/**
	 * Get schema for lessons.
	 */
	private function get_schema_lessons() {
		return [
			'type'       => 'object',
			'required'   => [ 'type', 'title' ],
			'properties' => [
				'type'  => [
					'type'     => 'string',
					'pattern'  => 'lesson',
					'required' => true,
				],
				'id'    => [
					'description' => __( 'Lesson post ID', 'sensei-lms' ),
					'type'        => 'integer',
				],
				'title' => [
					'description' => __( 'Lesson title', 'sensei-lms' ),
					'type'        => 'string',
				],
				'draft' => [
					'description' => __( 'Whether the lesson is currently a draft', 'sensei-lms' ),
					'type'        => 'boolean',
					'readOnly'    => true,
				],
			],
		];
	}

	/**
	 * Get schema for modules.
	 */
	private function get_schema_modules() {
		return [
			'type'       => 'object',
			'required'   => [ 'type', 'title', 'lessons' ],
			'properties' => [
				'type'        => [
					'type'     => 'string',
					'pattern'  => 'module',
					'required' => true,
				],
				'id'          => [
					'description' => __( 'Module term ID', 'sensei-lms' ),
					'type'        => 'integer',
				],
				'title'       => [
					'description' => __( 'Module title', 'sensei-lms' ),
					'type'        => 'string',
				],
				'description' => [
					'description' => __( 'Module description', 'sensei-lms' ),
					'type'        => 'string',
				],
				'lessons'     => [
					'description' => __( 'Lessons in module', 'sensei-lms' ),
					'type'        => 'array',
					'items'       => $this->get_schema_lessons(),
				],
			],
		];
	}
}

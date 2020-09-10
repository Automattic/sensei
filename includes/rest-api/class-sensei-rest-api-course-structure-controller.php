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
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'save_course_structure' ],
					'permission_callback' => [ $this, 'can_user_save_structure' ],
					'args'                => [
						'structure' => [
							'description' => __( 'JSON string of the structure', 'sensei-lms' ),
							'required'    => true,
							'type'        => 'string',
						],
					],
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

		return is_user_logged_in() && current_user_can( get_post_type_object( 'course' )->cap->edit_post, $course->ID );
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

		$response = new WP_REST_Response();
		$response->set_data( $course_structure->get() );

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

		$structure_input = $this->parse_input( $request->get_param( 'structure' ) );
		if ( is_wp_error( $structure_input ) ) {
			return $structure_input;
		}

		$result = $course_structure->save( $structure_input );
		if ( is_wp_error( $result ) ) {
			return new WP_Error(
				$result->get_error_code(),
				$result->get_error_message(),
				[ 'status' => 500 ]
			);
		}

		$response = new WP_REST_Response();
		$response->set_data( $course_structure->get() );

		return $response;
	}

	/**
	 * Parse and sanitize the structure input.
	 *
	 * @param string $structure_input JSON string of structure.
	 *
	 * @return WP_Error|array False if the input is invalid.
	 */
	private function parse_input( string $structure_input ) : bool {
		$raw = json_decode( $structure_input, true );
		if ( ! is_array( $raw ) ) {
			return false;
		}
		$structure = [];
		foreach ( $raw as $raw_item ) {
			if ( ! is_array( $raw_item ) ) {
				return new WP_Error(
					'sensei_course_structure_invalid_item',
					__( 'Each item must be an array', 'sensei-lms' ),
					[ 'status' => 400 ]
				);
			}

			$item = $this->validate_and_sanitize_item( $raw_item );
			if ( is_wp_error( $item ) ) {
				return $item;
			}

			$structure[] = $item;
		}

		return $structure;
	}

	/**
	 * Validate and sanitize input item of structure.
	 *
	 * @param array $raw_item Raw item to sanitize.
	 *
	 * @return array|WP_Error
	 */
	private function validate_and_sanitize_item( array $raw_item ) {
		if ( ! isset( $raw_item['type'] ) || ! in_array( $raw_item['type'], [ 'module', 'lesson' ], true ) ) {
			return new WP_Error(
				'sensei_course_structure_invalid_item_type',
				__( 'All items must have a `type` set.', 'sensei-lms' ),
				[ 'status' => 400 ]
			);
		}

		if ( ! isset( $raw_item['title'] ) || '' === trim( sanitize_text_field( $raw_item['title'] ) ) ) {
			return new WP_Error(
				'sensei_course_structure_missing_title',
				__( 'All items must have a `title` set.', 'sensei-lms' ),
				[ 'status' => 400 ]
			);
		}

		if (
			'module' === $raw_item['type']
			&& (
				! isset( $raw_item['lessons'] )
				|| ! is_array( $raw_item['lessons'] )
			)
		) {
			return new WP_Error(
				'sensei_course_structure_missing_lessons',
				__( 'Module items must include a `lessons` array.', 'sensei-lms' ),
				[ 'status' => 400 ]
			);
		}

		$item = [
			'type'  => $raw_item['type'],
			'id'    => ! empty( $raw_item['id'] ) ? intval( $raw_item['id'] ) : null,
			'title' => trim( sanitize_text_field( $raw_item['title'] ) ),
		];

		if ( 'module' === $raw_item['type'] ) {
			$item['description'] = isset( $raw_item['description'] ) ? trim( sanitize_text_field( $raw_item['description'] ) ) : null;
			$item['lessons']     = [];
			foreach ( $raw_item['lessons'] as $raw_lesson ) {
				$lesson = $this->validate_and_sanitize_item( $raw_lesson );
				if ( is_wp_error( $lesson ) ) {
					return $lesson;
				}

				if ( 'lesson' !== $lesson['type'] ) {
					return new WP_Error(
						'sensei_course_structure_invalid_module_lesson',
						__( 'Module lessons array can only contain lessons.', 'sensei-lms' ),
						[ 'status' => 400 ]
					);
				}

				$item['lessons'][] = $lesson;
			}
		}

		return $item;
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
		return [
			'definitions' => [
				'lesson' => [
					'type'       => 'object',
					'required'   => [ 'type', 'title' ],
					'properties' => [
						'type'  => [
							'const' => 'lesson',
						],
						'id'    => [
							'description' => __( 'Lesson post ID', 'sensei-lms' ),
							'type'        => 'integer',
						],
						'title' => [
							'description' => __( 'Lesson title', 'sensei-lms' ),
							'type'        => 'string',
						],
					],
				],
				'module' => [
					'type'       => 'object',
					'required'   => [ 'type', 'title', 'lessons' ],
					'properties' => [
						'type'        => [
							'const' => 'module',
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
							'items'       => [ '$ref' => '#/definitions/lesson' ],
						],
					],
				],
			],
			'type'        => 'array',
			'items'       => [
				'oneOf' => [ [ '$ref' => '#/definitions/lesson' ], [ '$ref' => '#/definitions/module' ] ],
			],
		];
	}
}

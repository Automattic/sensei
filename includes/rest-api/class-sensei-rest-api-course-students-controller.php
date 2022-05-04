<?php
/**
 * File contains the Sensei_REST_API_Course_Students_Controller class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Course students actions controller class.
 *
 * @since 4.4.0
 */
class Sensei_REST_API_Course_Students_Controller extends \WP_REST_Controller {

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
	protected $rest_base = 'course-students';

	/**
	 * Sensei_REST_API_Course_Students_Controller constructor.
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
			$this->rest_base . '/batch',
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'batch_create_items' ],
					'permission_callback' => [ $this, 'batch_operation_permissions_check' ],
					'args'                => $this->get_args_schema(),
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'batch_remove_items' ],
					'permission_callback' => [ $this, 'batch_operation_permissions_check' ],
					'args'                => $this->get_args_schema(),
				],
			]
		);
	}

	/**
	 * Add users to courses.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function batch_create_items( WP_REST_Request $request ) {
		$params      = $request->get_params();
		$student_ids = $params['student_ids'];
		$course_ids  = $params['course_ids'];

		$result = [];
		foreach ( $student_ids as $user_id ) {
			$user               = new WP_User( $user_id );
			$result[ $user_id ] = false;
			if ( $user->exists() ) {
				foreach ( $course_ids as $course_id ) {
					$course_enrolment                 = Sensei_Course_Enrolment::get_course_instance( $course_id );
					$result[ $user_id ][ $course_id ] = $course_enrolment->enrol( $user_id );
				}
			}
		}

		return new WP_REST_Response( $result, WP_HTTP::OK );
	}

	/**
	 * Remove users from courses.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function batch_remove_items( WP_REST_Request $request ) : WP_REST_Response {
		$params     = $request->get_params();
		$user_ids   = $params['student_ids'];
		$course_ids = $params['course_ids'];
		$result     = [];
		foreach ( $user_ids as $user_id ) {
			$user = new WP_User( $user_id );

			if ( $user->exists() ) {
				foreach ( $course_ids as $course_id ) {
					$course_enrolment                 = Sensei_Course_Enrolment::get_course_instance( $course_id );
					$result[ $user_id ][ $course_id ] = $course_enrolment->withdraw( $user_id );
				}
			} else {
				$result[ $user_id ] = false;
			}
		}
		return new WP_REST_Response( $result, WP_HTTP::OK );
	}

	/**
	 * Check if the current user can add students to courses.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return boolean|WP_Error
	 */
	public function batch_operation_permissions_check( WP_REST_Request $request ) {
		$params          = $request->get_params();
		$course_ids      = $params['course_ids'];
		$edit_course_cap = get_post_type_object( 'course' )->cap->edit_post;
		foreach ( $course_ids as $course_id ) {
			$course = get_post( absint( $course_id ) );
			if ( empty( $course ) || 'course' !== $course->post_type ) {
				return new WP_Error(
					'sensei_course_student_batch_action_missing_course',
					__( 'Course not found.', 'sensei-lms' ),
					[
						'status'    => 404,
						'course_id' => $course_id,
					]
				);
			}
			if ( ! current_user_can( $edit_course_cap, $course_id ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Schema definition for endpoint arguments.
	 *
	 * @return array[]
	 */
	public function get_args_schema(): array {
		return [
			'course_ids'  => [
				'description' => 'Course Ids to perform the action on.',
				'type'        => 'array',
				'minItems'    => 1,
				'uniqueItems' => true,
				'items'       => [
					'type' => 'integer',
				],
				'required'    => true,
			],
			'student_ids' => [
				'description' => 'Student Ids to perform the action on',
				'type'        => 'array',
				'minItems'    => 1,
				'uniqueItems' => true,
				'items'       => [
					'type' => 'integer',
				],
				'required'    => true,
			],
		];
	}
}

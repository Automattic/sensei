<?php
/**
 * File contains the Sensei_REST_API_Course_Users_Controller class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Course users actions controller class.
 *
 * @since x.x.x
 */
class Sensei_REST_API_Course_Users_Controller extends \WP_REST_Controller {

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
	protected $rest_base = 'course-users/batch';

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
			$this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'add_users_to_courses' ],
					'permission_callback' => [ $this, 'can_add_users_to_courses' ],
					'args'                => $this->get_args_schema(),
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'remove_users_from_courses' ],
					'permission_callback' => [ $this, 'can_add_users_to_courses' ],
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
	public function add_users_to_courses( WP_REST_Request $request ) {
		$params     = $request->get_params();
		$user_ids   = $params['user_ids'];
		$course_ids = $params['course_ids'];
		foreach ( $user_ids as $user_id ) {
			$user = new WP_User( $user_id );
			if ( $user->exists() ) {
				foreach ( $course_ids as $course_id ) {
					$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
					$course_enrolment->enrol( $user_id );
				}
			}
		}

		return new WP_REST_Response( null, WP_HTTP::OK );
	}

	/**
	 * Remove users from courses.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function remove_users_from_courses( WP_REST_Request $request ) : WP_REST_Response {
		$params     = $request->get_params();
		$user_ids   = $params['user_ids'];
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
	 * Check if the current user can add users to courses.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return bool|WP_Error
	 */
	public function can_add_users_to_courses( WP_REST_Request $request ): bool {
		$params          = $request->get_params();
		$course_ids      = $params['course_ids'];
		$edit_course_cap = get_post_type_object( 'course' )->cap->edit_post;
		foreach ( $course_ids as $course_id ) {
			$course = get_post( absint( $course_id ) );
			if ( empty( $course ) || 'course' !== $course->post_type || ! current_user_can( $edit_course_cap, $course_id ) ) {
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
			'course_ids' => [
				'description' => 'Course Ids to perform the action on.',
				'type'        => 'array',
				'minItems'    => 1,
				'uniqueItems' => true,
				'items'       => [
					'type' => 'integer',
				],
				'required'    => true,
			],
			'user_ids'   => [
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

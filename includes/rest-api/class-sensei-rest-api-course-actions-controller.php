<?php
/**
 * File contains the Sensei_REST_API_Course_Actions_Controller class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Course actions controller class.
 *
 * @since 4.4.0
 */
class Sensei_REST_API_Course_Actions_Controller extends \WP_REST_Controller {

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
	protected $rest_base = 'course-actions';

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
			$this->rest_base . '/add',
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'add_users_to_courses' ],
					'permission_callback' => [ $this, 'can_add_users_to_courses' ],
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
	 * Check if the current user can add users to courses.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return bool
	 */
	public function can_add_users_to_courses( WP_REST_Request $request ) {
		$params          = $request->get_params();
		$course_ids      = $params['course_ids'];
		$edit_course_cap = get_post_type_object( 'course' )->cap->edit_post;
		foreach ( $course_ids as $course_id ) {
			$course = get_post( absint( $course_id ) );
			if ( empty( $course ) || ! current_user_can( $edit_course_cap, $course_id ) ) {
				return false;
			}
		}
		return true;
	}
}

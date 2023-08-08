<?php
/**
 * File containing the class Sensei_REST_API_Course_Utils_Controller.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Course Utilities REST API endpoints.
 *
 * @package Sensei
 * @author  Automattic
 * @since   4.9.0
 */
class Sensei_REST_API_Course_Utils_Controller extends \WP_REST_Controller {

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
	protected $rest_base = 'course-utils';

	/**
	 * Sensei_REST_API_Course_Utils_Controller constructor.
	 *
	 * @param string $namespace Routes namespace.
	 */
	public function __construct( $namespace ) {
		$this->namespace = $namespace;
	}

	/**
	 * Perform permissions check when editing post meta.
	 *
	 * @since  3.11.0
	 * @access private
	 *
	 * @param bool   $allowed True if allowed to view the meta field by default, false otherwise.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id  Lesson ID.
	 * @return bool Whether the user can edit the post meta.
	 */
	public function auth_callback( $allowed, $meta_key, $post_id ) {
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Register the REST API endpoints for Course Structure.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/update-teacher',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_teacher' ],
				'permission_callback' => [ $this, 'check_edit_permissions' ],
				'args'                => [
					\Sensei_Teacher::NONCE_FIELD_NAME => [
						'type'              => 'string',
						'required'          => true,
						'validate_callback' => [ $this, 'validate_nonce_value' ],
					],
					'post_id'                         => [
						'type'     => 'number',
						'required' => true,
					],
					'teacher'                         => [
						'type'     => 'number',
						'required' => true,
					],
					'custom_slugs'                    => [
						'type'     => 'string',
						'required' => false,
					],
				],
			]
		);
	}

	/**
	 * Save new teacher.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The json response.
	 */
	public function update_teacher( WP_REST_Request $request ) {
		$post_id             = $request->get_param( 'post_id' );
		$teacher             = $request->get_param( 'teacher' );
		$module_custom_slugs = $request->get_param( 'custom_slugs' );

		// If a custom slug is of a module that belongs to another teacher from another course, don't process farther.
		if ( isset( $module_custom_slugs ) ) {
			$module_custom_slugs = json_decode( sanitize_text_field( wp_unslash( $module_custom_slugs ) ) );
			foreach ( $module_custom_slugs as $module_custom_slug ) {
				$course_name = Sensei()->teacher::is_module_in_use_by_different_course_and_teacher( $module_custom_slug, $post_id, $teacher );
				if ( $course_name ) {
					return new WP_REST_Response(
						[
							'message' => 'Update Teacher Failed: Module in use by different course and teacher.',
						],
						WP_HTTP::BAD_REQUEST
					);
				}
			}
		}

		Sensei()->teacher->save_teacher( $post_id, $teacher );
		return new WP_REST_Response(
			[
				'status' => 'success',
			],
			WP_HTTP::OK
		);
	}

	/**
	 * Validates the nonce value.
	 *
	 * @param string $nonce The nonce value.
	 * @return boolean
	 */
	public function validate_nonce_value( $nonce ) {
		return wp_verify_nonce( $nonce, \Sensei_Teacher::NONCE_ACTION_NAME );
	}

	/**
	 * Check if the current user has permission to edit the post.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return boolean
	 */
	public function check_edit_permissions( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'post_id' );
		$post    = get_post( $post_id );

		$post_type = get_post_type_object( $post->post_type );
		return current_user_can( $post_type->cap->edit_post, $post_id );
	}
}

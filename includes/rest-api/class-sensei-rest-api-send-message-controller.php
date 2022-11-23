<?php
/**
 * File containing the class Sensei_REST_API_Send_Message_Controller.
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
class Sensei_REST_API_Send_Message_Controller extends \WP_REST_Controller {

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
	protected $rest_base = 'messages';

	/**
	 * Sensei_REST_API_Send_Message_Controller constructor.
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
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_new_message' ],
				'permission_callback' => [ $this, 'can_user_save_new_message' ],
				'args'                => [
					\Sensei_Messages::NONCE_FIELD_NAME => [
						'type'              => 'string',
						'required'          => true,
						'validate_callback' => [ $this, 'validate_nonce_value' ],
					],
					'post_id'                          => [
						'type'     => 'number',
						'required' => true,
					],
					'contact_message'                  => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'wp_kses_post',
					],
				],
			]
		);
	}

	/**
	 * Check user permission for submitting a new message.
	 *
	 * @param WP_REST_Request $request WordPress request object.
	 *
	 * @return bool Whether the user can submit a new message.
	 */
	public function can_user_save_new_message( WP_REST_Request $request ) {

		if ( ! is_user_logged_in() ) {
			return false;
		}

		$post_id = $request->get_param( 'post_id' );

		// If it is a quiz post then get it's lesson post.
		$post = get_post( $post_id );
		if ( 'quiz' === $post->post_type ) {
			$post = get_post( \Sensei()->quiz->get_lesson_id( $post->ID ) );
		}

		// If it is a lesson post then get it's course post.
		if ( 'lesson' === $post->post_type ) {
			$post = get_post( \Sensei()->lesson->get_course_id( $post->ID ) );
		}

		// If we did not get the course post till now then not allowed.
		if ( 'course' !== $post->post_type ) {
			return false;
		}

		// Allow user to submit a message if they are enrolled to the course.
		if ( \Sensei()->course->is_user_enrolled( $post->ID, get_current_user_id() ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Save new message.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return string The json response.
	 */
	public function save_new_message( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'post_id' );
		$message = $request->get_param( 'contact_message' );

		$fail_response = [
			'success' => false,
		];

		$post = get_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $fail_response;
		}

		// Save the message.
		$message_id = \Sensei()->messages->save_new_message_post(
			get_current_user_id(),
			$post->post_author,
			$message,
			$post->ID
		);

		// Return success object with message_id on success.
		if ( $message_id ) {
			return [
				'success'    => true,
				'message_id' => $message_id,
				'message'    => $message,
			];
		}

		// Return fail object on fail.
		return $fail_response;
	}

	/**
	 * Validates the nonce value.
	 *
	 * @param string $nonce The nonce value.
	 * @return boolean
	 */
	public function validate_nonce_value( $nonce ) {
		return wp_verify_nonce( $nonce, \Sensei_Messages::NONCE_ACTION_NAME );
	}
}

<?php
/**
 * Sensei Tour API.
 *
 * @package sensei
 * @since   4.22.0
 */

namespace Sensei\Admin\Tour;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Tour REST API endpoints.
 *
 * @since   4.22.0
 */
class Sensei_REST_API_Tour_Controller extends \WP_REST_Controller {

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
	protected $rest_base = 'tour';

	/**
	 * Sensei Tour.
	 *
	 * @var Sensei_Tour
	 */
	private $tour;

	/**
	 * Sensei_REST_API_Tour_Controller constructor.
	 *
	 * @param string      $rest_namespace REST API namespace.
	 * @param Sensei_Tour $tour           Sensei Tour.
	 */
	public function __construct( $rest_namespace, Sensei_Tour $tour ) {
		$this->namespace = $rest_namespace;
		$this->tour      = $tour;
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'set_tour_completion_status' ],
					'permission_callback' => [ $this, 'get_tour_permissions_check' ],
					'args'                => [
						'tour_id'  => [
							'required' => true,
							'type'     => 'string',
						],
						'complete' => [
							'required' => true,
							'type'     => 'boolean',
						],
					],
				],
			]
		);
	}

	/**
	 * Set tour status.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function set_tour_completion_status( $request ) {
		$complete = ! ! $request->get_param( 'complete' );
		$tour_id  = sanitize_text_field( $request->get_param( 'tour_id' ) ?? '' );

		$this->tour->set_tour_completion_status( $tour_id, $complete, get_current_user_id() );

		return new \WP_REST_Response( true, 200 );
	}

	/**
	 * Check if a given request has access to get tour.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|\WP_Error
	 */
	public function get_tour_permissions_check( $request ) {
		return current_user_can( \Sensei_Admin::get_top_menu_capability() );
	}
}

<?php
/**
 * Sensei Tour API compatibility controller.
 *
 * @package sensei
 * @since   4.22.0
 */

namespace Sensei\Admin\Tour;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deprecated Sensei Tour REST API endpoints.
 *
 * @since 4.22.0
 * @deprecated $$next-version$$ The onboarding tours are no longer supported.
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
	 * @deprecated $$next-version$$ The onboarding tours are no longer supported.
	 *
	 * @param string      $rest_namespace REST API namespace.
	 * @param Sensei_Tour $tour           Sensei Tour.
	 */
	public function __construct( $rest_namespace, Sensei_Tour $tour ) {
		_deprecated_constructor( __CLASS__, '$$next-version$$' );
		$this->namespace = $rest_namespace;
		$this->tour      = $tour;
	}

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @deprecated $$next-version$$ The onboarding tours are no longer supported.
	 */
	public function register_routes() {
		_deprecated_function( __METHOD__, '$$next-version$$' );
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'set_tour_completion_status' ),
					'permission_callback' => array( $this, 'get_tour_permissions_check' ),
					'args'                => array(
						'tour_id'  => array(
							'required' => true,
							'type'     => 'string',
						),
						'complete' => array(
							'required' => true,
							'type'     => 'boolean',
						),
					),
				),
			)
		);
	}

	/**
	 * Set tour status.
	 *
	 * @deprecated $$next-version$$ The onboarding tours are no longer supported.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function set_tour_completion_status( $request ) {
		_deprecated_function( __METHOD__, '$$next-version$$' );
		$complete = (bool) $request->get_param( 'complete' );
		$tour_id  = sanitize_text_field( $request->get_param( 'tour_id' ) ?? '' );

		$this->tour->set_tour_completion_status( $tour_id, $complete, get_current_user_id() );

		return new \WP_REST_Response( true, 200 );
	}

	/**
	 * Check if a given request has access to update a tour.
	 *
	 * @deprecated $$next-version$$ The onboarding tours are no longer supported.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|\WP_Error
	 */
	public function get_tour_permissions_check( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Required REST callback signature.
		_deprecated_function( __METHOD__, '$$next-version$$' );

		return current_user_can( \Sensei_Admin::get_top_menu_capability() );
	}
}

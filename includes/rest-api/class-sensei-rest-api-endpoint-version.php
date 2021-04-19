<?php
// phpcs:ignoreFile
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class Sensei_REST_API_Endpoint_Version
 * returns the current sensei version;
 *
 * @deprecated 3.11.0
 */
class Sensei_REST_API_Endpoint_Version extends Sensei_REST_API_Controller {
	protected $base = '/version';
	public function register() {
		_deprecated_function( __METHOD__, '3.11.0' );

		register_rest_route(
			$this->api->get_api_prefix(),
			$this->base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * @deprecated 3.11.0
	 */
	public function get_items( $request ) {
		if ( ! headers_sent() ) {
			header( 'Warning: 299 - Deprecated API' );
		}

		_deprecated_function( __METHOD__, '3.11.0' );

		return new WP_REST_Response( array( 'sensei_version' => Sensei()->version ), 200 );
	}

	/**
	 * @deprecated 3.11.0
	 */
	public function get_items_permissions_check( $request ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return true;
	}
}

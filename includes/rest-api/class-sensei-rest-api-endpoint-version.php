<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Sensei_REST_API_Endpoint_Version
 * returns the current sensei version;
 */
class Sensei_REST_API_Endpoint_Version extends Sensei_REST_API_Controller {
    protected $base = '/version';
    public function register() {
        register_rest_route( $this->api->get_api_prefix(),  $this->base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                'args'                => array()
            )
        ) );
    }

    public function get_items( $request ) {
        return new WP_REST_Response( array( 'sensei_version' => Sensei()->version ), 200 );
    }

    public function get_items_permissions_check( $request ) {
        return true;
    }
}
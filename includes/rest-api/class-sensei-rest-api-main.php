<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Sensei_REST_API_Main
 * @package rest-api
 */
class Sensei_REST_API_Main {
    const SENSEI_NAMESPACE = 'sensei/v1';
    private $endpoints = array();

    /**
     * Sensei_REST_API constructor.
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register' ) );
    }

    /**
     * register all endpoints
     */
    public function register() {
        $this->endpoints = array(
            '/sensei-version' => new Sensei_REST_API_Endpoint_Version()
        );
        if ( $this->can_use_rest_api() ) {
            foreach ($this->endpoints as $base => $endpoint ) {
                register_rest_route( self::SENSEI_NAMESPACE,  $base, $endpoint->register() );
            }
        }
    }

    public function can_use_rest_api() {
        return function_exists( 'register_rest_route' );
    }
}
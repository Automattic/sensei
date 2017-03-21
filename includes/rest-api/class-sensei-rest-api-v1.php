<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Sensei_REST_API_V1
 * @package rest-api
 */
class Sensei_REST_API_V1 {
    /**
     * @var Sensei_REST_API_Helper
     */
    private $helper;
    private $namespace = 'sensei/v1';
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
        if ( !$this->can_use_rest_api() ) {
            return;
        }
        $this->helper = new Sensei_REST_API_Helper( $this );
        $this->endpoints = $this->get_endpoints();
        foreach ($this->endpoints as $base => $endpoint ) {
            register_rest_route( $this->get_name_space(),  $base, $endpoint->register() );
        }
    }

    /**
     * @return Sensei_REST_API_Helper
     */
    public function get_helper() {
        return $this->helper;
    }

    public function get_endpoints() {
        return apply_filters( 'sensei_rest_api_v1_get_endpoints', array(
            '/version'             => new Sensei_REST_API_Endpoint_Version( $this ),
            '/courses/(?P<id>\d+)' => new Sensei_REST_API_Endpoint_Courses( $this ),
            '/courses'             => new Sensei_REST_API_Endpoint_Courses( $this )
        ) );
    }

    public function can_use_rest_api() {
        $rest_api_enabled = Sensei()->feature_flags->is_enabled( 'rest_api_v1' );
        return $rest_api_enabled && function_exists( 'register_rest_route' );
    }
    
    public function get_name_space() {
        return apply_filters( 'sensei_rest_api_v1_get_namespace', $this->namespace );
    }
}
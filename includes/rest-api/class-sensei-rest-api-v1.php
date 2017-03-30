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
    private $api_prefix = 'sensei/v1';
    private $endpoints = array();

    /**
     * Sensei_REST_API constructor.
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register' ) );
    }

    /**
     * bootstrap registry
     * register all endpoints
     */
    public function register() {
        if ( !$this->can_use_rest_api() ) {
            return;
        }
        Sensei_Domain_Models_Registry::get_instance()
            ->set_data_store( 'users', new Sensei_Domain_Models_User_Data_Store() )
            ->set_data_store_for_domain_model( 'Sensei_Domain_Models_Course', new Sensei_Domain_Models_Course_Data_Store_Cpt() )
            ->set_data_store_for_domain_model( 'Sensei_Domain_Models_Module', new Sensei_Domain_Models_Module_Data_Store() );
        $this->helper = new Sensei_REST_API_Helper( $this );
        $this->endpoints = $this->get_endpoints();
        foreach ($this->endpoints as $endpoint ) {
            $endpoint->register( $this );
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
            new Sensei_REST_API_Endpoint_Version( $this ),
            new Sensei_REST_API_Endpoint_Courses( $this ),
            new Sensei_REST_API_Endpoint_Modules( $this )
        ) );
    }

    public function can_use_rest_api() {
        $rest_api_enabled = Sensei()->feature_flags->is_enabled( 'rest_api_v1' );
        return $rest_api_enabled && function_exists( 'register_rest_route' );
    }
    
    public function get_api_prefix() {
        return apply_filters( 'sensei_rest_api_v1_get_api_prefix', $this->api_prefix );
    }
}
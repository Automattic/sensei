<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

class Sensei_REST_API_Helper {
    /**
     * @var Sensei_REST_API_V1
     */
    private $api;

    /**
     * Sensei_REST_API_Helpers constructor.
     * @param $api Sensei_REST_API_V1
     */
    public function __construct($api ) {
        $this->api = $api;
    }

    public function is_numeric( $thing, $request, $key ) {
        return is_numeric( $thing );
    }

    /**
     * @param $rest_namespace
     * @param $domain_model
     * @param $args
     * @return string
     */
    public function build_url_for_item( $rest_namespace, $domain_model ) {
        return $this->rest_url() . $rest_namespace . '/' . $domain_model->get_id();
    }

    public function rest_url() {
        return rest_url();
    }

    public function base_namespace_url() {
        return $this->rest_url() . $this->api->get_api_prefix();
    }
}
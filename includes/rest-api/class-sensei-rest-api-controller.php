<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

class Sensei_REST_API_Controller extends WP_REST_Controller {
    /**
     * @var Sensei_REST_API_V1
     */
    protected $api;

    /**
     * Sensei_REST_API_Controller constructor.
     * @param $api Sensei_REST_API_V1
     */
    public function __construct( $api ) {
        $this->api = $api;
    }

    protected function succeed( $data ) {
        return new WP_REST_Response( $data, 200 );
    }

    protected function created( $data ) {
        return new WP_REST_Response( $data, 201 );
    }

    protected function fail_with( $data ) {
        return new WP_REST_Response( $data, 400 );
    }

    protected function not_found( $message ) {
        return new WP_REST_Response( array( 'message' => $message ), 404 );
    }
}
<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

class Sensei_REST_API_Controller extends WP_REST_Controller {
    const HTTP_CREATED   = 201;
    const HTTP_SUCCESS   = 200;
    const BAD_REQUEST    = 400;
    const HTTP_NOT_FOUND = 404;

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
        return new WP_REST_Response( $data, self::HTTP_SUCCESS );
    }

    protected function created( $data ) {
        return new WP_REST_Response( $data, self::HTTP_CREATED );
    }

    protected function fail_with( $data ) {
        return new WP_REST_Response( $data, self::BAD_REQUEST );
    }

    protected function not_found( $message ) {
        return $this->respond( new WP_REST_Response( array( 'message' => $message ), self::HTTP_NOT_FOUND) );
    }

    public function respond( $thing ) {
        return rest_ensure_response( $thing );
    }
}
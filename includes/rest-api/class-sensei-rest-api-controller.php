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
     * @var string the endpoint base
     */
    protected $base = null;
    /**
     * @var string the domain model class this endpoint serves
     */
    protected $domain_model_class = null;
    /**
     * @var Sensei_Domain_Models_Factory
     */
    protected $factory = null;

    /**
     * Sensei_REST_API_Controller constructor.
     * @param $api Sensei_REST_API_V1
     * @throws Sensei_Domain_Models_Exception
     */
    public function __construct( $api ) {
        $this->api = $api;
        if ( empty( $this->base ) ) {
            throw new Sensei_Domain_Models_Exception( 'Need to put a string with a backslash in $base' );
        }
        if ( !empty( $this->domain_model_class ) ) {
            $this->factory = Sensei_Domain_Models_Registry::get_instance()
                ->get_factory( $this->domain_model_class );
        }
    }

    /**
     * @param $entity array|Sensei_Domain_Models_Model_Collection|Sensei_Domain_Models_Model_Abstract
     * @return array
     */
    protected function prepare_data_transfer_object( $entity ) {
        if ( is_array( $entity ) ) {
            return $entity;
        }

        if ( is_a( $entity, 'Sensei_Domain_Models_Model_Collection' ) ) {
            $results = array();
            foreach ( $entity->get_items() as $model ) {
                $results[] = $this->model_to_data_transfer_object( $model );
            }
            return $results;
        }

        if ( is_a( $entity, 'Sensei_Domain_Models_Model_Abstract' ) ) {
            return $this->model_to_data_transfer_object( $entity );
        }

        return $entity;
    }

    /**
     * @param $model Sensei_Domain_Models_Model_Abstract
     * @return array
     */
    protected function model_to_data_transfer_object($model ) {
        $result = array();
        foreach ($model->get_data_transfer_object_field_mappings() as $mapping_name => $field_name ) {
            $value = $model->__get( $field_name );
            $result[$mapping_name] = $value;
        }
        $result['_links'] = $this->add_links( $model );
        return $result;
    }

    protected function add_links( $model ) {
        return array();
    }

    public function register() {
        throw new Sensei_Domain_Models_Exception( 'override me' );
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
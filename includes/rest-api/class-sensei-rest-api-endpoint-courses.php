<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

class Sensei_REST_API_Endpoint_Courses extends Sensei_REST_API_Controller {
    
    protected $base = '/courses';
    protected $domain_model_class = 'Sensei_Domain_Models_Course';

    public function register() {
        $prefix = $this->api->get_api_prefix();
        register_rest_route( $prefix, $this->base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
            ),
            array(
                'methods'         => WP_REST_Server::CREATABLE,
                'callback'        => array( $this, 'create_item' ),
                'permission_callback' => array( $this, 'create_item_permissions_check' ),
                'args'            => $this->get_endpoint_args_for_item_schema( true ),
            )
        ) );
        register_rest_route( $prefix,  $this->base . '/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
            ),
            array(
                'methods'         => WP_REST_Server::EDITABLE,
                'callback'        => array( $this, 'create_item' ),
                'permission_callback' => array( $this, 'create_item_permissions_check' ),
                'args'            => $this->get_endpoint_args_for_item_schema( true ),
            ),
            array(
                'methods'         => WP_REST_Server::DELETABLE,
                'callback'        => array( $this, 'delete_item' ),
                'permission_callback' => array( $this, 'create_item_permissions_check' ),
                'args'            => $this->get_endpoint_args_for_item_schema( true ),
            ),
        ) );
    }

    public function get_items( $request ) {
        $item_id = isset( $request['id'] ) ? absint( $request['id'] ) : null;
        
        if (null === $item_id ) {
            $models = $this->factory->all();
            $data = $this->prepare_for_data_transfer( $models );
            return $this->succeed( $data );
        }

        $course = $this->factory->find_one_by_id($item_id);
        if ( empty( $course ) ) {
            return $this->not_found( __( 'Course not found' ) );
        }

        return $this->succeed( $this->prepare_for_data_transfer( $course ) );
    }

    /**
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function create_item( $request ) {
        $update_existing = isset( $request['id'] ) ? absint( $request['id'] ) : null;
        $exists = null;
        if ( null !== $update_existing ) {
            $exists = $this->factory->find_one_by_id( $update_existing );
            if ( empty( $exists ) ) {
                return $this->not_found( __( 'Course does not exist', 'woothemes-sensei' ) );
            }
        }

        if ( null !== $update_existing && null === $exists ) {
            $course = $exists->merge_updates_from_request( $request );
        } else {
            $course = $this->prepare_item_for_database( $request );
        }
        $validation = $course->validate();
        if ( true !== $validation ) {
            // Got a validation Error. Return that
            return $this->fail_with( $validation );
        }
        $id_or_error = $course->upsert();
        if ( is_wp_error( $id_or_error ) ) {
            return $this->fail_with( $id_or_error );
        }

        return $this->created( $this->prepare_for_data_transfer( array('id' => absint( $id_or_error ) ) ) );
    }
    
    public function delete_item( $request ) {
        $id = isset( $request['id'] ) ? absint( $request['id'] ) : null;
        if ( empty( $id ) ) {
            return $this->fail_with( __( 'No Course ID provided', 'woothemes-sensei' ) );
        }
        $course = $this->factory->find_one_by_id( $id );
        if ( null === $course ) {
            return $this->not_found( __( 'Course does not exist', 'woothemes-sensei' ) );
        }
        $result = $course->delete();
        return $this->succeed( $result );
    }

    /**
     * Prepare the item for create or update operation.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_Error|object $prepared_item
     */
    protected function prepare_item_for_database( $request ) {
        return $this->factory->new_from_request( $request) ;
    }

    /**
     * @param WP_REST_Request $request
     * @return bool
     */
    public function get_items_permissions_check( $request ) {
        return $this->admin_permissions_check( $request );
    }

    /**
     * @param WP_REST_Request $request
     * @return bool
     */
    public function create_item_permissions_check( $request ) {
        return $this->admin_permissions_check( $request );
    }

    public function delete_item_permissions_check( $request ) {
        return $this->admin_permissions_check( $request );
    }

    private function admin_permissions_check( $request ) {
        // we are only going to allow admins to access the rest api for now
        return Sensei()->feature_flags->is_enabled( 'rest_api_v1_skip_permissions' ) || current_user_can( 'manage_sensei' );
    }

    /**
     * @param $entity array|Sensei_Domain_Models_Model_Collection|Sensei_Domain_Models_Course
     * @return array
     */
    private function prepare_for_data_transfer( $entity ) {
        if ( is_array( $entity ) ) {
            return $entity;
        }

        if ( is_a( $entity, 'Sensei_Domain_Models_Model_Collection' ) ) {
            $results = array();
            foreach ( $entity->get_items() as $model ) {
                $results[] = $this->model_to_json( $model );
            }
            return $results;
        }

        if ( is_a( $entity, 'Sensei_Domain_Models_Course' ) ) {
            return $this->model_to_json( $entity );
        }

        return $entity;
    }

    /**
     * @param $model Sensei_Domain_Models_Course
     * @return array
     */
    private function model_to_json( $model ) {
        $result = array();
        foreach ( $model->get_json_field_mappings() as $mapping_name => $field_name ) {
            $value = $model->__get( $field_name );
            $result[$mapping_name] = $value;
        }
        $result['_links'] = $this->add_links( $model );
        return $result;
    }

    /**
     * @param $model Sensei_Domain_Models_Course
     * @return array
     */
    protected function add_links( $model ) {
        $helper = $this->api->get_helper();
        return array(
            'self' => array(
                array(
                    'href' => esc_url( $helper->base_namespace_url() . '/courses/' . $model->get_id() )
                )
            ),
            'collection' => array(
                array(
                    'href' => esc_url( $helper->base_namespace_url() . '/courses/' )
                )
            ),
            'author' => array(
                array(
                    'href' => esc_url( $helper->rest_url() . 'wp/v2/users/' . $model->author )
                )
            )
        );
    }
}